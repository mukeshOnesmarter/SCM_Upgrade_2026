<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandElasticity extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'product_id',
        'demand_channel_id',
        'pq',
        'sq',
        'aq',
        'cai',
        'extra_data',
    ];
}
