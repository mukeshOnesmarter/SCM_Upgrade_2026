<?php

namespace App\Mail;

use App\Models\Faculty;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class SimulationInviteFacultyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $simulation_id;

    public $faculty_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($simulation_id, $faculty_id)
    {
        $this->simulation_id = $simulation_id;
        $this->faculty_id = $faculty_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $simulation = Simulation::with('simulationQuarters')->find($this->simulation_id);
        $simulation->course_code = $simulation->share_code;
        $simulation->share_code = Crypt::encryptString($simulation->share_code);

        $faculty = Faculty::find($this->faculty_id);
        $faculty->faculty_code = Crypt::encryptString($faculty->faculty_code);

        return $this->subject('Course Code And Order Details')
            ->view('emails.simulation-invite-faculty', compact('simulation', 'faculty'));
    }
}
