<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use HasFactory;

    /**
     * Relationship: A Carrier has many CarrierChannels.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(CarrierChannel::class);
    }

    /**
     * Relationship: A Carrier has many CarrierCost.
     */
    public function carrierCost(): HasMany
    {
        return $this->hasMany(CarrierCost::class);
    }
}
