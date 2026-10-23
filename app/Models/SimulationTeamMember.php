<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'simulation_team_id',
        'faculty_id',
        'student_id',
        'student_allowed',
        'initial_setup_done',
        'initial_setup_at',
        'student_joined',
        'is_blocked',
        'blocked_at',
        'is_removed',
        'removed_at',
        'payment_status',
        'payment_due_date',
    ];

    /**
     * Relationship: Belongs To A Simulation
     */
    public function simulation()
    {
        return $this->belongsTo(Simulation::class, 'simulation_id');
    }

    /**
     * RELATIONSHIP: BELONGS TO A STUDENT PROFILE
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * RELATIONSHIP: BELONGS TO A FACULTY PROFILE
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
