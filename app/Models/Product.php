<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['industry_id', 'sort_name', 'name', 'distribution', 'description'];

    /**
     * Relationship: A Product Has Many Product Material
     */
    public function material()
    {
        return $this->hasMany(ProductMaterial::class);
    }

    /**
     * Relationship: Belongs to a Industry
     */
    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }
}
