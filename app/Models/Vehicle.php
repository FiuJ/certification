<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasUuids;

    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'user_id',
        'type',
        'license_plate',
        'brand',
        'model',
        'battery_capacity',
    ];

    protected function casts(): array
    {
        return [
            'battery_capacity' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(
            Rental::class,
            'vehicle_id',
            'vehicle_id'
        );
    }
}
