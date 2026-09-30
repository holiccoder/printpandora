<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class PasswordChangeNotification extends ResetPassword
{
    /**
     * Build a link for the account password-change page instead of Fortify's
     * guest-only public reset page.
     *
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return url(route('password.change', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
