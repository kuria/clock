<?php declare(strict_types=1);

namespace Kuria\Clock;

/**
 * Provides access to current date and time
 */
abstract class Clock
{
    /** @var int|float|null */
    private static $now;

    /**
     * Override the current time
     *
     * - this change only affects methods of this class
     * - this method is intended to be used only for testing purposes
     * - the new time can include microseconds
     *
     * @see Clock::resume()
     *
     * @param \DateTimeInterface|int|float $now UNIX timestamp or \DateTimeInterface instance
     * @throws \InvalidArgumentException if $now does not have a valid type
     */
    static function override($now): void
    {
        if ($now instanceof \DateTimeInterface) {
            $now = $now->getTimestamp() + ($now->format('u') / 1e6); // extract timestamp with microseconds
        } elseif (!is_int($now) && !is_float($now)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected int, float or an instance of DateTimeInterface, but got %s',
                is_object($now) ? get_class($now) : gettype($now)
            ));
        }

        self::$now = $now;
    }

    /**
     * See if the current time is currently overridden
     *
     * @see Clock::override()
     */
    static function isOverridden(): bool
    {
        return self::$now !== null;
    }

    /**
     * Resume normal operation after the time has been overridden
     *
     * If the time is not currently overridden, this method does nothing.
     *
     * @see Clock::override()
     */
    static function resume(): void
    {
        self::$now = null;
    }

    /**
     * Get the current UNIX timestamp
     */
    static function time(): int
    {
        if (self::$now !== null) {
            return (int) self::$now;
        }

        return time();
    }

    /**
     * Get the current UNIX timestamp with microsecond precision
     */
    static function microtime(): float
    {
        if (self::$now !== null) {
            return (float) self::$now;
        }

        return microtime(true);
    }

    /**
     * Get the current date-time
     *
     * If no time zone is specified, then the default time zone will be used.
     *
     * The default time zone is specified by the date.timezone INI setting or the date_default_timezone_set() function.
     */
    static function dateTime(?\DateTimeZone $timezone = null): \DateTime
    {
        if (self::$now !== null) {
            $now = \DateTime::createFromFormat('U.u', sprintf('%.6F', self::$now));
            $now->setTimezone($timezone ?? new \DateTimeZone(date_default_timezone_get()));

            return $now;
        }

        return new \DateTime('now', $timezone);
    }

    /**
     * Get the current date-time as an immutable instance
     *
     * If no time zone is specified, then the default time zone will be used.
     *
     * The default time zone is specified by the date.timezone INI setting or the date_default_timezone_set() function.
     */
    static function dateTimeImmutable(?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        if (self::$now !== null) {
            $now = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', self::$now));
            $now = $now->setTimezone($timezone ?? new \DateTimeZone(date_default_timezone_get()));

            return $now;
        }

        return new \DateTimeImmutable('now', $timezone);
    }
}
