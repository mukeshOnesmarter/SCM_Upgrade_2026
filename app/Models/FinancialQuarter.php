<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialQuarter extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter_id',
        'cash',
        'loans',
        'marketable_securities',
        'non_operating_income',
        'dividends',
        'net_income',
        'retained_earnings_current',
    ];
}
