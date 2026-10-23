<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'profile_picture',
        'address',
        'nationality',
        'emergency_contact',
        'bio',
        'social_links',
        'academic_interests',
        'skills',
    ];

    /**
     * Relationship: Belongs to a Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
