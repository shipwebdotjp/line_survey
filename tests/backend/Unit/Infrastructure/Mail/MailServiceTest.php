<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mail;

use App\Config\Settings;
use App\Infrastructure\Mail\MailService;
use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use InvalidArgumentException;

class MailServiceTest extends TestCase
{
    private array $envBackup = [];
    private array $envKeys = [
        'MAIL_MAILER',
        'RESEND_API_KEY',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'ADMIN_MAIL',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'APP_ORIGIN_URL',
        'APP_PUBLIC_URL'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ($this->envKeys as $key) {
            if (isset($_ENV[$key])) {
                $this->envBackup[$key] = $_ENV[$key];
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envKeys as $key) {
            if (array_key_exists($key, $this->envBackup)) {
                $_ENV[$key] = $this->envBackup[$key];
            } else {
                unset($_ENV[$key]);
            }
        }
        $this->envBackup = [];
        parent::tearDown();
    }

    private function createSettings(array $overrides = []): Settings
    {
        $_ENV['APP_PUBLIC_URL'] = 'http://test.example.com';
        foreach ($overrides as $key => $value) {
            $_ENV[$key] = $value;
        }
        return new Settings();
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

    public function test_constructor_throws_when_app_public_url_missing(): void
    {
        $_ENV['APP_PUBLIC_URL'] = '';
        $settings = new Settings();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_PUBLIC_URL is not configured.');

        new MailService($settings);
    }

    public function test_constructor_throws_on_invalid_mailer(): void
    {
        $settings = $this->createSettings(['MAIL_MAILER' => 'invalid']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mailer configured: invalid');

        new MailService($settings);
    }

    public function test_sendConfirmation_via_smtp_routing(): void
    {
        $settings = $this->createSettings([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.test.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'user',
            'MAIL_PASSWORD' => 'pass',
            'MAIL_ENCRYPTION' => 'tls'
        ]);

        $phpMailerMock = $this->createMock(PHPMailer::class);

        // PHPMailer properties are often public, but let's assume we can mock send()
        $phpMailerMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $mailService = new MailService($settings, $phpMailerMock);

        $respondent = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'honorific' => '様'
        ];
        $survey = [
            'send_confirmation_email' => true,
            'title' => 'Test Survey',
            'public_id' => 'survey123'
        ];
        $response = [
            'submitted_at' => '2023-01-01 12:00:00',
            'answer_json' => [],
            'edit_token' => 'token123'
        ];

        $result = $mailService->sendConfirmation($respondent, $survey, $response);

        $this->assertEquals('sent', $result['status']);
        $this->assertEquals('Email sent successfully via SMTP.', $result['message']);
    }

    public function test_sendConfirmation_via_smtp_sends_admin_copy_when_configured(): void
    {
        $settings = $this->createSettings([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.test.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'user',
            'MAIL_PASSWORD' => 'pass',
            'MAIL_ENCRYPTION' => 'tls',
            'ADMIN_MAIL' => 'admin@example.com',
        ]);

        $phpMailerMock = $this->createMock(PHPMailer::class);
        $sentRecipients = [];

        $phpMailerMock->expects($this->exactly(2))
            ->method('clearAllRecipients');

        $phpMailerMock->expects($this->exactly(2))
            ->method('addAddress')
            ->willReturnCallback(function (string $address) use (&$sentRecipients) {
                $sentRecipients[] = $address;
                return true;
            });

        $phpMailerMock->expects($this->exactly(2))
            ->method('send')
            ->willReturn(true);

        $mailService = new MailService($settings, $phpMailerMock);

        $respondent = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'honorific' => '様'
        ];
        $survey = [
            'send_confirmation_email' => true,
            'title' => 'Test Survey',
            'public_id' => 'survey123'
        ];
        $response = [
            'submitted_at' => '2023-01-01 12:00:00',
            'answer_json' => [],
            'edit_token' => 'token123'
        ];

        $result = $mailService->sendConfirmation($respondent, $survey, $response);

        $this->assertEquals('sent', $result['status']);
        $this->assertEquals('Email sent successfully via SMTP.', $result['message']);
        $this->assertSame(['test@example.com', 'admin@example.com'], $sentRecipients);
        $this->assertArrayHasKey('admin_result', $result);
        $this->assertEquals('sent', $result['admin_result']['status']);
    }

    public function test_sendConfirmation_reports_admin_copy_failure_without_failing_primary_send(): void
    {
        $settings = $this->createSettings([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.test.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'user',
            'MAIL_PASSWORD' => 'pass',
            'MAIL_ENCRYPTION' => 'tls',
            'ADMIN_MAIL' => 'admin@example.com',
        ]);

        $phpMailerMock = $this->createMock(PHPMailer::class);
        $sentRecipients = [];
        $sendCount = 0;

        $phpMailerMock->expects($this->exactly(2))
            ->method('clearAllRecipients');

        $phpMailerMock->expects($this->exactly(2))
            ->method('addAddress')
            ->willReturnCallback(function (string $address) use (&$sentRecipients) {
                $sentRecipients[] = $address;
                return true;
            });

        $phpMailerMock->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function () use (&$sendCount) {
                $sendCount++;
                if ($sendCount === 2) {
                    throw new \Exception('SMTP failure');
                }

                return true;
            });

        $mailService = new MailService($settings, $phpMailerMock);

        $respondent = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'honorific' => '様'
        ];
        $survey = [
            'send_confirmation_email' => true,
            'title' => 'Test Survey',
            'public_id' => 'survey123'
        ];
        $response = [
            'submitted_at' => '2023-01-01 12:00:00',
            'answer_json' => [],
            'edit_token' => 'token123'
        ];

        $result = $mailService->sendConfirmation($respondent, $survey, $response);

        $this->assertEquals('sent', $result['status']);
        $this->assertStringContainsString('Admin copy failed', $result['message']);
        $this->assertSame(['test@example.com', 'admin@example.com'], $sentRecipients);
        $this->assertArrayHasKey('admin_result', $result);
        $this->assertEquals('failed', $result['admin_result']['status']);
    }
}
