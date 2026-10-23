<?php

namespace App\Models;

use App\Notifications\ResetPasswordWithRole;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Faculty extends Model implements CanResetPassword
{
    use CanResetPasswordTrait, Notifiable;
    use HasFactory;

    protected $fillable = [
        'faculty_code',
        'role',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'department',
        'designation',
        'join_date',
        'status',
    ];

    /**
     * Relationship: A Faculty Has One Profile
     */
    public function profile()
    {
        return $this->hasOne(FacultyProfile::class);
    }

    protected static function booted()
    {
        static::creating(function ($faculty) {
            // GENERATE A RANDOM 8-DIGIT NUMBER
            $faculty->faculty_code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        });
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordWithRole($token, 'faculty'));
    }
}
