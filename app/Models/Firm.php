<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Firm extends Model
{
    use HasFactory;

    protected $fillable = ['industry_id', 'name', 'custome_name', 'description', 'business_types', 'demand_increment'];
}
