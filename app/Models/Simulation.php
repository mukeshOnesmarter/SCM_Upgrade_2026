<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Simulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'organization',
        'quarters',
        'firms',
        'teams',
        'start_date',
        'end_date',
        'timezone',
        'is_published',
        'simulation_status',
        'fee',
        'licence_count',
        'payment_status',
        'is_syncing',
        'is_deploying',
        'is_analysis_enabled',
        'created_by',
    ];

    /**
     * Relationship: A Simulation Has Many Faculties
     */
    public function simulationFaculties()
    {
        return $this->hasMany(SimulationFaculty::class);
    }

    /**
     * Relationship: A Simulation has many Firms
     */
    public function simulationFirms()
    {
        return $this->hasMany(SimulationFirm::class);
    }

    /**
     * Relationship: A Simulation has many Quarters
     */
    public function simulationQuarters()
    {
        return $this->hasMany(SimulationQuarter::class)
            ->orderBy('quarter_name', 'asc');
    }

    /**
     * Relationship: A Simulation has many Quarters Customize
     */
    public function simulationQuarterCustomize()
    {
        return $this->hasMany(SimulationQuarterCustomize::class)
            ->orderBy('quarter_name', 'asc');
    }

    /**
     * Relationship: A Simulation has many Teams
     */
    public function simulationTeams()
    {
        return $this->hasMany(SimulationTeam::class);
    }

    public function simulationAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by', 'id');
    }

    /**
     * LIFECYCLE HOOKS
     */
    protected static function booted()
    {
        static::creating(function ($simulation) {
            // GENERATE A RANDOM 8-DIGIT NUMBER
            $simulation->simulation_code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            // GENERATE UNIQUE CODE IN THE FORMAT 'YZFU-RY9Q'
            $simulation->share_code = self::generateUniqueCode();
        });
    }

    /**
     * Generate Unique Code
     */
    public static function generateUniqueCode()
    {
        // You can customize this pattern, using Str::random for randomness
        $part1 = strtoupper(Str::random(4)); // Generates 4 random uppercase letters
        $part2 = strtoupper(Str::random(1)).rand(0, 9).strtoupper(Str::random(1)); // Generates 1 letter + 1 digit + 1 letter

        return "{$part1}-{$part2}";
    }
}
