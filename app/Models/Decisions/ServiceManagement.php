<?php

namespace App\Models\Decisions;

use App\Models\Region;
use App\Models\ServiceOutsourcing;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceManagement extends Model
{
    protected $table = 'service_managements';

    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'service_id',
        'change_saved_by',
        'change_saved_timestamp	',
        'change_log_history	',
        'extra_data	',
    ];

    /**
     * Relationship: Belongs to a Region
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Relationship: Belongs to a service_outsourcings
     */
    public function serviceOutsourcing(): BelongsTo
    {
        return $this->belongsTo(ServiceOutsourcing::class, 'service_id');
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
