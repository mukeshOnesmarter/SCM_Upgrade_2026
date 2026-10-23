<?php

namespace App\Models\Decisions\Demand;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialDemand extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'product_id',
        'firm_id',
        'demand_channel_01',
        'demand_channel_02',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
    ];
}
