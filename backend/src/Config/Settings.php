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
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo',
                'origin_url' => $_ENV['APP_ORIGIN_URL'] ?? null,
                'public_url' => $_ENV['APP_PUBLIC_URL'] ?? null,
            ],
            'db' => [
                'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_NAME'] ?? '',
                'username' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            ],
            'error' => [
                'display_error_details' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
                'log_errors' => true,
                'log_error_details' => true,
            ],
            'auth' => [
                'admin_user' => $_ENV['ADMIN_USER'] ?? 'admin',
                'admin_pass' => $_ENV['ADMIN_PASS'] ?? 'password',
            ],
            'line' => [
                'channel_id' => $_ENV['LINE_CHANNEL_ID'] ?? '',
            ],
            'mail' => [
                'mailer' => $_ENV['MAIL_MAILER'] ?? 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
                'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? '',
                'resend_api_key' => $_ENV['RESEND_API_KEY'] ?? '',
                'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'onboarding@resend.dev',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Survey App',
                'admin_address' => $_ENV['ADMIN_MAIL'] ?? '',
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
