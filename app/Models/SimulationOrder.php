<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationOrder extends Model
{
    use HasFactory;

    protected $table = 'simulation_orders';

    protected $fillable = [
        'course_title',
        'organization',
        'faculty_id',
        'faculty_name',
        'faculty_email',
        'start_date',
        'quarters',
        'teams',
        'learners',
        'fee_per_learner',
        'payment_responsibility',
        'time_zone',
        'special_instructions',
        'settings',
        'extra_data',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'fee_per_learner' => 'decimal:2',
        'quarters' => 'integer',
        'teams' => 'integer',
        'learners' => 'integer',
        'settings' => 'array',
        'extra_data' => 'array',
    ];

    /**
     * Get the quarters for this order.
     */
    public function orderQuarters(): HasMany
    {
        return $this->hasMany(SimulationOrderQuarter::class, 'simulation_order_id')
            ->orderBy('quarter_number');
    }

    /**
     * The faculty who placed / owns the order.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
