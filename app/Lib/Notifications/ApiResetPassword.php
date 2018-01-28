<?php

namespace App\Lib\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ApiResetPassword extends ResetPassword
{
    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token, $passwordResetUrl)
    {
        $this->token = $token;
        $this->passwordResetUrl = $passwordResetUrl;
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // $resetUrl = url(config('app.url').route('password.reset', $this->token, false);
        $resetUrl = $this->passwordResetUrl . '?token=' . $this->token;
        return (new MailMessage)
            ->line('You are XXX receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('If you did not request a password reset, no further action is required.');
    }
}
