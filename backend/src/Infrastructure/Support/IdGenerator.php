<?php

namespace App\Infrastructure\Support;

final class IdGenerator
{
    private const PUBLIC_ID_PREFIX = 'sv_';
    private const PUBLIC_ID_RANDOM_BYTES = 16;
    private const EDIT_TOKEN_RANDOM_BYTES = 32;

    public static function generatePublicId(): string
    {
        return self::PUBLIC_ID_PREFIX . self::encode(random_bytes(self::PUBLIC_ID_RANDOM_BYTES));
    }

    public static function generateEditToken(): string
    {
        return self::encode(random_bytes(self::EDIT_TOKEN_RANDOM_BYTES));
    }

    private static function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
