<?php

namespace App\Models\Decisions;

use App\Models\Product;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturing extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'product_id',
        'production_quantity',
        'emergency_production',
        'adjustable_production',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
    ];

    /**
     * Relationship: Belongs to a product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent()
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
