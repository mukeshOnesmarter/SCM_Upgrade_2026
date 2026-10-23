<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'group_number',
        'team_name',
        'team_members',
        'status',
        'deleted_at',
    ];

    /**
     * Relationship: Belongs To A Simulation
     */
    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }
}
