<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacultyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'profile_picture',
        'address',
        'dob',
        'gender',
        'qualifications',
        'research_interests',
        'experience',
        'bio',
        'social_links',
    ];

    /**
     * Relationship: Belongs to a Faculty
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
