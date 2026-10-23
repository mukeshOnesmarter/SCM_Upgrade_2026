<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMaterial extends Model
{
    use HasFactory;

    /**
     * Relationship: Belongs To A Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Belongs To A RawMaterial
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'material_id');
    }

    /**
     * Relationship: Belongs To A SubAssemblyComponent
     */
    public function sac()
    {
        return $this->belongsTo(SubAssemblyComponent::class, 'material_id');
    }
}
