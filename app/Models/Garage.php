<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garage extends Model
{
    use HasUuids;

    protected $primaryKey = 'garage_id';

    protected $fillable = [
        'garage_type',
        'garage_code',
        'daily_rate',
        'electricity_rate_per_kwh',
        'status',
    ];

    public function rentals(): HasMany
    {
        return $this->hasMany(
            Rental::class,
            'garage_id',
            'garage_id'
        );
    }
}
