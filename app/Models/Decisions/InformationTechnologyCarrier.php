<?php

namespace App\Models\Decisions;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationTechnologyCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'information_technology_id',
        'carrier_id',
        'status',
    ];

    /**
     * Relationship: Belongs to a carrier
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Relationship: Belongs to a information technology
     */
    public function informationTechnology(): BelongsTo
    {
        return $this->belongsTo(InformationTechnology::class);
    }
}
