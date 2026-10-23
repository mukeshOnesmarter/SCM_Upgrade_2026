<?php

namespace App\Models\Decisions;

use App\Models\Carrier;
use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'dc_number',
        'dc_type',
        'is_home_region',
        'is_operational',
        'rfid_application',
        'ffgi_shipping',
        'sac_shipping',
        'cross_docking_k',
        'cross_docking_l',
        'cross_docking_m',
        'cross_docking_n',
        'emergency_carrier_id',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
        'extra_data',
    ];

    /**
     * Relationship: Belongs to a Region
     */
    public function emergencyCarrier()
    {
        return $this->belongsTo(Carrier::class, 'emergency_carrier_id');
    }

    /**
     * Relationship: Belongs to a Region
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Relationship: Belongs to a Student
     */
    public function savedByStudent()
    {
        return $this->belongsTo(Student::class, 'change_saved_by');
    }
}
