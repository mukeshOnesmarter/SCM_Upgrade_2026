<?php

namespace App\Models\Decisions;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationTechnologySupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'information_technology_id',
        'supplier_id',
        'status',
    ];

    /**
     * Relationship: Belongs to a supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: Belongs to a information technology
     */
    public function informationTechnology(): BelongsTo
    {
        return $this->belongsTo(InformationTechnology::class);
    }
}
