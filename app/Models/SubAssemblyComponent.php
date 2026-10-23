<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SubAssemblyComponent extends Model
{
    use HasFactory;

    /**
     * Relationship: A SubAssemblyComponent Has One Transportation Cost
     */
    public function transportationCost(): HasOne
    {
        return $this->hasOne(SubAssemblyTransportationCost::class, 'sub_assembly_component_id');
    }

    /**
     * Relationship: A SubAssemblyComponent Has Many Volume Discount
     */
    public function volumeDiscount(): HasMany
    {
        return $this->hasMany(SubAssemblyVolumeDiscount::class, 'sub_assembly_component_id');
    }

    /**
     * Relationship: A Component Has Many Supplier
     */
    public function supplierComponent()
    {
        return $this->hasMany(SupplierComponent::class, 'sub_assembly_component_id');
    }
}
