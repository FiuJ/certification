<?php

namespace App\Services;

use App\Models\ChargingRecord;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChargingService
{
    /**
     * Get charging history for a renter's rental.
     */
    public function getChargingRecords(
        User $user,
        string $rentalId
    ): Collection {
        $rental = $this->getRenterRental($user, $rentalId);

        return $rental->chargingRecords()
            ->latest()
            ->get();
    }

    /**
     * Get an active rental eligible for charging.
     */
    public function getRentalForCharging(
        User $user,
        string $rentalId
    ): Rental {
        $rental = $this->getRenterRental($user, $rentalId);

        $this->ensureChargingEligible($rental);

        return $rental;
    }

    /**
     * Preview the charging cost before payment.
     */
    public function previewCharging(
        User $user,
        string $rentalId,
        float $batteryPercentage
    ): array {
        $rental = $this->getRentalForCharging(
            $user,
            $rentalId
        );

        return $this->calculateCharging(
            $rental,
            $batteryPercentage
        );
    }

    /**
     * Create a charging transaction.
     */
    public function createChargingRecord(
        User $user,
        string $rentalId,
        array $data,
        string $paymentPath
    ): ChargingRecord {
        return DB::transaction(function () use (
            $user,
            $rentalId,
            $data,
            $paymentPath
        ) {
            $rental = $this->getRentalForCharging(
                $user,
                $rentalId
            );

            $percentage = (float) $data[
                'current_battery_percentage'
            ];

            // Recalculate the amount on the server.
            $charging = $this->calculateCharging(
                $rental,
                $percentage
            );

            return $rental->chargingRecords()->create([
                'current_battery_percentage' => $percentage,
                'amount' => $charging['amount'],
                'payment_path' => $paymentPath,
            ]);
        }, 3);
    }

    /**
     * Find a rental belonging to an approved renter.
     */
    private function getRenterRental(
        User $user,
        string $rentalId
    ): Rental {
        if (
            $user->role !== 'renter' ||
            $user->registration_status !== 'Approve'
        ) {
            throw new AuthorizationException(
                'Only approved renters can access charging.'
            );
        }

        return Rental::with(['vehicle', 'garage'])
            ->where('rental_id', $rentalId)
            ->whereHas('vehicle', function ($query) use ($user) {
                $query->where(
                    'user_id',
                    $user->user_id
                );
            })
            ->firstOrFail();
    }

    /**
     * Check vehicle, garage, and rental period.
     */
    private function ensureChargingEligible(
        Rental $rental
    ): void {
        if (
            $rental->vehicle->type !== 'electric_car' ||
            $rental->garage->garage_type !== 'electric_car'
        ) {
            throw ValidationException::withMessages([
                'rental_id' =>
                    'Charging is only available for electric car rentals.',
            ]);
        }

        if (
            $rental->vehicle->battery_capacity === null ||
            (float) $rental->vehicle->battery_capacity <= 0 ||
            $rental->garage->electricity_rate_per_kwh === null ||
            (int) $rental->garage->electricity_rate_per_kwh <= 0
        ) {
            throw ValidationException::withMessages([
                'rental_id' =>
                    'Battery capacity or electricity rate is invalid.',
            ]);
        }

        $startDate = CarbonImmutable::parse(
            $rental->start_date
        )->startOfDay();

        $durationDays = $rental->rental_type === 'monthly'
            ? $startDate->daysInMonth
            : (int) $rental->duration;

        $endDate = $startDate->addDays($durationDays);

        $today = CarbonImmutable::today();

        // The end date is exclusive.
        if (
            $today->lt($startDate) ||
            $today->gte($endDate)
        ) {
            throw ValidationException::withMessages([
                'rental_id' =>
                    'Charging is only allowed during an active rental.',
            ]);
        }
    }

    /**
     * Calculate the energy and total charging amount.
     */
    private function calculateCharging(
        Rental $rental,
        float $batteryPercentage
    ): array {
        if (
            !is_finite($batteryPercentage) ||
            $batteryPercentage < 0 ||
            $batteryPercentage >= 100 ||
            round($batteryPercentage, 2) != $batteryPercentage
        ) {
            throw ValidationException::withMessages([
                'current_battery_percentage' =>
                    'Battery percentage must be between 0 and 99.99 with up to two decimal places.',
            ]);
        }

        $capacity = (float) $rental->vehicle
            ->battery_capacity;

        $electricityRate = (int) $rental->garage
            ->electricity_rate_per_kwh;

        $energyNeeded = (
            (100 - $batteryPercentage) / 100
        ) * $capacity;

        // Charging amount is stored as whole IDR.
        $amount = (int) round(
            $energyNeeded * $electricityRate
        );

        if ($amount < 1) {
            throw ValidationException::withMessages([
                'current_battery_percentage' =>
                    'The calculated charging amount must be at least Rp1.',
            ]);
        }

        return [
            'rental_id' => $rental->rental_id,
            'current_battery_percentage' => $batteryPercentage,
            'battery_capacity' => $capacity,
            'electricity_rate_per_kwh' => $electricityRate,
            'energy_needed_kwh' => round($energyNeeded, 4),
            'amount' => $amount,
        ];
    }
}
