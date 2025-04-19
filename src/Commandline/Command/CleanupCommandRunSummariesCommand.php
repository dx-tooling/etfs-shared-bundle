<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Commandline\Command;

use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use EnterpriseToolingForSymfony\SharedBundle\Commandline\Entity\CommandRunSummary;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Format;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'commandline:cleanup-command-run-summaries',
    description: 'Clean up command run summaries that are no longer needed.'
)]
class CleanupCommandRunSummariesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DateAndTimeService     $dateAndTimeService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        try {
            // Step 1: Remove summaries older than 48 hours
            $deletedOldSummaries = $this->deleteOldSummaries($connection);
            $io->success(sprintf('Deleted %d summaries that were started more than 48 hours ago', $deletedOldSummaries));

            // Step 2: Remove normally finished summaries older than 24 hours
            $deletedFinishedSummaries = $this->deleteFinishedSummaries($connection);
            $io->success(sprintf('Deleted %d summaries that finished normally and were started more than 24 hours ago', $deletedFinishedSummaries));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('An error occurred during cleanup: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Delete summaries that are older than 48 hours.
     *
     * @throws \Doctrine\DBAL\Exception|DateMalformedStringException
     * @throws Exception
     */
    private function deleteOldSummaries(Connection $connection): int
    {
        $tableName = $this->entityManager->getClassMetadata(CommandRunSummary::class)->getTableName();
        $threshold = $this
            ->dateAndTimeService
            ->getDateTimeImmutable()
            ->modify('-48 hours')
            ->format(Format::DATABASE_DATETIME->value);

        $result = $connection->executeStatement(
            "DELETE FROM $tableName WHERE started_at < :threshold",
            ['threshold' => $threshold]
        );

        if (!is_int($result)) {
            throw new Exception('Could not delete old summaries');
        }

        return $result;
    }

    /**
     * Delete summaries that finished normally and are older than 24 hours.
     *
     * @throws \Doctrine\DBAL\Exception|DateMalformedStringException
     * @throws Exception
     */
    private function deleteFinishedSummaries(Connection $connection): int
    {
        $tableName = $this->entityManager->getClassMetadata(CommandRunSummary::class)->getTableName();
        $threshold = $this
            ->dateAndTimeService
            ->getDateTimeImmutable()
            ->modify('-24 hours')
            ->format(Format::DATABASE_DATETIME->value);

        $result = $connection->executeStatement(
            "DELETE FROM $tableName WHERE finished_normally = true AND started_at < :threshold",
            ['threshold' => $threshold]
        );

        if (!is_int($result)) {
            throw new Exception('Could not delete old summaries');
        }

        return $result;
    }
}
