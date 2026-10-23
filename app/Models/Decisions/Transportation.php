<?php

namespace App\Models\Decisions;

use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transportation extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'distribution_center_id',
        'change_saved_by',
    ];

    /**
     *  A Transportation has many transportation_infos
     */
    public function info(): HasMany
    {
        return $this->hasMany(TransportationInfo::class);
    }

    public function transportationInfo(): HasMany
    {
        return $this->hasMany(TransportationInfo::class);
    }

    /**
     * Relationship: Belongs to a region
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'distribution_center_id');
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
