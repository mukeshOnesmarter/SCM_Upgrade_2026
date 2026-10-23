<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationFirm extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'firm_id',
        'firm_name_custome',
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

    /**
     * Relationship: Belongs To A Firm
     */
    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }
}
