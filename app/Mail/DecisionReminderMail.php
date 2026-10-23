<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DecisionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $simulation;

    public $quarter;

    public $saved;

    public $notSaved;

    public $reminderType;

    public function __construct($student, $simulation, $quarter, array $saved, array $notSaved, string $reminderType)
    {
        $this->student = $student;
        $this->simulation = $simulation;
        $this->quarter = $quarter;
        $this->saved = $saved;
        $this->notSaved = $notSaved;
        $this->reminderType = $reminderType;
    }

    public function build()
    {
        return $this->subject("Reminder: Your simulation decisions for Q{$this->quarter->number}")
            ->view('emails.decision_reminder') // create a blade view
            ->with([
                'student' => $this->student,
                'simulation' => $this->simulation,
                'quarter' => $this->quarter,
                'saved' => $this->saved,
                'notSaved' => $this->notSaved,
                'reminderType' => $this->reminderType,
            ]);
    }
}
