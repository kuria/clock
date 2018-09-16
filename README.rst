Clock
#####

Provides access to current date and time that can be mocked for testing purposes.

.. image:: https://travis-ci.com/kuria/clock.svg?branch=master
  :target: https://travis-ci.com/kuria/clock

.. contents::


Requirements
************

- PHP 7.1+


Usage
*****

The ``Kuria\Clock\Clock`` class provides access to current date and time.


Getting current time
====================

- ``Clock::time(): int``

  - get the current UNIX timestamp

- ``Clock::microtime(): float``

  - get the current UNIX timestamp with microsecond precision

- ``Clock::dateTime(?\DateTimeZone $timezone = null): \DateTime``

  - get the current date-time
  - uses default time zone if not specified

- ``Clock::dateTimeImmutable(?\DateTimeZone $timezone = null): \DateTimeImmutable``

  - get the current date-time as an immutable instance
  - uses default time zone if not specified


Overriding current time
=======================

- ``Clock::override($now): void``

  - override the current time
  - ``$now`` can be an UNIX timestamp or an instance of ``DateTimeInterface``
  - ``$now`` can include microseconds
  - this change only affects methods of the ``Clock`` class

- ``Clock::isOverridden(): bool``

  - see if the current time is currently overridden

- ``Clock::resume(): void``

  - resume normal operation after the time has been overridden
  - if the time is not currently overridden, this method does nothing

.. NOTE::

   Time overriding is intended only for testing purposes.
