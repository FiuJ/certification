<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use HasUuids;

    protected $primaryKey = 'rental_id';

    protected $fillable = [
        'vehicle_id',
        'garage_id',
        'original_rental_id',
        'rental_type',
        'start_date',
        'duration',
        'rental_amount',
        'payment_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'duration' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            Vehicle::class,
            'vehicle_id',
            'vehicle_id'
        );
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(
            Garage::class,
            'garage_id',
            'garage_id'
        );
    }

    public function originalRental(): BelongsTo
    {
        return $this->belongsTo(
            Rental::class,
            'original_rental_id',
            'rental_id'
        );
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(
            Rental::class,
            'original_rental_id',
            'rental_id'
        );
    }

    public function chargingRecords(): HasMany
    {
        return $this->hasMany(
            ChargingRecord::class,
            'rental_id',
            'rental_id'
        );
    }
}
