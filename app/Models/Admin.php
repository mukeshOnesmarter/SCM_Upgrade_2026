<?php

namespace App\Models;

use App\Notifications\ResetPasswordWithRole;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Admin extends Model implements CanResetPassword
{
    use CanResetPasswordTrait, Notifiable;
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'password'];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordWithRole($token, 'admin'));
    }
}
