<?php

namespace App\Notifications;

use App\Channels\MailjetChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResetPasswordWithRole extends Notification
{
    use Queueable;

    public $token;

    public $role;

    /** True for a new account's first email: set your password, not reset it. */
    public bool $newAccount;

    public function __construct($token, $role, bool $newAccount = false)
    {
        $this->token = $token;
        $this->role = $role;
        $this->newAccount = $newAccount;
    }

    public function via($notifiable)
    {
        return [MailjetChannel::class];
    }

    public function toMailjet($notifiable)
    {
        $url = url("/{$this->role}/password/reset/{$this->token}?email={$notifiable->email}");

        $html = view('emails.reset-password', [
            'url' => $url,
            'name' => $notifiable->name ?? 'User',
        ])->render();

        return [
            'to' => $notifiable->email,
            'name' => $notifiable->name ?? $notifiable->email,
            'subject' => $this->newAccount ? 'Set your FLEXEE password' : 'Reset Your Password',
            'html' => $html,
        ];
    }
}
