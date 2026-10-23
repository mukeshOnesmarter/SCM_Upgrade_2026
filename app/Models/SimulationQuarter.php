<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationQuarter extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'quarter_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'decisions',
        'status',
        'deleted_at',
    ];

    protected $casts = [
        'decisions' => 'array',
    ];

    /**
     * Relationship: Belongs To A Simulation
     */
    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }
}
