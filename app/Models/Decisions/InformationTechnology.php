<?php

namespace App\Models\Decisions;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformationTechnology extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
        'ai_integration',
        'extra_data',
    ];

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }

    /**
     * Relationship: Technology through many carriers
     */
    public function carriers(): HasMany
    {
        return $this->hasMany(InformationTechnologyCarrier::class);
    }

    /**
     * Relationship: Technology through many suppliers
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(InformationTechnologySupplier::class);
    }
}
