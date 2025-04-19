<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service;

use DateTimeImmutable;
use DateTimeZone;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Format;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Timezone;
use Exception;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockAwareTrait;

readonly class DateAndTimeService
{
    use ClockAwareTrait;

    /**
     * @throws Exception
     */
    public static function getDateTimeImmutable(
        string   $modifier = 'now',
        Timezone $timezone = Timezone::UTC
    ): DateTimeImmutable {
        $clock = Clock::get()->withTimeZone(new DateTimeZone($timezone->value));
        $now   = $clock->now();

        return $now->modify($modifier);
    }

    /**
     * @throws Exception
     */
    public static function formatFromModifier(
        string   $modifier = 'now',
        Format   $format = Format::ISO8601,
        Timezone $timezone = Timezone::UTC,
    ): string {
        return self::getDateTimeImmutable($modifier, $timezone)->format($format->value);
    }
}
