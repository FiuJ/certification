<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargingRecord extends Model
{
    use HasUuids;

    protected $primaryKey = 'charging_record_id';

    protected $fillable = [
        'rental_id',
        'current_battery_percentage',
        'amount',
        'payment_path',
    ];

    protected function casts(): array
    {
        return [
            'current_battery_percentage' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(
            Rental::class,
            'rental_id',
            'rental_id'
        );
    }

}
