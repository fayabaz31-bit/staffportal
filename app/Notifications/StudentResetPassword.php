<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class StudentResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('student.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
    }
}
