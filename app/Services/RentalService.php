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
     * Get all original rentals with their extensions for administrators.
     */
    public function getAdminRentals(): Collection
    {
        return Rental::with([
            'vehicle.user',
            'garage',
            'extensions',
        ])
            ->whereNull('original_rental_id')
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Get an original rental with its extensions for administrators.
     * An extension ID resolves to its original rental.
     */
    public function getAdminRental(string $rentalId): Rental
    {
        $selectedRental = Rental::findOrFail($rentalId);

        $originalId = $selectedRental->original_rental_id
            ?? $selectedRental->rental_id;

        return Rental::with([
            'vehicle.user',
            'garage',
            'extensions.garage',
        ])
            ->where('rental_id', $originalId)
            ->firstOrFail();
    }

    /**
     * Get the latest exclusive end date of an original rental,
     * including all of its extensions.
     */
    public function getLatestEndDate(Rental $originalRental): CarbonImmutable
    {
        return collect([
            $originalRental,
            ...$originalRental->extensions,
        ])
            ->map(fn (Rental $rental) => $this->getEndDate($rental))
            ->sortByDesc(fn (CarbonImmutable $date) => $date->timestamp)
            ->first();
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
                $endDate,
                null,
                $data['garage_id']
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
     * List available garages and their rental prices.
     * This is a read-only preview; no garage is reserved.
     */
    public function quoteRental(User $user, array $data): array
    {
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

        $vehicle = $user->vehicles()
            ->where('vehicle_id', $data['vehicle_id'])
            ->firstOrFail();

        $garageType = $this->getGarageType(
            $vehicle,
            $data['garage_type'] ?? null
        );

        $garages = $this->getAvailableGarages(
            $garageType,
            $startDate,
            $endDate
        );

        return [
            'duration_days' => (int) $startDate->diffInDays(
                $endDate
            ),
            'garages' => $garages->map(function (Garage $garage) use ($data) {
                return [
                    'garage_id' => $garage->garage_id,
                    'garage_code' => $garage->garage_code,
                    'garage_type' => $garage->garage_type,
                    'daily_rate' => (int) $garage->daily_rate,
                    'electricity_rate_per_kwh' =>
                        $garage->electricity_rate_per_kwh === null
                            ? null
                            : (int) $garage->electricity_rate_per_kwh,
                    'amount' => $this->calculateAmount(
                        $garage,
                        $data['rental_type'],
                        $data['duration'] ?? null
                    ),
                ];
            })->values()->all(),
        ];
    }


    /**
     * Preview an extension price without saving an extension.
     */
    public function quoteExtension(
        User $admin,
        string $rentalId,
        string $inclusiveEndDate
    ): array {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException(
                'Only administrators can extend rentals.'
            );
        }

        return DB::transaction(function () use (
            $rentalId, $inclusiveEndDate
        ) {
            $original = $this->getAdminRental($rentalId);

            $lastRental = collect([
                $original,
                ...$original->extensions,
            ])
                ->sortByDesc(
                    fn (Rental $rental) =>
                        $this->getEndDate($rental)->timestamp
                )
                ->first();

            $startDate = $this->getEndDate($lastRental);

            $duration = $this->calculateExtensionDuration(
                $startDate,
                $inclusiveEndDate
            );

            $endDate = $startDate->addDays($duration);

            $garage = $this->findAvailableGarage(
                $lastRental->garage->garage_type,
                $startDate,
                $endDate,
                $lastRental->garage_id
            );

            return [
                'garage_code' => $garage->garage_code,
                'daily_rate' => (int) $garage->daily_rate,
                'duration_days' => $duration,
                'amount' => $this->calculateExtensionAmount(
                    $garage,
                    $startDate,
                    $duration
                ),
            ];
        });
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
     * Find an available garage.
     *
     * New rentals may select a specific garage.
     * Extensions still prefer their previous garage.
     */
    private function findAvailableGarage(
        string $garageType,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $preferredGarageId = null,
        ?string $selectedGarageId = null
    ): Garage {
        $garages = $this->getAvailableGarages(
            $garageType,
            $startDate,
            $endDate,
            true
        );

        // Renter selected an exact garage.
        // Never silently substitute another garage.
        if ($selectedGarageId !== null) {
            $selectedGarage = $garages->firstWhere(
                'garage_id',
                $selectedGarageId
            );

            if ($selectedGarage === null) {
                throw ValidationException::withMessages([
                    'garage_id' =>
                        'The selected garage is not available for this rental period.',
                ]);
            }

            return $selectedGarage;
        }

        // Preserve current Admin extension behavior.
        if ($preferredGarageId !== null) {
            $preferredGarage = $garages->firstWhere(
                'garage_id',
                $preferredGarageId
            );

            if ($preferredGarage !== null) {
                return $preferredGarage;
            }
        }

        $garage = $garages->first();

        if ($garage === null) {
            throw ValidationException::withMessages([
                'garage_id' =>
                    'No compatible garage is available for the selected period.',
            ]);
        }

        return $garage;
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


    /**
     * Find all garages available during the requested period.
     * End dates are exclusive.
     */
    private function getAvailableGarages(
        string $garageType,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        bool $lock = false
    ): Collection {
        $garageQuery = Garage::where(
            'garage_type',
            $garageType
        )->orderBy('garage_code');

        if ($lock) {
            $garageQuery->lockForUpdate();
        }

        $garages = $garageQuery->get();

        if ($garages->isEmpty()) {
            return $garages;
        }

        $rentalQuery = Rental::whereIn(
            'garage_id',
            $garages->pluck('garage_id')
        )->where(
            'start_date',
            '<',
            $endDate->toDateString()
        );

        if ($lock) {
            $rentalQuery->lockForUpdate();
        }

        $existingRentals = $rentalQuery
            ->get()
            ->groupBy('garage_id');

        return $garages->filter(
            function (Garage $garage) use (
                $existingRentals,
                $startDate
            ) {
                foreach (
                    $existingRentals->get($garage->garage_id, [])
                    as $rental
                ) {
                    $existingEndDate = $this->getEndDate(
                        $rental
                    );

                    if ($startDate->lt($existingEndDate)) {
                        return false;
                    }
                }

                return true;
            }
        )->values();
    }



}
