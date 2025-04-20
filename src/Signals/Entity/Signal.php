<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Signals\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Exception;
use ValueError;

#[ORM\Entity]
#[ORM\Table(name: 'signals')]
class Signal
{
    /**
     * @throws Exception
     */
    public function __construct(string $name)
    {
        if (mb_strlen($name) > 64) {
            throw new ValueError('Signal name must not be longer than 64 characters.');
        }

        if (mb_strlen($name) < 1) {
            throw new ValueError('Signal name must not be empty.');
        }

        $this->name      = $name;
        $this->createdAt = DateAndTimeService::getDateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false
    )]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
