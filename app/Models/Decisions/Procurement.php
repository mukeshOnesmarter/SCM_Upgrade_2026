<?php

namespace App\Models\Decisions;

use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'faculty_id',
        'group_number',
        'quarter',
        'region_id',
        'procurement_type',
        'change_saved_by',
        'change_saved_timestamp',
        'change_log_history',
    ];

    /**
     * A Procurement has many raw material
     */
    public function rawMaterial()
    {
        return $this->hasMany(ProcurementRawMaterial::class, 'procurement_id');
    }

    /**
     * A Procurement has many sub assembly
     */
    public function subAssembly()
    {
        return $this->hasMany(ProcurementSubAssembly::class, 'procurement_id');
    }

    public function procurementEmergencyRaw()
    {
        return $this->hasMany(ProcurementEmergencyRaw::class, 'procurement_id');
    }

    public function procurementEmergencySub()
    {
        return $this->hasMany(ProcurementEmergencySub::class, 'procurement_id');
    }

    /**
     * Relationship: Belongs to a region_id
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
