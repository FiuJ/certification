<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalService
{
    /**
     * Get rentals belonging to a renter.
     */
    public function getRentals(User $user): Collection
    {
        return Rental::whereHas('vehicle', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Get a specific rental belonging to a renter.
     */
    public function getRental(User $user, string $rentalId): Rental
    {
        return Rental::whereHas('vehicle', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })
            ->where('rental_id', $rentalId)
            ->firstOrFail();
    }

    /**
     * Create a new rental or Buy More transaction.
     */
    public function createRental(
        User $user,
        array $data,
        string $paymentPath
    ): Rental {
        if (
            $user->role !== 'renter' ||
            $user->registration_status !== 'Approve'
        ) {
            throw new AuthorizationException(
                'Only approved renters can create rentals.'
            );
        }

        $startDate = CarbonImmutable::parse($data['start_date']);

        if ($startDate->lt(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                'start_date' => 'The start date cannot be in the past.',
            ]);
        }

        $endDate = $this->calculateEndDate(
            $startDate,
            $data['rental_type'],
            $data['duration'] ?? null
        );

        return DB::transaction(function () use (
            $user,
            $data,
            $paymentPath,
            $startDate,
            $endDate
        ) {
            // Only allow the renter's own vehicle.
            $vehicle = $user->vehicles()
                ->where('vehicle_id', $data['vehicle_id'])
                ->firstOrFail();

            $garageType = $this->getGarageType(
                $vehicle,
                $data['garage_type'] ?? null
            );

            $garage = $this->findAvailableGarage(
                $garageType,
                $startDate,
                $endDate
            );

            $amount = $this->calculateAmount(
                $garage,
                $data['rental_type'],
                $data['duration'] ?? null
            );

            $rental = Rental::create([
                'vehicle_id' => $vehicle->vehicle_id,
                'garage_id' => $garage->garage_id,
                'original_rental_id' => null,
                'rental_type' => $data['rental_type'],
                'start_date' => $startDate->toDateString(),
                'duration' => $data['rental_type'] === 'daily'
                    ? $data['duration']
                    : null,
                'rental_amount' => $amount,
                'payment_path' => $paymentPath,
            ]);

            // Only mark the garage as used when occupancy starts today.
            if ($startDate->isToday()) {
                $garage->update(['status' => 'used']);
            }

            return $rental;
        }, 3);
    }

    /**
     * Extend an existing rental using the admin's inclusive end date.
     * The garage type must remain unchanged.
     */
    public function extendRental(
        User $admin,
        string $rentalId,
        array $data,
        string $paymentPath
    ): Rental {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException(
                'Only administrators can extend rentals.'
            );
        }

        return DB::transaction(function () use (
            $rentalId,
            $data,
            $paymentPath
        ) {
            $selectedRental = Rental::findOrFail($rentalId);

            $originalId = $selectedRental->original_rental_id
                ?? $selectedRental->rental_id;

            $originalRental = Rental::where(
                'rental_id',
                $originalId
            )
                ->lockForUpdate()
                ->firstOrFail();

            $rentalHistory = Rental::where(function ($query) use (
                $originalId
            ) {
                $query->where('rental_id', $originalId)
                    ->orWhere('original_rental_id', $originalId);
            })
                ->lockForUpdate()
                ->get();

            $lastRental = $rentalHistory
                ->sortByDesc(
                    fn (Rental $rental) =>
                        $this->getEndDate($rental)->timestamp
                )
                ->first();

            // A new extension starts at the previous exclusive end.
            $startDate = $this->getEndDate($lastRental);

            // The admin selects the last occupied day.
            $duration = $this->calculateExtensionDuration(
                $startDate,
                $data['end_date']
            );

            $endDateExclusive = $startDate->addDays(
                $duration
            );

            // Preserve the garage type of the last rental.
            $garageType = $lastRental->garage->garage_type;

            // Prefer the same garage, then another of the same type.
            $garage = $this->findAvailableGarage(
                $garageType,
                $startDate,
                $endDateExclusive,
                $lastRental->garage_id
            );

            $amount = $this->calculateExtensionAmount(
                $garage,
                $startDate,
                $duration
            );

            $extension = Rental::create([
                'vehicle_id' => $originalRental->vehicle_id,
                'garage_id' => $garage->garage_id,
                'original_rental_id' => $originalId,
                'rental_type' => 'extension',
                'start_date' => $startDate->toDateString(),
                'duration' => $duration,
                'rental_amount' => $amount,
                'payment_path' => $paymentPath,
            ]);

            if ($startDate->isToday()) {
                $garage->update(['status' => 'used']);
            }

            return $extension;
        }, 3);
    }

    
    /**
     * Determine the compatible garage type for a vehicle.
     */
    private function getGarageType(
        Vehicle $vehicle,
        ?string $selectedGarageType = null
    ): string {
        if ($vehicle->type === 'electric_car') {
            if (!in_array(
                $selectedGarageType,
                ['car', 'electric_car'],
                true
            )) {
                throw ValidationException::withMessages([
                    'garage_type' =>
                        'Select a normal or electric car garage.',
                ]);
            }

            return $selectedGarageType;
        }

        $garageType = match ($vehicle->type) {
            'bicycle', 'motorcycle' => 'bicycle',
            'car' => 'car',
            default => throw ValidationException::withMessages([
                'vehicle_id' => 'Invalid vehicle type.',
            ]),
        };

        if (
            $selectedGarageType !== null &&
            $selectedGarageType !== $garageType
        ) {
            throw ValidationException::withMessages([
                'garage_type' =>
                    'The selected garage type is not compatible with the vehicle.',
            ]);
        }

        return $garageType;
    }


    /**
     * Calculate the exclusive rental end date.
     */
    private function calculateEndDate(
        CarbonImmutable $startDate,
        string $rentalType,
        ?int $duration
    ): CarbonImmutable {
        if ($rentalType === 'monthly') {
            return $startDate->addDays($startDate->daysInMonth);
        }

        if ($rentalType !== 'daily') {
            throw ValidationException::withMessages([
                'rental_type' => 'Invalid rental type.',
            ]);
        }

        $maxDays = $startDate->daysInMonth - 1;

        if (
            $duration === null ||
            $duration < 1 ||
            $duration > $maxDays
        ) {
            throw ValidationException::withMessages([
                'duration' => "Duration must be between 1 and {$maxDays} days.",
            ]);
        }

        return $startDate->addDays($duration);
    }

    /**
     * Calculate the rental price in IDR.
     */
    private function calculateAmount(
        Garage $garage,
        string $rentalType,
        ?int $duration
    ): int {
        if ($rentalType === 'monthly') {
            return $garage->daily_rate * 25;
        }

        return $garage->daily_rate * $duration;
    }

    /**
     * Find a garage without overlapping rentals.
     */
    private function findAvailableGarage(
        string $garageType,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $preferredGarageId = null
    ): Garage {
        // Lock garage rows during the booking transaction.
        $garages = Garage::where('garage_type', $garageType)
            ->orderBy('garage_code')
            ->lockForUpdate()
            ->get();

        if ($garages->isEmpty()) {
            throw ValidationException::withMessages([
                'garage_id' => 'No compatible garages are available.',
            ]);
        }

        // Prefer the garage used by the previous rental.
        if ($preferredGarageId !== null) {
            $garages = $garages->sortBy(
                fn (Garage $garage) =>
                    $garage->garage_id === $preferredGarageId ? 0 : 1
            )->values();
        }

        // Get rentals that start before the requested end date.
        $existingRentals = Rental::whereIn(
            'garage_id',
            $garages->pluck('garage_id')
        )
            ->where('start_date', '<', $endDate->toDateString())
            ->lockForUpdate()
            ->get()
            ->groupBy('garage_id');

        foreach ($garages as $garage) {
            $isAvailable = true;

            foreach (
                $existingRentals->get($garage->garage_id, [])
                as $existingRental
            ) {
                $existingEndDate = $this->getEndDate(
                    $existingRental
                );

                if ($startDate->lt($existingEndDate)) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                return $garage;
            }
        }

        throw ValidationException::withMessages([
            'start_date' => 'No garage is available for the selected period.',
        ]);
    }

    
    /**
     * Calculate the exclusive end date of a saved rental.
     */
    private function getEndDate(Rental $rental): CarbonImmutable
    {
        $startDate = CarbonImmutable::parse(
            $rental->start_date
        )->startOfDay();

        if ($rental->rental_type === 'extension') {
            $duration = (int) $rental->duration;

            if ($duration < 1 || $duration > 65535) {
                throw ValidationException::withMessages([
                    'duration' => 'Invalid extension duration.',
                ]);
            }

            return $startDate->addDays($duration);
        }

        return $this->calculateEndDate(
            $startDate,
            $rental->rental_type,
            $rental->duration
        );
    }


    /**
     * Convert the admin's inclusive end date into total rental days.
     */
    private function calculateExtensionDuration(
        CarbonImmutable $startDate,
        string $inclusiveEndDate
    ): int {
        $endDateExclusive = CarbonImmutable::parse(
            $inclusiveEndDate
        )->startOfDay()->addDay();

        if ($endDateExclusive->lte($startDate)) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'The end date must be on or after the extension start date.',
            ]);
        }

        $duration = (int) $startDate->diffInDays(
            $endDateExclusive
        );

        if ($duration < 1 || $duration > 65535) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'The extension duration must be between 1 and 65535 days.',
            ]);
        }

        return $duration;
    }


    /**
     * Calculate extension pricing by full calendar-month periods
     * and any remaining daily period.
     */
    private function calculateExtensionAmount(
        Garage $garage,
        CarbonImmutable $startDate,
        int $duration
    ): int {
        $remainingDays = $duration;
        $currentDate = $startDate;
        $monthlyPeriods = 0;

        while (
            $remainingDays >= $currentDate->daysInMonth
        ) {
            $daysInPeriod = $currentDate->daysInMonth;

            $remainingDays -= $daysInPeriod;
            $monthlyPeriods++;

            $currentDate = $currentDate->addDays(
                $daysInPeriod
            );
        }

        $monthlyAmount = $monthlyPeriods
            * 25
            * (int) $garage->daily_rate;

        $dailyAmount = $remainingDays
            * (int) $garage->daily_rate;

        return $monthlyAmount + $dailyAmount;
    }


}
