<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class VehicleService
{
    public function getVehicles(User $user): Collection
    {
        return $user->vehicles()
            ->latest()
            ->get();
    }

    public function getVehicle(User $user, string $vehicleId): Vehicle
    {
        return $user->vehicles()
            ->where('vehicle_id', $vehicleId)
            ->firstOrFail();
    }

    public function createVehicle(User $user, array $data): Vehicle
    {
        return $user->vehicles()->create([
            'type' => $data['type'],

            'license_plate' => $data['type'] === 'bicycle'
                ? null
                : $data['license_plate'],

            'brand' => $data['brand'],
            'model' => $data['model'],

            'battery_capacity' => $data['type'] === 'electric_car'
                ? $data['battery_capacity']
                : null,
        ]);
    }
}
