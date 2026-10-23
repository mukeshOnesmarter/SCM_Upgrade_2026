<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialCost extends Model
{
    use HasFactory;

    /**
     * Relationship: Belongs to a RawMaterial
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
