<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your SmelterWorks panel email')
            ->greeting('Confirm your email')
            ->line('Click the button below to verify your email address and finish setting up your panel account.')
            ->action('Verify email address', $verificationUrl)
            ->line('If you did not create an account, you can ignore this message.');
    }
}
