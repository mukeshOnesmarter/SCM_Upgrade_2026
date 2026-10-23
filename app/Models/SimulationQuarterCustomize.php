<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationQuarterCustomize extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'simulation_id',
        'quarter_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'decisions',
        'status',
        'deleted_at',
        'close_manualy',
        'closing_sync',
        'sync_date',
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
