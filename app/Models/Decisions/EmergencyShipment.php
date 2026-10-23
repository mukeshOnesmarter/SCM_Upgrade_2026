<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Model;

class EmergencyShipment extends Model
{
    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_id',
        'quarter',
        'emergency_production_id',
        'product_id',
        'quantity',
        'unit_freight',
        'total_freight',
        'mode',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
