<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'dc_number',
        'plastic',
        'casing',
        'control_interface',
        'audio_modal',
        'motherboard',
        'motherboard_on_order',
    ];
}
