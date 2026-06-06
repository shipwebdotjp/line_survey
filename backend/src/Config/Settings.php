<?php

namespace App\Config;

class Settings
{
    private array $settings;

    public function __construct()
    {
        $this->settings = [
            'app' => [
                'name' => 'Survey App',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            ],
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_NAME'] ?? '',
                'username' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? '',
            ],
            'error' => [
                'display_error_details' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                'log_errors' => true,
                'log_error_details' => true,
            ],
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->settings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
