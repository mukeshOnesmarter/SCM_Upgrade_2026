<?php

namespace App\Models\Decisions;

use App\Models\Carrier;
use App\Models\Product;
use App\Models\SupplyChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'transportation_id',
        'product_id',
        'carrier_id',
        'supply_channel_id',
        'quantity',
        'total_shipment',
        'quantity_delay',
    ];

    /**
     * Relationship: Belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship: Belongs to a carrier
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    /**
     * Relationship: Belongs to a supplyChannel
     */
    public function supplyChannel(): BelongsTo
    {
        return $this->belongsTo(SupplyChannel::class, 'supply_channel_id');
    }

    /**
     * Relationship: Belongs to a carrier
     */
    public function transportation(): BelongsTo
    {
        return $this->belongsTo(Transportation::class);
    }
}
