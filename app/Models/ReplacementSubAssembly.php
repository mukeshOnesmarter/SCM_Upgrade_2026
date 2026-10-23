<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplacementSubAssembly extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'sub_assembly_component_id',
        'quantity',
    ];
}
