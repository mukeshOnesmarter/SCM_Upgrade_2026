<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;

    public $loginUrl;

    public $userEmail;

    public $userPassword;

    public $accountValidateUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($studentName, $loginUrl, $userEmail, $userPassword, $accountValidateUrl)
    {
        $this->studentName = $studentName;
        $this->loginUrl = $loginUrl;
        $this->userEmail = $userEmail;
        $this->userPassword = $userPassword;
        $this->accountValidateUrl = $accountValidateUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('FLEXEE: Account Validate')
            ->view('emails.account-validate');
    }
}
