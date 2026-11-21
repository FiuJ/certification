<?php

namespace App\Services;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GarageService
{
    /**
     * Get all garages.
     */
    public function getGarages(): Collection
    {
        return Garage::orderBy('garage_code')->get();
    }

    /**
     * Create garages in a batch with automatic garage codes.
     */
    public function createGarages(array $data): Collection
    {
        $prefixes = [
            'bicycle' => 'S',
            'car' => 'C',
            'electric_car' => 'E',
        ];

        $type = $data['garage_type'];
        $quantity = (int) $data['quantity'];

        if (!isset($prefixes[$type])) {
            throw ValidationException::withMessages([
                'garage_type' => 'Invalid garage type.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }

        return DB::transaction(function () use (
            $data,
            $prefixes,
            $type,
            $quantity
        ) {
            // Get the highest existing garage code for this type.
            $lastGarage = Garage::where('garage_type', $type)
                ->orderByDesc('garage_code')
                ->lockForUpdate()
                ->first();

            $lastNumber = $lastGarage
                ? (int) substr($lastGarage->garage_code, 1)
                : 0;

            // Each garage type supports codes 001 through 999.
            if ($lastNumber + $quantity > 999) {
                throw ValidationException::withMessages([
                    'quantity' => 'Maximum 999 garages per type.',
                ]);
            }

            $garages = new Collection();

            for ($i = 1; $i <= $quantity; $i++) {
                $number = $lastNumber + $i;

                $code = $prefixes[$type]
                    . str_pad($number, 3, '0', STR_PAD_LEFT);

                $garage = Garage::create([
                    'garage_type' => $type,
                    'garage_code' => $code,
                    'daily_rate' => $data['daily_rate'],
                    'electricity_rate_per_kwh' =>
                        $type === 'electric_car'
                            ? $data['electricity_rate_per_kwh']
                            : null,
                    'status' => 'available',
                ]);

                $garages->push($garage);
            }

            return $garages;
        }, 3);
    }
}
