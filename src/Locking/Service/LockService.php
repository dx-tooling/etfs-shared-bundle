<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Locking\Service;

use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Throwable;
use ValueError;

readonly class LockService
{
    public function __construct(
        private LockFactory     $factory,
        private LoggerInterface $logger
    ) {
    }

    public function acquireLock(
        string $resource,
        float  $ttl = 60.0
    ): ?SharedLockInterface {
        if (mb_strlen($resource) < 3) {
            throw new ValueError('Lock id must be at least 3 characters long.');
        }

        $lock = $this->factory->createLock($resource, $ttl, false);
        try {
            $acquired = $lock->acquire();
        } catch (Throwable) {
            try {
                $message = "Was asked to acquire lock $resource, but lock is already acquired and has a lifetime until "
                    . DateAndTimeService::formatFromModifier("+{$lock->getRemainingLifetime()} seconds")
                    . " UTC (which is in {$lock->getRemainingLifetime()} seconds).";
                $this->logger->debug($message);
            } catch (Throwable) {
                $this->logger->debug(
                    "Was asked to acquire lock $resource, but lock is already acquired and has a remaining lifetime of {$lock->getRemainingLifetime()} seconds."
                );
            }

            return null;
        }

        if (!$acquired) {
            return null;
        }

        return $lock;
    }

    public function getRemainingLifetime(string $resource): ?float
    {
        $lock = $this->factory->createLock($resource);
        if (!$lock->isAcquired()) {
            return null;
        }

        return $lock->getRemainingLifetime();
    }

    public function releaseLock(string $resource): void
    {
        $lock = $this->factory->createLock($resource);
        try {
            $lock->release();
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
        }
    }

    public function isLocked(string $resource): bool
    {
        $lock = $this->factory->createLock($resource);

        return $lock->isAcquired() && !$lock->isExpired();
    }
}
