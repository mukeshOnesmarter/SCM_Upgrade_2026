<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOutsourcing extends Model
{
    use HasFactory;

    public function serviceCost(): HasMany
    {
        return $this->hasMany(ServiceOutsourcingCost::class, 'service_outsourcing_id');
    }
}
