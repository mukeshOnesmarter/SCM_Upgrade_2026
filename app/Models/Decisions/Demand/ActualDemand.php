<?php

namespace App\Models\Decisions\Demand;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ActualDemand
 *
 * ⚠️ IMPORTANT CLARIFICATION
 * ----------------------------------------------------
 * This model currently represents **MARKET DEMAND**, NOT actual firm sales.
 *
 * • Data stored here is the total market demand generated
 *   per simulation / quarter / region / product / channel.
 *
 * • It does NOT indicate how much demand a firm fulfilled.
 *   Fulfillment is calculated later via allocation,
 *   production, inventory, and logistics logic.
 */
class ActualDemand extends Model
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
        'shift_applied',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
        'extra_data',
    ];
}
