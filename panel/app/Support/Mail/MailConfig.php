<?php

namespace App\Support\Mail;

final class MailConfig
{
    public static function smtpConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array', 'failover', 'roundrobin'], true)) {
            return false;
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');

            return $host !== '' && $host !== '127.0.0.1';
        }

        return filled(config("mail.mailers.{$mailer}.transport") ?? $mailer);
    }

    public static function verificationEnabled(): bool
    {
        return (bool) config('panel.verify_email') && self::smtpConfigured();
    }
}
