<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Commandline\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'command_run_summaries')]
#[ORM\Index(
    name: 'command_name_started_at_idx',
    columns: ['command_name', 'started_at']
)]
class CommandRunSummary
{
    public function __construct(
        string            $commandName,
        string            $arguments,
        string            $options,
        DateTimeImmutable $startedAt
    ) {
        $this->commandName = $commandName;
        $this->arguments   = $arguments;
        $this->options     = $options;
        $this->hostname    = (string)gethostname();
        $this->envvars     = (string)json_encode(getenv());
        $this->startedAt   = $startedAt;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: Types::GUID)]
    private ?string $id = null;

    public function getId(): string
    {
        if ($this->id === null) {
            throw new RuntimeException('ID not yet initialized');
        }

        return $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $commandName;

    public function setCommandName(string $commandName): void
    {
        $this->commandName = $commandName;
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }

    #[ORM\Column(type: Types::STRING, length: 1024)]
    private string $arguments;

    public function setArguments(string $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): string
    {
        return $this->arguments;
    }

    #[ORM\Column(type: Types::STRING, length: 1024)]
    private string $options;

    public function setOptions(string $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): string
    {
        return $this->options;
    }

    #[ORM\Column(type: Types::STRING, length: 1024)]
    private string $hostname;

    public function getHostname(): string
    {
        return $this->hostname;
    }

    #[ORM\Column(type: Types::STRING, length: 8192)]
    private string $envvars;

    public function getEnvvars(): string
    {
        return $this->envvars;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $startedAt;

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $finishedDueToNoInitialLock = false;

    public function isFinishedDueToNoInitialLock(): bool
    {
        return $this->finishedDueToNoInitialLock;
    }

    public function setFinishedDueToNoInitialLock(bool $finishedDueToNoInitialLock): void
    {
        $this->finishedDueToNoInitialLock = $finishedDueToNoInitialLock;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $finishedDueToGotBehindLock = false;

    public function isFinishedDueToGotBehindLock(): bool
    {
        return $this->finishedDueToGotBehindLock;
    }

    public function setFinishedDueToGotBehindLock(bool $finishedDueToGotBehindLock): void
    {
        $this->finishedDueToGotBehindLock = $finishedDueToGotBehindLock;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $finishedDueToFailedToUpdateLock = false;

    public function isFinishedDueToFailedToUpdateLock(): bool
    {
        return $this->finishedDueToFailedToUpdateLock;
    }

    public function setFinishedDueToFailedToUpdateLock(bool $finishedDueToFailedToUpdateLock): void
    {
        $this->finishedDueToFailedToUpdateLock = $finishedDueToFailedToUpdateLock;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $finishedDueToRolloutSignal = false;

    public function isFinishedDueToRolloutSignal(): bool
    {
        return $this->finishedDueToRolloutSignal;
    }

    public function setFinishedDueToRolloutSignal(bool $finishedDueToRolloutSignal): void
    {
        $this->finishedDueToRolloutSignal = $finishedDueToRolloutSignal;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $finishedNormally = false;

    public function isFinishedNormally(): bool
    {
        return $this->finishedNormally;
    }

    public function setFinishedNormally(bool $finishedNormally): void
    {
        $this->finishedNormally = $finishedNormally;
    }

    #[ORM\Column(type: Types::INTEGER)]
    private int $numberOfHandledElements = 0;

    public function getNumberOfHandledElements(): int
    {
        return $this->numberOfHandledElements;
    }

    public function setNumberOfHandledElements(int $numberOfHandledElements): void
    {
        $this->numberOfHandledElements = $numberOfHandledElements;
    }

    private ?int $expectedNumberOfElementsToHandle = null;

    public function getExpectedNumberOfElementsToHandle(): ?int
    {
        return $this->expectedNumberOfElementsToHandle;
    }

    public function setExpectedNumberOfElementsToHandle(?int $expectedNumberOfElementsToHandle): void
    {
        $this->expectedNumberOfElementsToHandle = $expectedNumberOfElementsToHandle;
    }

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxAllocatedMemory = 0;

    public function getMaxAllocatedMemory(): int
    {
        return $this->maxAllocatedMemory;
    }

    public function setMaxAllocatedMemory(int $maxAllocatedMemory): void
    {
        $this->maxAllocatedMemory = $maxAllocatedMemory;
    }
}
