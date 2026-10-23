<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulationJoinToken extends Model
{
    protected $fillable = [
        'token',
        'faculty_id',
        'share_code',
        'simulation_id',
        'expires_at',
    ];
}
