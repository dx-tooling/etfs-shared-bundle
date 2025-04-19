<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Unit\DateAndTime\Service;

use DateTimeImmutable;
use DateTimeZone;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum\Timezone;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

class DateAndTimeServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetDateTimeImmutable(): void
    {
        $mockClock = new MockClock();
        Clock::set($mockClock);

        $this->assertSame(
            'UTC',
            DateAndTimeService::getDateTimeImmutable()->getTimezone()->getName()
        );

        $this->assertSame(
            'Europe/Berlin',
            DateAndTimeService::getDateTimeImmutable('now', Timezone::EuropeBerlin)->getTimezone()->getName()
        );

        $now = DateAndTimeService::getDateTimeImmutable();

        $mockClock->sleep(60);

        $now1MinuteLater = DateAndTimeService::getDateTimeImmutable();

        $this->assertSame(
            $now->getTimestamp() + 60,
            $now1MinuteLater->getTimestamp(),
            'The time is not one minute later as expected.'
        );

        $today = DateAndTimeService::getDateTimeImmutable('today');

        $this->assertSame(
            (new DateTimeImmutable('today', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'),
            $today->format('Y-m-d H:i:s.u'),
            'The $today variable does not represent the current date at 00:00:00.00000 as expected.'
        );
    }

    /**
     * @throws Exception
     */
    public function testWithDateOnly(): void
    {
        $initialDate = DateAndTimeService::getDateTimeImmutable(
            '2021-02-03'
        );

        $lastDayOfThisMonth = $initialDate
            ->modify('last day of this month');

        $this->assertNotSame(
            '2021-02-28 00:00:00',
            $lastDayOfThisMonth->format('Y-m-d H:i:s'),
            'The last day of this month is not calculated correctly.'
        );

        $this->assertSame(
            '2021-02-28 00:00:00',
            $lastDayOfThisMonth->modify('midnight')->format('Y-m-d H:i:s'),
            'The last day of this month is not calculated correctly.'
        );
    }
}
