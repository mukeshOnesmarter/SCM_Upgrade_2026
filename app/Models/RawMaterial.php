<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    /**
     * Relationship: A Raw Material Has One Costing
     */
    public function materialCosts()
    {
        return $this->hasOne(RawMaterialCost::class);
    }
}
