<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierCost extends Model
{
    use HasFactory;

    /**
     * Relationship: A CarrierChannel belongs to a Carrier.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }
}
