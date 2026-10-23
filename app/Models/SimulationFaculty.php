<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationFaculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'role',
        'is_published',
        'frozen_until',
        'status',
        'deleted_at',
        'payment_mode',
    ];

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
}
