<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierChannel extends Model
{
    use HasFactory;

    /**
     * Relationship: A CarrierChannel belongs to a Carrier.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Relationship: A CarrierChannel belongs to a SupplyChannel.
     */
    public function supplyChannel(): BelongsTo
    {
        return $this->belongsTo(SupplyChannel::class);
    }
}
