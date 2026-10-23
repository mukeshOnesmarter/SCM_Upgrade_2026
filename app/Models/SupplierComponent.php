<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierComponent extends Model
{
    use HasFactory;

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
