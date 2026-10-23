<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FacultyInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'simulation_id',
        'email',
        'invite_token',
        'invited_at',
        'accepted_at',
        'password_generated',
        'sent_intro_email',
    ];

    /**
     * RELATIONSHIP: BELONGS TO A FACULTY PROFILE
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Relationship: Belongs To A Simulation
     */
    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }

    protected static function booted()
    {
        static::creating(function ($invite) {
            $invite->invite_token = $invite->invite_token ?? Str::random(40);
            $invite->invited_at = $invite->invited_at ?? now();
            // Never stored readable. Nothing reads it back; students set their own
            // password when they register.
            $invite->password_generated = Hash::make($invite->password_generated ?? self::generateRandomPassword());
        });
    }

    // Helper to generate a random password
    public static function generateRandomPassword($length = 12)
    {
        $upperCase = Str::upper(Str::random($length - 4));
        $digits = random_int(10, 99);
        $specialChars = substr(str_shuffle('!@#$%^&*'), 0, 2);
        $password = $upperCase.$digits.$specialChars;

        return str_shuffle($password);
    }
}
