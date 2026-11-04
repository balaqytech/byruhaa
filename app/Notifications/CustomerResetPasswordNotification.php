<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPasswordNotification extends ResetPassword
{
    public function toMail(mixed $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expiresInMinutes = (int) config('auth.passwords.customers.expire', 60);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة مرور حسابك في بيرحاء')
            ->view([
                'html' => 'mail.customer.reset-password',
                'text' => 'mail.customer.reset-password-text',
            ], [
                'customerName' => $notifiable->name,
                'resetUrl' => $resetUrl,
                'expiresInMinutes' => $expiresInMinutes,
            ]);
    }
}
