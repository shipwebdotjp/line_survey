<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Config\Settings;
use App\Infrastructure\Support\DateTimeHelper;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use RuntimeException;
use InvalidArgumentException;

class MailService
{
    private string $mailer;
    private string $apiKey;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $smtpEncryption;
    private string $fromAddress;
    private string $fromName;
    private string $adminAddress;
    private string $appUrl;
    private ?PHPMailer $phpMailer = null;

    public function __construct(Settings $settings, ?PHPMailer $phpMailer = null)
    {
        $this->mailer = strtolower($settings->get('mail.mailer', 'smtp'));
        if (!in_array($this->mailer, ['smtp', 'resend'], true)) {
            throw new InvalidArgumentException("Invalid mailer configured: {$this->mailer}");
        }

        $this->apiKey = $settings->get('mail.resend_api_key', '');
        $this->smtpHost = $settings->get('mail.host', 'localhost');
        $this->smtpPort = (int) $settings->get('mail.port', 587);
        $this->smtpUsername = $settings->get('mail.username', '');
        $this->smtpPassword = $settings->get('mail.password', '');
        $this->smtpEncryption = $settings->get('mail.encryption', '');
        $this->fromAddress = $settings->get('mail.from_address', 'onboarding@resend.dev');
        $this->fromName = $settings->get('mail.from_name', 'Survey App');
        $this->adminAddress = trim((string) $settings->get('mail.admin_address', ''));
        $this->phpMailer = $phpMailer;

        $appUrl = $settings->get('app.url');
        if (empty($appUrl)) {
             throw new RuntimeException('APP_URL is not configured.');
        }
        $this->appUrl = rtrim($appUrl, '/');
    }

    /**
     * Send confirmation email.
     *
     * @param array $respondent
     * @param array $survey
     * @param array $response
     * @param bool $isUpdate
     * @return array{status: 'sent'|'skipped'|'failed', message: string, admin_result?: array{status: string, message: string}}
     */
    public function sendConfirmation(array $respondent, array $survey, array $response, bool $isUpdate = false): array
    {
        if (!($survey['send_confirmation_email'] ?? true)) {
            return ['status' => 'skipped', 'message' => 'Email sending is disabled for this survey.'];
        }

        if ($this->mailer === 'resend' && empty($this->apiKey)) {
            return ['status' => 'failed', 'message' => 'Resend API key is not configured.'];
        }

        $to = $respondent['email'];
        $subject = ($isUpdate ? '【回答修正控え】' : '【回答控え】') . $survey['title'];

        $body = $this->buildEmailBody($respondent, $survey, $response, $isUpdate);

        $primaryResult = $this->sendEmail($to, $subject, $body);
        if (($primaryResult['status'] ?? null) !== 'sent') {
            return $primaryResult;
        }

        $message = $primaryResult['message'] ?? 'Email sent successfully.';
        $result = [
            'status' => 'sent',
            'message' => $message,
        ];

        if ($this->shouldSendAdminCopy($to)) {
            $adminResult = $this->sendEmail($this->adminAddress, $subject, $body);
            $result['admin_result'] = $adminResult;

            if (($adminResult['status'] ?? null) !== 'sent') {
                $result['message'] = $message . ' Admin copy failed: ' . ($adminResult['message'] ?? 'Unknown error.');
            }
        }

        return $result;
    }

    private function shouldSendAdminCopy(string $primaryRecipient): bool
    {
        if ($this->adminAddress === '') {
            return false;
        }

        return strcasecmp(trim($primaryRecipient), $this->adminAddress) !== 0;
    }

