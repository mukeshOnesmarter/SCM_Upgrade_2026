<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['region_id', 'name', 'custome_name', 'description', 'contact_info', 'status'];

    /**
     * Relationship: A Supplier Has Many Component
     */
    public function supplierComponent()
    {
        return $this->hasMany(SupplierComponent::class);
    }

    /**
     * Relationship: A Supplier Has Many Component
     */
    public function supplierComponentPrice()
    {
        return $this->hasMany(SupplierComponentPrice::class);
    }
}
