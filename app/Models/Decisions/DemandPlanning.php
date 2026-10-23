<?php

namespace App\Models\Decisions;

use App\Models\DemandChannel;
use App\Models\Product;
use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandPlanning extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'product_id',
        'demand_channel_id',
        'status',
        'price',
        'market_price',
        'marketSpending',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
        'extra_data',
    ];

    /**
     * Relationship: Belongs to a Region
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Relationship: Belongs to a Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Belongs to a DemandChannel
     */
    public function demandChannel(): BelongsTo
    {
        return $this->belongsTo(DemandChannel::class, 'demand_channel_id');
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent()
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
