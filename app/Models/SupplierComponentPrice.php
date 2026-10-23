<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierComponentPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'quarter_id',
        'supplier_id',
        'sub_assembly_component_id',
        'adjusted_price',
        'market_share',
        'has_capacity_constraint',
        'extra_data',
    ];

    /**
     * Relationship: Belongs to a Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: Belongs to a SubAssemblyComponent
     */
    public function subAssemblyComponent()
    {
        return $this->belongsTo(SubAssemblyComponent::class);
    }
}
