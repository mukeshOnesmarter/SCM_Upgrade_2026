<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'simulation_id',
        'faculty_id',
    ];

    /**
     * Relationship: Belongs To A Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relationship: Belongs To A Simulation
     */
    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }

    /**
     * Relationship: Belongs To A Faculty
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function teamMember()
    {
        return $this->hasOne(SimulationTeamMember::class, 'student_id', 'student_id');
    }
}
