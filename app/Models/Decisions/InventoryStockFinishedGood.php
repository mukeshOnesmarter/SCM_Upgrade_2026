<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStockFinishedGood extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'dc_number',
        'product_0',
        'product_01',
        'product_02',
    ];
}
