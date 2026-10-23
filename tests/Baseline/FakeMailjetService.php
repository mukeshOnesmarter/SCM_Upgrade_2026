<?php

namespace Tests\Baseline;

use App\Services\MailjetService;

/**
 * Stands in for MailjetService during the baseline run.
 * It records what would have been sent and sends nothing.
 */
class FakeMailjetService extends MailjetService
{
    public array $sent = [];

    public function send($toEmail, $toName, $subject, $html): void
    {
        $this->sent[] = ['to' => $toEmail, 'subject' => $subject];
    }
}
