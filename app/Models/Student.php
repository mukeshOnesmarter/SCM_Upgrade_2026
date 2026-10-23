<?php

namespace App\Models;

use App\Notifications\ResetPasswordWithRole;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Student extends Model implements CanResetPassword
{
    use CanResetPasswordTrait, Notifiable;
    use HasFactory;

    protected $fillable = [
        'student_code',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'dob',
        'gender',
        'department',
        'join_date',
        'graduation_date',
        'status',
    ];

    /**
     * Relationship: A Student Has One Profile
     */
    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    protected static function booted()
    {
        static::creating(function ($student) {
            do {
                $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Student::where('student_code', $code)->exists()); // Ensure uniqueness

            $student->student_code = $code;
        });
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordWithRole($token, 'student'));
    }
}
