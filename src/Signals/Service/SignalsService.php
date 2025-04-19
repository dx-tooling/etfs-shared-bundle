<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Signals\Service;

use Doctrine\ORM\EntityManagerInterface;
use EnterpriseToolingForSymfony\SharedBundle\Signals\Entity\Signal;
use Exception;
use Throwable;

readonly class SignalsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function setSignal(string $name): void
    {
        if ($this->signalIsSet($name)) {
            return;
        }

        $signal = new Signal($name);
        $this->entityManager->persist($signal);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function signalIsSet(string $name): bool
    {
        try {
            return $this->entityManager->find(Signal::class, $name) !== null;
        } catch (Throwable $t) {
            throw new Exception($t->getMessage(), $t->getCode(), $t);
        }
    }

    public function removeSignal(string $name): void
    {
        $signal = $this->entityManager->getRepository(Signal::class)->find($name);

        if ($signal === null) {
            return;
        }

        $this->entityManager->remove($signal);
        $this->entityManager->flush();
    }
}