    private function buildEmailBody(array $respondent, array $survey, array $response, bool $isUpdate = false): string
    {
        $name = $respondent['name'];
        $honorific = $respondent['honorific'] ?? 'さん';
        if (empty($honorific)) {
            $honorific = 'さん';
        }

        $displayName = $name . $honorific;

        $submittedAt = DateTimeHelper::parseTokyo($response['submitted_at']);
        $submittedAtStr = DateTimeHelper::formatTokyo($submittedAt);

        $lines = [];
        $lines[] = "{$displayName}";
        $lines[] = "";
        $lines[] = $isUpdate ? "アンケート回答の修正を受け付けました。" : "アンケートへのご回答ありがとうございます。";
        $lines[] = $isUpdate ? "修正後の内容は以下の通りです。" : "以下の内容で受け付けました。";
        $lines[] = "";
        $lines[] = "■アンケート名";
        $lines[] = $survey['title'];
        $lines[] = "";
        $lines[] = "■回答日時";
        $lines[] = $submittedAtStr;
        $lines[] = "";

        if ($survey['include_answers_in_email'] ?? true) {
            $lines[] = "■回答内容";
            $answers = $response['answer_json'] ?? [];
            $questions = $this->getAllQuestions($survey['questions_json'] ?? []);

            // Simple label mapping
            $labelMap = [];
            foreach ($questions as $q) {
                if (isset($q['name'])) {
                    $labelMap[$q['name']] = $q['title'] ?? $q['name'];
                }
            }

            foreach ($answers as $key => $value) {
                $label = $labelMap[$key] ?? $key;
                if (is_array($value)) {
                    $valueStr = implode(', ', $value);
                } else {
                    $valueStr = (string)$value;
                }
                $lines[] = "{$label}: {$valueStr}";
            }
            $lines[] = "";
        }

        if ($survey['allow_edit'] ?? false) {
            $editUrl = "{$this->appUrl}/s/{$survey['public_id']}/r/{$response['edit_token']}/edit";
            $lines[] = "■回答の編集";
            $lines[] = "以下のURLから回答を修正することが可能です。";
            $lines[] = $editUrl;
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    private function getAllQuestions(array $questionsJson): array
    {
        $elements = [];
        if (isset($questionsJson['elements']) && is_array($questionsJson['elements'])) {
            $elements = $questionsJson['elements'];
        } elseif (isset($questionsJson['pages']) && is_array($questionsJson['pages'])) {
            foreach ($questionsJson['pages'] as $page) {
                if (isset($page['elements']) && is_array($page['elements'])) {
                    $elements = array_merge($elements, $page['elements']);
                }
            }
        }

        $questions = [];
        $this->extractElements($elements, $questions);
        return $questions;
    }

    private function extractElements(array $elements, array &$questions): void
    {
        foreach ($elements as $element) {
            if (isset($element['type']) && ($element['type'] === 'panel' || $element['type'] === 'paneldynamic') && isset($element['elements']) && is_array($element['elements'])) {
                $this->extractElements($element['elements'], $questions);
            } else {
                $questions[] = $element;
            }
        }
    }

    private function sendEmail(string $to, string $subject, string $body): array
    {
        if ($this->mailer === 'resend') {
            return $this->sendViaResend($to, $subject, $body);
        }

        if ($this->mailer === 'smtp') {
            return $this->sendViaSmtp($to, $subject, $body);
        }

        throw new RuntimeException("Unsupported mailer: {$this->mailer}");
    }

    private function sendViaResend(string $to, string $subject, string $body): array
    {
        $url = 'https://api.resend.com/emails';

        $data = [
            'from' => "{$this->fromName} <{$this->fromAddress}>",
            'to' => [$to],
            'subject' => $subject,
            'text' => $body,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            return ['status' => 'failed', 'message' => 'Curl error: ' . $error];
        }

        $response = json_decode($result, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => 'sent', 'message' => 'Email sent successfully. ID: ' . ($response['id'] ?? 'unknown')];
        }

        return [
            'status' => 'failed',
            'message' => 'Resend API error: ' . ($response['message'] ?? $result)
        ];
    }

    private function sendViaSmtp(string $to, string $subject, string $body): array
    {
        $mail = $this->phpMailer ?? new PHPMailer(true);

        try {
            $mail->clearAllRecipients();

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';

            if (!empty($this->smtpUsername)) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->smtpUsername;
                $mail->Password = $this->smtpPassword;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($this->smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Recipients
            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return ['status' => 'sent', 'message' => 'Email sent successfully via SMTP.'];
        } catch (PHPMailerException $e) {
            return ['status' => 'failed', 'message' => 'SMTP error: ' . $mail->ErrorInfo];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
