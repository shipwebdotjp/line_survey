<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mail;

use App\Config\Settings;
use App\Infrastructure\Mail\MailService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MailServiceTest extends TestCase
{
    private function createSettings(array $overrides = []): Settings
    {
        $_ENV['APP_URL'] = 'http://test.example.com';
        foreach ($overrides as $key => $value) {
            $_ENV[$key] = $value;
        }
        return new Settings();
    }

    protected function tearDown(): void
    {
        unset($_ENV['MAIL_MAILER']);
        unset($_ENV['RESEND_API_KEY']);
        unset($_ENV['MAIL_FROM_ADDRESS']);
        unset($_ENV['MAIL_FROM_NAME']);
        unset($_ENV['MAIL_HOST']);
        unset($_ENV['MAIL_PORT']);
        unset($_ENV['MAIL_USERNAME']);
        unset($_ENV['MAIL_PASSWORD']);
        unset($_ENV['MAIL_ENCRYPTION']);
        unset($_ENV['APP_URL']);
    }

    public function test_sendConfirmation_skips_when_disabled(): void
    {
        $settings = $this->createSettings();
        $mailService = new MailService($settings);

        $respondent = ['email' => 'test@example.com'];
        $survey = ['send_confirmation_email' => false];
        $response = [];

        $result = $mailService->sendConfirmation($respondent, $survey, $response);

        $this->assertEquals('skipped', $result['status']);
        $this->assertStringContainsString('disabled', $result['message']);
    }

    public function test_sendConfirmation_fails_when_resend_api_key_missing(): void
    {
        $settings = $this->createSettings(['MAIL_MAILER' => 'resend', 'RESEND_API_KEY' => '']);
        $mailService = new MailService($settings);

        $respondent = ['email' => 'test@example.com'];
        $survey = ['send_confirmation_email' => true, 'title' => 'Test Survey'];
        $response = [];

        $result = $mailService->sendConfirmation($respondent, $survey, $response);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Resend API key is not configured', $result['message']);
    }

    public function test_constructor_throws_when_app_url_missing(): void
    {
        // Settings has a default http://localhost if APP_URL is not set in $_ENV
        // To trigger the exception, we need to bypass $_ENV or make sure Settings returns empty for app.url
        // Since Settings reads $_ENV in constructor, we set it to empty string.
        $oldAppUrl = $_ENV['APP_URL'] ?? null;
        $_ENV['APP_URL'] = '';
        try {
            $settings = new Settings();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('APP_URL is not configured.');

            new MailService($settings);
        } finally {
            if ($oldAppUrl !== null) {
                $_ENV['APP_URL'] = $oldAppUrl;
            } else {
                unset($_ENV['APP_URL']);
            }
        }
    }
}
