<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyProduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_id',
        'product_id',
        'quarter',
        'quantity',
        'estimated_unit_cost',
        'estimated_total_cost',
        'source',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
