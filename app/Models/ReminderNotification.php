<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'quarter_id',
        'reminder_type',
        'sent_at',
    ];
}
