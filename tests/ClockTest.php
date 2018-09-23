<?php declare(strict_types=1);

namespace Kuria\Clock;

use Kuria\DevMeta\Test;

class ClockTest extends Test
{
    private const TZ_DEFAULT = 'America/New_York';
    private const TZ_OTHER = 'Asia/Tokyo';

    /** @var string */
    private $originalDefaultTz;

    protected function setUp()
    {
        Clock::resume();
        $this->originalDefaultTz = date_default_timezone_get();
        date_default_timezone_set(self::TZ_DEFAULT);
    }

    protected function tearDown()
    {
        Clock::resume();
        date_default_timezone_set($this->originalDefaultTz);
    }

    function testShouldGetCurrentTime()
    {
        $this->assertFalse(Clock::isOverridden());
        $this->assertRoughlyCurrentTime(Clock::time());
        $this->assertRoughlyCurrentTime(Clock::microtime());
        $this->assertRoughlyCurrentTime(Clock::dateTime()->getTimestamp());
        $this->assertRoughlyCurrentTime(Clock::dateTimeImmutable()->getTimestamp());
    }

    /**
     * @dataProvider provideDateTimeMethods
     */
    function testShouldGetCurrentDateTimeWithDefaultTimeZone(string $method)
    {
        $this->assertFalse(Clock::isOverridden());

        /** @var \DateTimeInterface $dateTime */
        $dateTime = Clock::{$method}();

        $this->assertRoughlyCurrentTime($dateTime->getTimestamp());
        $this->assertSame(self::TZ_DEFAULT, $dateTime->getTimezone()->getName());
    }

    /**
     * @dataProvider provideDateTimeMethods
     */
    function testShouldGetCurrentDateTimeWithCustomTimeZone(string $method)
    {
        $this->assertFalse(Clock::isOverridden());

        /** @var \DateTimeInterface $dateTime */
        $dateTime = Clock::{$method}(new \DateTimeZone(self::TZ_OTHER));

        $this->assertRoughlyCurrentTime($dateTime->getTimestamp());
        $this->assertSame(self::TZ_OTHER, $dateTime->getTimezone()->getName());
    }

    function provideDateTimeMethods()
    {
        return [
            ['dateTime'],
            ['dateTimeImmutable'],
        ];
    }

    /**
     * @dataProvider provideCurrentTimeOverrides
     */
    function testShouldOverrideCurrentTime($now, int $expectedTime, float $expectedMicrotime, string $expectedDateTime)
    {
        $this->assertFalse(Clock::isOverridden());

        Clock::override($now);

        $this->assertTrue(Clock::isOverridden());

        $dateTime = Clock::dateTime();
        $dateTimeImmutable = Clock::dateTimeImmutable();
        $otherTz = new \DateTimeZone(self::TZ_OTHER);;
        $dateTimeOtherTz = Clock::dateTime($otherTz);
        $dateTimeImmutableOtherTz = Clock::dateTimeImmutable($otherTz);

        $this->assertSame($expectedTime, Clock::time(), 'Clock::time()');
        $this->assertEquals($expectedMicrotime, Clock::microtime(), 'Clock::microtime() ', 0.001, 0);
        $this->assertSame($expectedDateTime, $dateTime->format('Y-m-d H:i:s.u e'), 'Clock::dateTime()');
        $this->assertSame($expectedDateTime, $dateTimeImmutable->format('Y-m-d H:i:s.u e'), 'Clock::dateTimeImmutable()');

        $this->assertNotSame(
            $dateTime,
            Clock::dateTime(),
            'Clock::dateTime() should return unique instances'
        );

        $this->assertNotSame(
            $dateTimeImmutable,
            Clock::dateTimeImmutable(),
            'Clock::dateTimeImmutable() should return unique instances'
        );

        $this->assertSame(
            self::TZ_OTHER,
            $dateTimeOtherTz->getTimezone()->getName(),
            'expected Clock::dateTime(timezone) to propagate that timezone'
        );

        $this->assertSame(
            self::TZ_OTHER,
            $dateTimeImmutableOtherTz->getTimezone()->getName(),
            'expected Clock::dateTimeImmutable(timezone) to propagate that timezone'
        );

        $this->assertSame($expectedTime, $dateTimeOtherTz->getTimestamp(), 'Clock::dateTime(timezone)');
        $this->assertSame($expectedTime, $dateTimeImmutableOtherTz->getTimestamp(), 'Clock::dateTimeImmutable(timezone)');

        $this->assertNotSame(
            $dateTimeOtherTz,
            Clock::dateTime($otherTz),
            'dateTime(timezone) should return unique instances'
        );

        $this->assertNotSame(
            $dateTimeImmutableOtherTz,
            Clock::dateTimeImmutable($otherTz),
            'dateTimeImmutable(timezone) should return unique instances'
        );
    }

    function provideCurrentTimeOverrides()
    {
        return [
            // now, expectedTime, expectedMicrotime, expectedDateTime
            'int timestamp' => [
                1537126680,
                1537126680,
                1537126680.0,
                '2018-09-16 15:38:00.000000 America/New_York',
            ],

            'float timestamp' => [
                1537126289.123456,
                1537126289,
                1537126289.123456,
                '2018-09-16 15:31:29.123456 America/New_York',
            ],

            'datetime' => [
                new \DateTime('@1537139888'),
                1537139888,
                1537139888.0,
                '2018-09-16 19:18:08.000000 America/New_York',
            ],

            'datetime with microseconds' => [
                \DateTime::createFromFormat('U.u', '1537139888.123456'),
                1537139888,
                1537139888.123456,
                '2018-09-16 19:18:08.123456 America/New_York',
            ],

            'datetime with different time zone' => [
                new \DateTime('2018-09-16 12:24:48', new \DateTimeZone(self::TZ_OTHER)),
                1537068288,
                1537068288.0,
                '2018-09-15 23:24:48.000000 America/New_York',
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidOverrides
     */
    function testShouldThrowExceptionWhenOverridingCurrentTimeWithInvalidValue($invalidNow, string $expectedMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        Clock::override($invalidNow);
    }

    function provideInvalidOverrides()
    {
        return [
            // invalidNow, expectedMessage
            ['123456', 'Expected int, float or an instance of DateTimeInterface, but got string'],
            [new \stdClass(), 'Expected int, float or an instance of DateTimeInterface, but got stdClass'],
        ];
    }

    private function assertRoughlyCurrentTime($timestamp): void
    {
        /*
         * Assume that there cannot be more than 70 minutes between
         * the determination of current time and this assertion.
         *
         * The tolerance is very generous and should account for
         * any lag or DST changes. In most cases the delay should
         * be less than 1 second.
         */
        $tolerance = 4200;

        $this->assertLessThan(
            $tolerance,
            abs(microtime(true) - $timestamp),
            sprintf(
                'expected timestamp %F to roughly match the current time (tolerance = %d)',
                $timestamp,
                $tolerance
            )
        );
    }
}
