<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Commandline\Command;

use Doctrine\ORM\EntityManagerInterface;
use EnterpriseToolingForSymfony\SharedBundle\Commandline\Entity\CommandRunSummary;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Format;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Timezone;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use EnterpriseToolingForSymfony\SharedBundle\Locking\Service\LockService;
use EnterpriseToolingForSymfony\SharedBundle\Rollout\Service\RolloutService;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Throwable;

date_default_timezone_set(Timezone::UTC->value);

abstract class EnhancedCommand extends Command
{
    protected CommandRunSummary $commandRunSummary;

    protected ?SharedLockInterface $lock = null;

    protected readonly string $symfonyEnviroment;

    protected float $lockTtl = 60 * 10; // 10 minutes

    protected ?OutputInterface $output = null;

    protected bool $isTestEnvironment = false;

    protected bool $isDevEnvironment = false;

    protected bool $unexpectedShutdown = true;

    protected bool $ignoreRolloutSignal = false;

    protected int $finishedStartingAt;

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly RolloutService           $rolloutService,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LoggerInterface        $logger,
        protected readonly LockService            $lockService,
        ParameterBagInterface                     $parameterBag
    ) {
        $environment = $parameterBag->get('kernel.environment');
        if (!is_string($environment)) {
            throw new RuntimeException('kernel.environment must be a string');
        }
        $this->symfonyEnviroment = $environment;
        parent::__construct();
        gc_enable();

        $this->commandRunSummary = new CommandRunSummary(
            self::class,
            '',
            '',
            DateAndTimeService::getDateTimeImmutable()
        );

        $this->entityManager->persist($this->commandRunSummary);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $context
     */
    final protected function outputAndLog(
        string $message,
        string $logLevel = LogLevel::INFO,
        array  $context = []
    ): void {
        $this->output?->writeln($message);
        $message = '[Command Run][' . $this->commandRunSummary->getNumberOfHandledElements() . '][' . $this->commandRunSummary->getId() . '][' . $this->commandRunSummary->getArguments() . '][' . $this->commandRunSummary->getOptions() . '] ' . $message;

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * @throws Exception
     */
    private function getLockOrExit(string $lockResource): SharedLockInterface
    {
        $lock = $this->lockService->acquireLock($lockResource, $this->lockTtl);
        if ($lock === null) {
            $this->commandRunSummary->setFinishedDueToNoInitialLock(true);
            $this->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
            $this->entityManager->persist($this->commandRunSummary);
            $this->entityManager->flush();

            if ($this->isDevEnvironment) {
                $this->outputAndLog(
                    "<fg=yellow>Exiting because I could not get a lock for id </fg=yellow>$lockResource<fg=yellow>.</fg=yellow>"
                );
            }
            exit(Command::SUCCESS);
        }

        return $lock;
    }

    final protected function setExpectedNumberOfElementsToHandle(
        ?int $expectedNumberOfElementsToHandle
    ): void {
        $this->commandRunSummary->setExpectedNumberOfElementsToHandle(
            $expectedNumberOfElementsToHandle
        );
    }

    final protected function getExpectedNumberOfElementsToHandle(): ?int
    {
        return $this->commandRunSummary->getExpectedNumberOfElementsToHandle();
    }

    final protected function setNumberOfHandledElements(
        int $numberOfHandledElements
    ): void {
        $this->commandRunSummary->setNumberOfHandledElements(
            $numberOfHandledElements
        );
    }

    final protected function getNumberOfHandledElements(): int
    {
        return $this->commandRunSummary->getNumberOfHandledElements();
    }

    final protected function increaseNumberOfHandledElements(int $by = 1): void
    {
        $this->commandRunSummary->setNumberOfHandledElements(
            $this->commandRunSummary->getNumberOfHandledElements() + $by
        );
    }

    // Call this as soon as your Command is ready to do its thing, but before it actually does anything
    // that has a lasting effect.
    // Make sure to choose a large enough $lockTtl - your script must not take longer for one loop
    // iteration (that is, between calls to onLoopEnd), than $lockTtl, or it looses its lock and
    // will be terminated to avoid parallel runs.
    /**
     * @throws Exception
     */
    final protected function onStart(
        OutputInterface $output,
        string          $commandName,
        InputInterface  $input,
        string          $memoryLimit,
        int             $timeLimit,
        ?float          $lockTtl = null
    ): void {
        $this->output = $output;
        $this->outputAndLog('<fg=green>About to start.</fg=green>');
        if ($this->symfonyEnviroment    === 'test'
            || $this->symfonyEnviroment === 'test_mail'
        ) {
            $this->isTestEnvironment = true; // In test environments, we work without locks and without exits.
        }

        if ($this->symfonyEnviroment === 'dev') {
            $this->isDevEnvironment = true;
        }

        ini_set('memory_limit', $memoryLimit);
        set_time_limit($timeLimit);

        $this->commandRunSummary->setCommandName($commandName);
        $this->commandRunSummary->setArguments((string)json_encode($input->getArguments()));
        $this->commandRunSummary->setOptions((string)json_encode($input->getOptions()));

        $this->entityManager->persist($this->commandRunSummary);
        $this->entityManager->flush();
        $this->outputAndLog("<fg=green>Going to use command run summary with id</fg=green> {$this->commandRunSummary->getId()}<fg=green>.</fg=green>");

        $this->checkAndHandleRolloutSignal();

        if (!$this->isTestEnvironment) {
            if ($lockTtl !== null) {
                $this->lockTtl = $lockTtl;
            }
            usleep(rand(0, 500000)); // Lower the chances a bit that two processes want a lock at exactly the same time
            $this->lock = $this->getLockOrExit(
                $commandName
                . '|'
                . sha1(
                    json_encode($input->getArguments()) . json_encode($input->getOptions())
                ),
            );

            // We catch signals that end our script (e.g. hitting CTRL-C or being OS kill-ed), to ensure as clean an exit as possible.
            // This makes the signals actually trigger only during onLoopEnd calls, when the Command is assumed to be in a state
            // where exiting is safe. If an OS signal is received and handled, the shutdown handler is triggered before the script ends.
            pcntl_signal(SIGINT, [EnhancedCommand::class, 'handleSig']);
            pcntl_signal(SIGQUIT, [EnhancedCommand::class, 'handleSig']);
            pcntl_signal(SIGTERM, [EnhancedCommand::class, 'handleSig']);

            // This ensures (on a best effort basis) some cleanups like lock release even if we end prematurely (e.g. due to a SIG)
            register_shutdown_function(
                [EnhancedCommand::class, 'onShutdown'],
                $this
            );
        }
        $this->outputAndLog('<fg=green>Starting.</fg=green>');
        $this->finishedStartingAt = time();
    }

    /**
     * @throws Exception
     */
    final public static function onShutdown(EnhancedCommand $enhancedCommand): void
    {
        if ($enhancedCommand->unexpectedShutdown === true) {
            $enhancedCommand->outputAndLog('<fg=yellow>Shutdown handler has been triggered, trying best effort cleanups.<fg=yellow>');

            if (!is_null($enhancedCommand->lock)) {
                try {
                    $enhancedCommand->lock->release();
                } catch (Throwable $t) {
                    $enhancedCommand->outputAndLog("<fg=yellow>Could not release lock in shutdown handler: {$t->getMessage()}</fg=yellow>");
                }
            }

            if ($enhancedCommand->entityManager->isOpen()) {
                $allocatedMemory = (int)round(memory_get_usage(true) / 1024 / 1024);
                if ($allocatedMemory > $enhancedCommand->commandRunSummary->getMaxAllocatedMemory()) {
                    $enhancedCommand->commandRunSummary->setMaxAllocatedMemory($allocatedMemory);
                }
                $enhancedCommand->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
                $enhancedCommand->commandRunSummary->setFinishedNormally(false);
                try {
                    $enhancedCommand->entityManager->persist($enhancedCommand->commandRunSummary);
                    $enhancedCommand->entityManager->flush();
                } catch (Throwable $t) {
                    $enhancedCommand->outputAndLog(
                        '<fg=yellow>Could not persist command run summary in shutdown handler</fg=yellow>: ' . $t->getMessage()
                    );
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    final protected function onThrowableCatched(
        Throwable       $t,
        EnhancedCommand $enhancedCommand
    ): void {
        if (!$this->entityManager->isOpen()) {
            $enhancedCommand->outputAndLog("<fg=red>Entity Manager is closed after Throwable with message '{$t->getMessage()}', triggering premature shutdown.</fg=red>");
            self::onShutdown($this);
        }
    }

    // Call this whenever your Command is in a state where exiting is safe, and where it is about to start working
    // on a larger task. Only recommended for Commands with really large work batches - for normal Commands, using
    // only onLoopEnd is just fine.
    final protected function onLoopStart(
        int $probability = 1
    ): void {
        $this->onLoopEnd($probability);
    }

    // Call this whenever your Command has finished a relevant batch of work; when calling this, your Command must
    // be in a state where it is safe to be terminated.
    // You can pass a probability parameter, which determines the likelihood that this function actually
    // does its thing. If it does, true is returned, and false otherwise.
    /**
     * @throws Exception
     */
    final protected function onLoopEnd(
        int $probability = 1
    ): bool {
        pcntl_signal_dispatch();
        $this->checkAndHandleRolloutSignal();

        if ($probability < 1) {
            $probability = 1;
        }

        if (rand(1, $probability) !== 1) {
            return false;
        }

        $this->entityManager->flush();

        if ($this->lock !== null && !$this->isTestEnvironment) {
            if ($this->lock->getRemainingLifetime() <= 0) {
                $this->commandRunSummary->setFinishedDueToGotBehindLock(true);
                $this->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
                $this->entityManager->persist($this->commandRunSummary);
                $this->entityManager->flush();
                $this->outputAndLog('<fg=red>Got behind our own lock ttl, exiting.</fg=red> Handled ' . $this->commandRunSummary->getNumberOfHandledElements() . ' elements.');
                if (!$this->isTestEnvironment) {
                    exit(Command::FAILURE);
                }
            }

            try {
                if ($this->lock !== null) {
                    $this->lock->refresh($this->lockTtl);
                }
            } catch (Throwable) {
                $this->commandRunSummary->setFinishedDueToFailedToUpdateLock(true);
                $this->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
                $this->entityManager->persist($this->commandRunSummary);
                $this->entityManager->flush();
                $this->outputAndLog('<fg=red>Could not update lock, exiting.</fg=red> Handled ' . $this->commandRunSummary->getNumberOfHandledElements() . ' elements.');
                if (!$this->isTestEnvironment) {
                    exit(Command::FAILURE);
                }
            }
        }

        $allocatedMemory = (int)round(memory_get_usage(true) / 1024 / 1024);

        if ($allocatedMemory > $this->commandRunSummary->getMaxAllocatedMemory()) {
            $this->commandRunSummary->setMaxAllocatedMemory($allocatedMemory);
        }

        $expectedNumberOfElementsTextblock = '';
        if (!is_null($this->getExpectedNumberOfElementsToHandle())) {
            $expectedNumberOfElementsTextblock = " <fg=green>of</fg=green> <fg=yellow>{$this->getExpectedNumberOfElementsToHandle()}</fg=yellow>";
        }

        $this->outputAndLog(
            '<fg=green>Handled </fg=green><fg=yellow>' . $this->getNumberOfHandledElements() . '</fg=yellow>' . $expectedNumberOfElementsTextblock . ' <fg=green>elements so far. Current memory allocated: </fg=green><fg=yellow>' . $allocatedMemory . '</fg=yellow> <fg=green>MiB, maximum so far was </fg=green><fg=yellow>' . $this->commandRunSummary->getMaxAllocatedMemory() . '</fg=yellow> <fg=green>MiB, limit is</fg=green> <fg=yellow>' . ini_get('memory_limit') . '</fg=yellow><fg=green>.</fg=green>'
        );

        if (!is_null($this->getExpectedNumberOfElementsToHandle())
            && $this->getExpectedNumberOfElementsToHandle() > 0
            && $this->getNumberOfHandledElements()          > 0
        ) {
            $now                        = time();
            $duration                   = $now - $this->finishedStartingAt;
            $durationPerElement         = $duration / $this->getNumberOfHandledElements();
            $estimatedRemainingDuration = $durationPerElement * ($this->getExpectedNumberOfElementsToHandle() - $this->getNumberOfHandledElements());
            $estimatedFinishTime        = DateAndTimeService::getDateTimeImmutable()
                                                     ->modify('+' . (int)$estimatedRemainingDuration . ' seconds');
            $durationPerElement         = round($durationPerElement, 2);
            $estimatedRemainingDuration = round($estimatedRemainingDuration, 2);
            $this->outputAndLog(
                "<fg=green>By now, running for </fg=green><fg=yellow>$duration</fg=yellow><fg=green> seconds. Average time per element is </fg=green><fg=yellow>$durationPerElement</fg=yellow><fg=green> seconds, expected remaining time is </fg=green><fg=yellow>$estimatedRemainingDuration</fg=yellow><fg=green> seconds, which is at </fg=green><fg=yellow>{$estimatedFinishTime->format(Format::ISO8601->value)} UTC</fg=yellow><fg=green>.</fg=green>"
            );
        }

        $this->entityManager->persist($this->commandRunSummary);
        $this->entityManager->flush();

        gc_collect_cycles();
        gc_mem_caches();

        return true;
    }

    /**
     * @throws Exception
     */
    final protected function onFinish(bool $wasSuccessful = true): void
    {
        $this->entityManager->flush();
        if (!$this->isTestEnvironment && $this->lock !== null) {
            try {
                $this->lock->release();
            } catch (Throwable $t) {
                $this->outputAndLog("<fg=yellow>Could not release lock in onFinish: '{$t->getMessage()}'.</fg=yellow>");
            }
        }
        $allocatedMemory = (int)round(memory_get_usage(true) / 1024 / 1024);
        if ($allocatedMemory > $this->commandRunSummary->getMaxAllocatedMemory()) {
            $this->commandRunSummary->setMaxAllocatedMemory($allocatedMemory);
        }
        $this->commandRunSummary->setFinishedNormally(true);
        $this->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
        $this->entityManager->persist($this->commandRunSummary);
        $this->entityManager->flush();
        if ($wasSuccessful) {
            $this->outputAndLog(
                '<fg=green>All done, exiting.</fg=green> Handled ' . $this->commandRunSummary->getNumberOfHandledElements() . ' elements.'
            );
        } else {
            $this->outputAndLog(
                '<fg=yellow>Finished unsuccessfully, exiting.</fg=yellow> Handled ' . $this->commandRunSummary->getNumberOfHandledElements() . ' elements.'
            );
        }
        $this->unexpectedShutdown = false;
        if (!$this->isTestEnvironment) {
            exit($wasSuccessful ? Command::SUCCESS : Command::FAILURE);
        }
    }

    final public static function handleSig(int   $signo,
        mixed $signinfo
    ): never {
        echo 'Received OS signal ' . $signo . ' (' . json_encode($signinfo) . '), exiting. The shutdown handler should now be called before we exit for good.';
        echo "\n";
        exit(Command::SUCCESS);
    }

    /**
     * @throws Exception
     */
    final protected function checkAndHandleRolloutSignal(): void
    {
        if ($this->ignoreRolloutSignal === true) {
            return;
        }
        if ($this->rolloutService->rolloutIsInProgress()) {
            $this->entityManager->flush();
            if ($this->lock !== null) {
                try {
                    $this->lock->release();
                } catch (Throwable $t) {
                    $this->outputAndLog("<fg=yellow>Could not release log in exitIfRolloutInProgress: '{$t->getMessage()}'.</fg=yellow>");
                }
            }
            $this->commandRunSummary->setFinishedDueToRolloutSignal(true);
            $this->commandRunSummary->setFinishedAt(DateAndTimeService::getDateTimeImmutable());
            $this->entityManager->persist($this->commandRunSummary);
            $this->entityManager->flush();
            $this->outputAndLog(
                '<fg=yellow>Exiting because rollout is in progress.</fg=yellow> Handled ' . $this->commandRunSummary->getNumberOfHandledElements() . ' elements.',
            );
            $this->unexpectedShutdown = false;
            if (!$this->isTestEnvironment) {
                exit(Command::SUCCESS);
            }
        }
    }
}
