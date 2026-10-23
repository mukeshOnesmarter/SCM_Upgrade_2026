<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostpondConversion extends Model
{
    use HasFactory;

    protected $table = 'postpond_conversions';

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'dc_number',
        'converted_p1',
        'converted_p2',
        'total_converted',
    ];
}
