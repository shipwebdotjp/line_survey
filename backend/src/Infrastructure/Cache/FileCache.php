<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * A very simple file-based cache for things like JWKS.
 * In a real production environment, you might use Redis or Memcached.
 */
final class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../../var/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->getPath($key);
        if (!file_exists($path)) {
            return $default;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return $default;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['expires_at']) || !array_key_exists('value', $data)) {
            $this->delete($key);
            return $default;
        }

        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $expiresAt = null;
        if ($ttl instanceof \DateInterval) {
            $expiresAt = (new \DateTime())->add($ttl)->getTimestamp();
        } elseif (is_int($ttl)) {
            $expiresAt = time() + $ttl;
        }

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt
        ];

        return file_put_contents($this->getPath($key), json_encode($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $path = $this->getPath($key);
        if (!file_exists($path)) {
            return false;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['expires_at'])) {
            return false;
        }

        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    private function getPath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
