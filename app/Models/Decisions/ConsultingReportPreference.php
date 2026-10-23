<?php

namespace App\Models\Decisions;

use App\Models\ConsultingFee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultingReportPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_id',
        'quarter',
        'consulting_fee_id',
    ];

    /**
     * Relationship: Each preference belongs to a consulting fee
     */
    public function consultingFee()
    {
        return $this->belongsTo(ConsultingFee::class);
    }
}
