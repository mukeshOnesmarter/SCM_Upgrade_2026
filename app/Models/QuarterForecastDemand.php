<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarterForecastDemand extends Model
{
    use HasFactory;

    protected $fillable = ['simulation_id', 'product_id', 'region_id', 'firm_id', 'demand_channel_01', 'demand_channel_02', 'accuracy_channel_01', 'accuracy_channel_02', 'quarter'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }
}
