<?php

namespace App\Models\Decisions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scorecard extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_id',
        'quarter',
        'category',
        'sub_measure',
        'your_firm',
        'industry_worst',
        'industry_avg',
        'industry_best',
        'unit',
    ];

    protected $casts = [
        'simulation_id' => 'integer',
        'faculty_id' => 'integer',
        'group_id' => 'integer',
        'quarter' => 'integer',
        'your_firm' => 'decimal:2',
        'industry_worst' => 'decimal:2',
        'industry_avg' => 'decimal:2',
        'industry_best' => 'decimal:2',
    ];
}
