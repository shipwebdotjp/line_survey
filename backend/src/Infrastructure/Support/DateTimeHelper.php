<?php

namespace App\Infrastructure\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class DateTimeHelper
{
    private const TIMEZONE = 'Asia/Tokyo';

    public static function nowTokyo(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone());
    }

    public static function toTokyo(DateTimeInterface $dateTime): DateTimeImmutable
    {
        $immutable = $dateTime instanceof DateTimeImmutable
            ? $dateTime
            : DateTimeImmutable::createFromInterface($dateTime);

        return $immutable->setTimezone(self::timezone());
    }

    public static function formatTokyo(?DateTimeInterface $dateTime, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return self::toTokyo($dateTime)->format($format);
    }

    public static function parseTokyo(string $value, string $format = 'Y-m-d H:i:s'): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value, self::timezone());

        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf('Invalid date-time value: %s', $value));
        }

        return $parsed;
    }

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }
}
