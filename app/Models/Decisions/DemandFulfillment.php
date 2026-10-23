<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Model;

class DemandFulfillment extends Model
{
    protected $table = 'demand_fulfillments';

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'dc_number',
        'product_id',
        'demand',
        'taken_from_fg',
        'taken_from_postpond',
        'taken_from_plant',
        'produced_emergency',
        'unfilled',
        'unit_price',
        'production_unit_cost',
        'air_unit_cost',
        'postpond_conversion_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'production_unit_cost' => 'decimal:4',
        'air_unit_cost' => 'decimal:4',
    ];
}
