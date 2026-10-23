<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarterProbabilityDemand extends Model
{
    use HasFactory;

    protected $fillable = ['simulation_id', 'product_id', 'region_id', 'firm_id', 'demand_channel_01', 'demand_channel_02', 'quarter', 'gdp_pct'];
}
