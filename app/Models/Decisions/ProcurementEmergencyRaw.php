<?php

namespace App\Models\Decisions;

use App\Models\RawMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementEmergencyRaw extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'raw_material_id',
        'quantity',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class, 'procurement_id');
    }
}
