<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationOrderQuarterDecision extends Model
{
    protected $table = 'simulation_order_quarter_decisions';

    protected $fillable = [
        'simulation_order_quarter_id',
        'decision_id',
    ];

    /**
     * The quarter this pivot belongs to.
     */
    public function quarter(): BelongsTo
    {
        return $this->belongsTo(SimulationOrderQuarter::class, 'simulation_order_quarter_id');
    }

    /**
     * The decision attached to that quarter.
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'decision_id');
    }
}
