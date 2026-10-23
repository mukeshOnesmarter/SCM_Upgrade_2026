<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSimulationPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'simulation_id',
        'amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'transaction_reference',
        'confirmed_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function simulation()
    {
        return $this->belongsTo(Simulation::class);
    }
}
