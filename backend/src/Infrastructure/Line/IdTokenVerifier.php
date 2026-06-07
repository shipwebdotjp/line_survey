<?php

declare(strict_types=1);

namespace App\Infrastructure\Line;

use App\Config\Settings;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final class IdTokenVerifier
{
    private const CERTS_URL = 'https://api.line.me/oauth2/v2.1/certs';
    private const ISSUER = 'https://access.line.me';
    private const CACHE_KEY = 'line_jwks';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private Settings $settings,
        private CacheInterface $cache
    ) {
    }

    /**
     * Verify the LINE ID Token and return the decoded claims.
     *
     * @param string $idToken
     * @return array{sub: string, name: string, picture?: string, email?: string}
     * @throws RuntimeException
     */
    public function verify(string $idToken): array
    {
        $channelId = $this->settings->get('line.channel_id');
        if (empty($channelId)) {
            throw new RuntimeException('LINE Channel ID is not configured.');
        }

        try {
            $jwks = $this->fetchJwks();
            $keys = JWK::parseKeySet($jwks);

            $decoded = JWT::decode($idToken, $keys);
            $claims = (array)$decoded;

            // Validate claims
            if (($claims['iss'] ?? '') !== self::ISSUER) {
                throw new RuntimeException('Invalid issuer.');
            }
            if (($claims['aud'] ?? '') !== $channelId) {
                throw new RuntimeException('Invalid audience.');
            }

            return [
                'sub' => $claims['sub'],
                'name' => $claims['name'] ?? '',
                'picture' => $claims['picture'] ?? null,
                'email' => $claims['email'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('ID Token verification failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function fetchJwks(): array
    {
        $cached = $this->cache->get(self::CACHE_KEY);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 seconds
            ]
        ]);

        $json = @file_get_contents(self::CERTS_URL, false, $ctx);
        if ($json === false) {
            throw new RuntimeException('Failed to fetch LINE public keys (timeout or network error).');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JWKS response from LINE.');
        }

        $this->cache->set(self::CACHE_KEY, $data, self::CACHE_TTL);

        return $data;
    }
}
