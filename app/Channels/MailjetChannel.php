<?php

namespace App\Channels;

use App\Notifications\ResetPasswordWithRole;
use App\Services\MailjetService;
use Illuminate\Notifications\Notification;

class MailjetChannel
{
    protected $mailjet;

    public function __construct(MailjetService $mailjet)
    {
        $this->mailjet = $mailjet;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {

        // Check if notification supports Mailjet
        if (! method_exists($notification, 'toMailjet')) {
            return;
        }

        /** @var ResetPasswordWithRole $notification */
        $message = $notification->toMailjet($notifiable);

        // Defensive check in case $message is not formatted correctly
        if (! isset($message['to'], $message['subject'], $message['html'])) {
            throw new \Exception('Mailjet message is missing required fields.');
        }

        $this->mailjet->send(
            $message['to'],
            $message['name'] ?? $message['to'],
            $message['subject'],
            $message['html']
        );
    }
}
