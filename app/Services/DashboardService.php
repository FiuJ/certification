<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get dashboard summary statistics.
     */
    public function getSummary(): array
    {
        return [
            'total_garages' => Garage::count(),

            'total_renters' => User::where(
                'role',
                'renter'
            )->count(),

            'total_rentals' => Rental::count(),

            'total_rental_revenue' =>
                (int) Rental::sum('rental_amount'),
        ];
    }

    /**
     * Get monthly rental results for a selected year.
     */
    public function getMonthlyRentalReport(
        int $year
    ): array {
        $yearStart = CarbonImmutable::create(
            $year,
            1,
            1
        )->startOfDay();

        $yearEnd = $yearStart->addYear();

        $rentals = $this->getRentalPeriods(
            $yearStart,
            $yearEnd
        );

        $report = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = CarbonImmutable::create(
                $year,
                $month,
                1
            )->startOfDay();

            $monthEnd = $monthStart->addMonth();

            $report[] = [
                'month' => $month,
                'month_name' => $monthStart->format('F'),
                ...$this->aggregatePeriod(
                    $rentals,
                    $monthStart,
                    $monthEnd
                ),
            ];
        }

        return $report;
    }

    /**
     * Get daily rental results for a selected month.
     */
    public function getDailyRentalReport(
        int $year,
        int $month
    ): array {
        $monthStart = CarbonImmutable::create(
            $year,
            $month,
            1
        )->startOfDay();

        $monthEnd = $monthStart->addMonth();

        $rentals = $this->getRentalPeriods(
            $monthStart,
            $monthEnd
        );

        $report = [];

        for (
            $day = 1;
            $day <= $monthStart->daysInMonth;
            $day++
        ) {
            $date = $monthStart->addDays($day - 1);

            $report[] = [
                'date' => $date->toDateString(),
                ...$this->aggregatePeriod(
                    $rentals,
                    $date,
                    $date->addDay()
                ),
            ];
        }

        return $report;
    }

    /**
     * Get rental results grouped by garage type.
     */
    public function getGarageTypeReport(): array
    {
        $garageCounts = Garage::query()
            ->select('garage_type')
            ->selectRaw('COUNT(*) AS garage_count')
            ->groupBy('garage_type')
            ->pluck('garage_count', 'garage_type');

        $rentalResults = Rental::query()
            ->join(
                'garages',
                'rentals.garage_id',
                '=',
                'garages.garage_id'
            )
            ->select('garages.garage_type')
            ->selectRaw(
                'COUNT(*) AS total_rentals,
                 COALESCE(SUM(rentals.rental_amount), 0)
                 AS rental_revenue'
            )
            ->groupBy('garages.garage_type')
            ->get()
            ->keyBy('garage_type');

        $report = [];

        foreach (
            ['bicycle', 'car', 'electric_car']
            as $garageType
        ) {
            $result = $rentalResults->get($garageType);

            $report[] = [
                'garage_type' => $garageType,
                'total_garages' => (int) (
                    $garageCounts->get($garageType, 0)
                ),
                'total_rentals' => (int) (
                    $result?->total_rentals ?? 0
                ),
                'rental_revenue' => (int) (
                    $result?->rental_revenue ?? 0
                ),
            ];
        }

        return $report;
    }

    /**
     * Get rental results grouped by renter.
     */
    public function getRenterReport(): array
    {
        return Rental::query()
            ->join(
                'vehicles',
                'rentals.vehicle_id',
                '=',
                'vehicles.vehicle_id'
            )
            ->join(
                'users',
                'vehicles.user_id',
                '=',
                'users.user_id'
            )
            ->where('users.role', 'renter')
            ->select(
                'users.user_id',
                'users.name',
                'users.email'
            )
            ->selectRaw(
                'COUNT(rentals.rental_id) AS total_rentals,
                 COALESCE(SUM(rentals.rental_amount), 0)
                 AS rental_revenue'
            )
            ->groupBy(
                'users.user_id',
                'users.name',
                'users.email'
            )
            ->orderByDesc('rental_revenue')
            ->get()
            ->map(fn ($result) => [
                'user_id' => $result->user_id,
                'name' => $result->name,
                'email' => $result->email,
                'total_rentals' =>
                    (int) $result->total_rentals,
                'rental_revenue' =>
                    (int) $result->rental_revenue,
            ])
            ->all();
    }

    /**
     * Get rentals overlapping the requested period.
     *
     * The end date is exclusive.
     */
    private function getRentalPeriods(
        CarbonImmutable $from,
        CarbonImmutable $until
    ): Collection {
        $rentals = Rental::query()
            ->where(
                'start_date',
                '<',
                $until->toDateString()
            )
            ->whereRaw(
                "DATE_ADD(
                    start_date,
                    INTERVAL IF(
                        rental_type = 'monthly',
                        DAY(LAST_DAY(start_date)),
                        duration
                    ) DAY
                ) > ?",
                [$from->toDateString()]
            )
            ->get([
                'start_date',
                'rental_type',
                'duration',
                'rental_amount',
            ]);

        return $rentals->map(function (Rental $rental) {
            $startDate = CarbonImmutable::parse(
                $rental->start_date
            )->startOfDay();

            $durationDays =
                $rental->rental_type === 'monthly'
                    ? $startDate->daysInMonth
                    : (int) $rental->duration;

            return [
                'start' => $startDate,
                'end' => $startDate->addDays(
                    $durationDays
                ),
                'amount' =>
                    (int) $rental->rental_amount,
            ];
        });
    }

    /**
     * Aggregate rental activity for one date range.
     */
    private function aggregatePeriod(
        Collection $rentals,
        CarbonImmutable $from,
        CarbonImmutable $until
    ): array {
        // Rentals starting within this period.
        $newRentals = $rentals->filter(
            fn (array $rental) =>
                $rental['start']->gte($from) &&
                $rental['start']->lt($until)
        );

        // Rentals occupying any part of this period.
        $overlappingRentals = $rentals->filter(
            fn (array $rental) =>
                $rental['start']->lt($until) &&
                $rental['end']->gt($from)
        );

        return [
            'new_rentals' => $newRentals->count(),

            'rentals_in_period' =>
                $overlappingRentals->count(),

            // Revenue is assigned to the start date only.
            'rental_revenue' =>
                (int) $newRentals->sum('amount'),
        ];
    }
}
