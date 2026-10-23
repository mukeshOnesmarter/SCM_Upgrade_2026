<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SimulationOrderQuarter extends Model
{
    protected $table = 'simulation_order_quarters';

    protected $fillable = [
        'simulation_order_id',
        'quarter_number',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'quarter_number' => 'integer',
    ];

    /**
     * The parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SimulationOrder::class, 'simulation_order_id');
    }

    /**
     * Decisions via pivot table simulation_order_quarter_decisions.
     * If the pivot contains additional columns consider using a custom pivot model.
     */
    public function decisions(): BelongsToMany
    {
        return $this->belongsToMany(
            Decision::class,
            'simulation_order_quarter_decisions', // pivot table
            'simulation_order_quarter_id',        // this model FK on pivot
            'decision_id'                         // related model FK on pivot
        )->withTimestamps();
    }
}
