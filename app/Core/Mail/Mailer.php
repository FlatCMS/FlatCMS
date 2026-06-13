<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Mail;

use App\Core\FlatFile;
use App\Core\Security\SecretBox;

final class Mailer
{
    public function send(string $to, string $subject, string $textBody, array $options = []): bool
    {
        $to = trim($to);
        $subject = trim($subject);
        $htmlBody = trim((string) ($options['html_body'] ?? ''));
        $attachments = $this->normalizeAttachments($options['attachments'] ?? []);

        if (!$this->isSafeHeaderValue($to) || !$this->isSafeHeaderValue($subject)) {
            return false;
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $settings = FlatFile::settings();
        $transport = strtolower(trim((string) ($settings['mail_transport'] ?? env('MAIL_TRANSPORT', 'mail'))));
        if (!in_array($transport, ['mail', 'smtp'], true)) {
            $transport = 'mail';
        }

        $defaultFromAddress = (string) ($settings['mail_from_address'] ?? ($settings['site_email'] ?? ''));
        $defaultFromName = (string) ($settings['mail_from_name'] ?? ($settings['site_name'] ?? config('app.name', 'FlatCMS')));

        $fromAddress = trim((string) ($options['from_address'] ?? env('MAIL_FROM_ADDRESS', $defaultFromAddress)));
        $fromName = trim((string) ($options['from_name'] ?? env('MAIL_FROM_NAME', $defaultFromName)));

        if (!$this->isSafeHeaderValue($fromAddress) || !$this->isSafeHeaderValue($fromName)) {
            return false;
        }

        if ($fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = $this->fallbackFromAddress();
        }

        if ($transport === 'smtp') {
            return $this->sendSmtp($to, $subject, $textBody, [
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'settings' => $settings,
                'html_body' => $htmlBody,
                'attachments' => $attachments,
            ]);
        }

        return $this->sendMail($to, $subject, $textBody, [
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'html_body' => $htmlBody,
            'attachments' => $attachments,
        ]);
    }

    private function sendMail(string $to, string $subject, string $textBody, array $options): bool
    {
        $fromAddress = (string) ($options['from_address'] ?? '');
        $fromName = (string) ($options['from_name'] ?? '');
        $htmlBody = trim((string) ($options['html_body'] ?? ''));
        $attachments = is_array($options['attachments'] ?? null) ? $options['attachments'] : [];

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'X-Mailer: FlatCMS';

        $fromHeader = $fromAddress;
        if ($fromName !== '') {
            $fromHeader = sprintf('"%s" <%s>', $this->escapeHeaderPhrase($fromName), $fromAddress);
        }

        $headers[] = 'From: ' . $fromHeader;
        $headers[] = 'Reply-To: ' . $fromAddress;

        $encodedSubject = $this->encodeHeader($subject);
        $normalizedTextBody = $this->normalizeBody($textBody);
        $body = $normalizedTextBody;

        if ($attachments !== []) {
            $mixedBoundary = '=_FlatCMS_Mixed_' . md5((string) microtime(true));
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

            $parts = [];
            if ($htmlBody !== '') {
                $altBoundary = '=_FlatCMS_Alt_' . md5($mixedBoundary . '_alt');
                $normalizedHtmlBody = $this->normalizeHtmlBody($htmlBody);
                $parts[] = '--' . $mixedBoundary . "\r\n"
                    . 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . "\r\n\r\n"
                    . '--' . $altBoundary . "\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . $normalizedTextBody . "\r\n"
                    . '--' . $altBoundary . "\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . $normalizedHtmlBody . "\r\n"
                    . '--' . $altBoundary . "--\r\n";
            } else {
                $parts[] = '--' . $mixedBoundary . "\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . $normalizedTextBody . "\r\n";
            }

            foreach ($attachments as $attachment) {
                $part = $this->buildMailAttachmentPart($mixedBoundary, $attachment);
                if ($part === '') {
                    continue;
                }
                $parts[] = $part;
            }

            $body = implode('', $parts) . '--' . $mixedBoundary . "--\r\n";
        } elseif ($htmlBody !== '') {
            $boundary = '=_FlatCMS_' . md5((string) microtime(true));
            $normalizedHtmlBody = $this->normalizeHtmlBody($htmlBody);
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $normalizedTextBody . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $normalizedHtmlBody . "\r\n"
                . '--' . $boundary . "--\r\n";
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        }

        return (bool) @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    }

    private function sendSmtp(string $to, string $subject, string $textBody, array $options): bool
    {
        $fromAddress = (string) ($options['from_address'] ?? '');
        $fromName = (string) ($options['from_name'] ?? '');
        $htmlBody = trim((string) ($options['html_body'] ?? ''));
        $attachments = is_array($options['attachments'] ?? null) ? $options['attachments'] : [];
        $settings = is_array($options['settings'] ?? null) ? $options['settings'] : [];

        $host = $this->normalizeSmtpHost((string) ($settings['mail_smtp_host'] ?? env('MAIL_SMTP_HOST', '')));
        if ($host === '') {
            return false;
        }

        $port = (int) ($settings['mail_smtp_port'] ?? env('MAIL_SMTP_PORT', 587));
        if ($port <= 0 || $port > 65535) {
            $port = 587;
        }

        $encryption = strtolower(trim((string) ($settings['mail_smtp_encryption'] ?? env('MAIL_SMTP_ENCRYPTION', 'tls'))));
        if (!in_array($encryption, ['', 'none', 'tls', 'ssl'], true)) {
            $encryption = 'tls';
        }

        $username = trim((string) ($settings['mail_smtp_username'] ?? env('MAIL_SMTP_USERNAME', '')));
        $envPassword = (string) env('MAIL_SMTP_PASSWORD', '');
        $storedPassword = (new SecretBox())->decrypt((string) ($settings['mail_smtp_password'] ?? ''));
        $password = $envPassword !== '' ? $envPassword : $storedPassword;
        $timeout = (int) env('MAIL_SMTP_TIMEOUT', 6);
        if ($timeout < 2) {
            $timeout = 2;
        }
        $slowThresholdMs = (int) env('MAIL_SLOW_LOG_THRESHOLD_MS', 2000);
        if ($slowThresholdMs < 250) {
            $slowThresholdMs = 250;
        }

        if (!$this->loadPHPMailer()) {
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->Timeout = $timeout;
            $mail->SMTPKeepAlive = false;
            $mail->SMTPDebug = 0;
            $mail->getSMTPInstance()->Timelimit = $timeout;

            if ($username !== '' || $password !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = $password;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = true;
            }

            $mail->setFrom($fromAddress, $fromName !== '' ? $fromName : $fromAddress);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            if ($htmlBody !== '') {
                $mail->isHTML(true);
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody;
            } else {
                $mail->isHTML(false);
                $mail->Body = $textBody;
            }

            foreach ($attachments as $attachment) {
                $path = trim((string) ($attachment['path'] ?? ''));
                if ($path === '' || !is_file($path)) {
                    continue;
                }

                $name = trim((string) ($attachment['name'] ?? ''));
                if ($name === '') {
                    $name = basename($path);
                }

                $mail->addAttachment($path, $name);
            }

            $start = microtime(true);
            $sent = (bool) $mail->send();
            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            if ($elapsedMs >= $slowThresholdMs) {
                error_log(sprintf(
                    '[FlatCMS] Slow SMTP send (%d ms) host=%s port=%d to=%s',
                    $elapsedMs,
                    $host,
                    $port,
                    $to
                ));
            }

            return $sent;
        } catch (\Throwable $e) {
            if ((bool) env('APP_DEBUG', false)) {
                error_log('[FlatCMS] SMTP send failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function normalizeSmtpHost(string $host): string
    {
        $host = trim($host);
        if ($host === '') {
            return '';
        }

        $parts = preg_split('/[;,]+/', $host);
        $candidate = trim((string) ($parts[0] ?? $host));
        $candidate = preg_replace('#^(ssl|tls)://#i', '', $candidate) ?? $candidate;
        return trim($candidate);
    }

    private function loadPHPMailer(): bool
    {
        $base = BASE_PATH . '/app/ThirdParty/PHPMailer/src';
        $files = [
            $base . '/Exception.php',
            $base . '/SMTP.php',
            $base . '/PHPMailer.php',
        ];

        foreach ($files as $file) {
            if (!is_file($file)) {
                return false;
            }
            require_once $file;
        }

        return class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    }

    private function fallbackFromAddress(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = preg_replace('/:\\d+$/', '', $host);
        $host = strtolower(trim($host));
        $host = preg_replace('/[^a-z0-9.-]/', '', $host);
        if ($host === '') {
            $host = 'localhost';
        }
        return 'no-reply@' . $host;
    }

    private function isSafeHeaderValue(string $value): bool
    {
        return !str_contains($value, "\r") && !str_contains($value, "\n");
    }

    private function encodeHeader(string $value): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function normalizeBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = trim($body) . "\n";
        return str_replace("\n", "\r\n", $body);
    }

    private function normalizeHtmlBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = trim($body) . "\n";
        return str_replace("\n", "\r\n", $body);
    }

    /**
     * @param mixed $attachments
     * @return array<int,array{path:string,name:string}>
     */
    private function normalizeAttachments(mixed $attachments): array
    {
        if (!is_array($attachments)) {
            return [];
        }

        $normalized = [];
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $path = trim((string) ($attachment['path'] ?? ''));
            if ($path === '' || !is_file($path)) {
                continue;
            }

            $name = trim((string) ($attachment['name'] ?? ''));
            if ($name === '') {
                $name = basename($path);
            }

            $normalized[] = [
                'path' => $path,
                'name' => $name,
            ];
        }

        return $normalized;
    }

    /**
     * @param array{path:string,name:string} $attachment
     */
    private function buildMailAttachmentPart(string $boundary, array $attachment): string
    {
        $path = trim((string) ($attachment['path'] ?? ''));
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return '';
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        $mimeType = $this->detectMimeType($path);
        $filename = $this->sanitizeAttachmentFilename((string) ($attachment['name'] ?? basename($path)));

        return '--' . $boundary . "\r\n"
            . 'Content-Type: ' . $mimeType . '; name="' . $filename . '"' . "\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n\r\n"
            . chunk_split(base64_encode($contents)) . "\r\n";
    }

    private function detectMimeType(string $path): string
    {
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($path);
            if (is_string($detected) && trim($detected) !== '') {
                return trim($detected);
            }
        }

        return 'application/octet-stream';
    }

    private function sanitizeAttachmentFilename(string $value): string
    {
        $value = basename(str_replace('\\', '/', trim($value)));
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? '';
        $value = trim($value, '_');

        return $value !== '' ? $value : 'attachment';
    }

    private function escapeHeaderPhrase(string $value): string
    {
        $value = str_replace(['"', '\\'], ['', ''], $value);
        $value = preg_replace('/[\\r\\n]+/', ' ', $value);
        return trim($value);
    }
}
