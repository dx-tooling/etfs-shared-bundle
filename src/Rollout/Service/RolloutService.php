<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Rollout\Service;

use EnterpriseToolingForSymfony\SharedBundle\Signals\Service\SignalsService;
use Exception;

/**
 * When a rollout starts, the rollout system signals the currently running application that it has started.
 * Critical parts of the application can then react to this event. For example, EnhancedCommands no longer launch,
 * in order to avoid being killed mid-job because the jobs server instance is shutdown by the rollout process.
 */
readonly class RolloutService
{
    private const SIGNAL_NAME = 'shared_rollout';

    public function __construct(
        private SignalsService $signalsService
    ) {
    }

    /** @throws Exception */
    public function setRolloutSignal(): void
    {
        $this->signalsService->setSignal(self::SIGNAL_NAME);
    }

    public function removeRolloutSignal(): void
    {
        $this->signalsService->removeSignal(self::SIGNAL_NAME);
    }

    public function rolloutIsInProgress(): bool
    {
        return $this->signalsService->signalIsSet(self::SIGNAL_NAME);
    }
}
