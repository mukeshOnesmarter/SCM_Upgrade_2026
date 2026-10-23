<?php

namespace App\Models\Decisions;

use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Models\SupplyChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementEmergencySub extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'sub_assembly_component_id',
        'supplier_id',
        'supply_channel_id',
        'quantity',
        'total_shipment',
        'quantity_delay',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class, 'procurement_id');
    }

    public function subAssemblyComponent()
    {
        return $this->belongsTo(SubAssemblyComponent::class, 'sub_assembly_component_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplyChannel()
    {
        return $this->belongsTo(SupplyChannel::class, 'supply_channel_id');
    }
}
