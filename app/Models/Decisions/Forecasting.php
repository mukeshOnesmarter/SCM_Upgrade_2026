<?php

namespace App\Models\Decisions;

use App\Models\Firm;
use App\Models\Product;
use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecasting extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'product_id',
        'firm_id',
        'demand_channel_01',
        'demand_channel_02',
        'accuracy_channel_01',
        'accuracy_channel_02',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
    ];

    /**
     * Relationship: Belongs to a region
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Relationship: Belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship: Belongs to a firm
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class, 'firm_id');
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent()
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
