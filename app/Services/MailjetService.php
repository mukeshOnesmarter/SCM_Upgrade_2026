<?php

namespace App\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one HTML email. Kept under its old name and signature so the callers
 * don't change, but it now goes through Laravel's mailer (MAIL_MAILER=mailjet,
 * Mailjet's Send API), so tests can fake it and the keys are read only when an
 * email is actually sent.
 *
 * The sender is MAIL_FROM_ADDRESS / MAIL_FROM_NAME, not a personal address.
 */
class MailjetService
{
    public function send($toEmail, $toName, $subject, $html): void
    {
        Mail::html((string) $html, function (Message $message) use ($toEmail, $toName, $subject) {
            $message->to((string) $toEmail, (string) ($toName ?: $toEmail))
                ->subject((string) $subject);
        });
    }
}
