<?php

namespace Database\Seeders;

use App\Models\ChargingRecord;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class GarageDemoSeeder extends Seeder
{
    /** name => [role, registration_status]. List order drives the NIK and phone numbers. */
    private const USERS = [
        'Admin' => ['admin', null],
        'UserPending' => ['renter', 'Pending'],
        'UserReject' => ['renter', 'Reject'],
        'UserRentEv' => ['renter', 'Approve'],
        'UserEvNormal' => ['renter', 'Approve'],
        'UserRentCar' => ['renter', 'Approve'],
        'UserRentMotorcycle' => ['renter', 'Approve'],
        'UserRentBicycle' => ['renter', 'Approve'],
        'UserRentyMonthly' => ['renter', 'Approve'],
        'UserExtended' => ['renter', 'Approve'],
        'UserExpiredEv' => ['renter', 'Approve'],
        'UserFutureRent' => ['renter', 'Approve'],
    ];

    /** garage_type => [code prefix, daily rate, electricity rate per kWh]. */
    private const GARAGES = [
        'bicycle' => ['S', 10000, null],
        'car' => ['C', 25000, null],
        'electric_car' => ['E', 40000, 2500],
    ];

    /** Only applied when a garage is first created, so later manual status changes survive reruns. */
    private const OCCUPIED_ON_FIRST_SEED = ['E001', 'C001', 'C002', 'C003', 'S001', 'S002', 'S003'];

    /** owner name => [type, license plate, brand, model, battery capacity kWh]. */
    private const VEHICLES = [
        'UserRentEv' => ['electric_car', 'DEMO-EV-01', 'Demo', 'Electric 60', 60.00],
        'UserEvNormal' => ['electric_car', 'DEMO-EV-02', 'Demo', 'Electric 50', 50.00],
        'UserRentCar' => ['car', 'DEMO-CAR-01', 'Demo', 'Normal Car', null],
        'UserRentMotorcycle' => ['motorcycle', 'DEMO-MOTO-01', 'Demo', 'Motorcycle', null],
        'UserRentBicycle' => ['bicycle', null, 'Demo', 'Bicycle', null],
        'UserRentyMonthly' => ['car', 'DEMO-CAR-02', 'Demo', 'Monthly Car', null],
        'UserExtended' => ['bicycle', null, 'Demo', 'Extended Bicycle', null],
        'UserExpiredEv' => ['electric_car', 'DEMO-EV-03', 'Demo', 'Expired Electric', 45.00],
        'UserFutureRent' => ['car', 'DEMO-CAR-03', 'Demo', 'Future Car', null],
    ];

    /**
     * key => [owner, garage code, rental type, start offset in days, duration, original rental key].
     *
     * Daily rentals of 11 days occupy the seed date through seed date + 10, inclusive.
     */
    private const RENTALS = [
        'rent-ev' => ['UserRentEv', 'E001', 'daily', 0, 11, null],
        'rent-ev-normal' => ['UserEvNormal', 'C001', 'daily', 0, 11, null],
        'rent-car' => ['UserRentCar', 'C002', 'daily', 0, 11, null],
        'rent-motorcycle' => ['UserRentMotorcycle', 'S001', 'daily', 0, 11, null],
        'rent-bicycle' => ['UserRentBicycle', 'S002', 'daily', 0, 11, null],
        'rent-monthly' => ['UserRentyMonthly', 'C003', 'monthly', 0, null, null],
        'extended-original' => ['UserExtended', 'S003', 'daily', -10, 10, null],
        'extended-extension' => ['UserExtended', 'S003', 'extension', 0, 11, 'extended-original'],
        'expired-ev' => ['UserExpiredEv', 'E003', 'daily', -20, 11, null],
        'future-rent' => ['UserFutureRent', 'C004', 'daily', 10, 11, null],
    ];

    private const CHARGING_BATTERY_PERCENTAGE = 50.0;

    /** Committed sample image, so the seeder does not need GD to draw one. */
    private const SAMPLE_PROOF = 'seeders/files/payment-proof.png';

    /**
     * Seed demonstration data without touching the application's business logic.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('GarageDemoSeeder may only run in local/testing environments.');
        }

        $proofs = $this->ensureDemoPaymentProofs();

        $anchor = DB::transaction(function () use ($proofs): CarbonImmutable {
            $anchor = $this->originalSeedDate();
            $users = $this->seedUsers();
            $garages = $this->seedGarages();
            $vehicles = $this->seedVehicles($users);
            $rentals = $this->seedRentals($anchor, $vehicles, $garages, $proofs['rental']);
            $this->seedChargingRecord($rentals['rent-ev'], $proofs['charging']);

            return $anchor;
        }, 3);

        $this->command?->info('Garage demo data is ready. Initial seed date: '.$anchor->toDateString());
        $this->command?->info('12 accounts; 30 garages; 9 vehicles; 10 rentals (including 1 extension); 1 charging record.');
        $this->command?->warn('Demo-only credentials: every NEW account uses password: password');
        $this->command?->warn('Existing demo account passwords and manually changed registration statuses are preserved.');
    }

    /**
     * Recover the first run's seed date from existing fixtures, so reruns never shift rental history.
     */
    private function originalSeedDate(): CarbonImmutable
    {
        $anchor = null;

        foreach (self::RENTALS as $key => [, , , $offset]) {
            $rental = Rental::query()->find($this->fixtureUuid('rental:'.$key));

            if ($rental === null) {
                continue;
            }

            $candidate = CarbonImmutable::parse($rental->start_date)->startOfDay()->subDays($offset);

            if ($anchor !== null && ! $candidate->isSameDay($anchor)) {
                throw new RuntimeException('Existing demo rental dates disagree. Refusing to shift seeded history.');
            }

            $anchor = $candidate;
        }

        return $anchor ?? CarbonImmutable::today('Asia/Jakarta');
    }

    /**
     * Create missing demo users; existing passwords and approval statuses are left alone.
     */
    private function seedUsers(): array
    {
        $users = [];

        foreach (array_keys(self::USERS) as $number => $name) {
            [$role, $registrationStatus] = self::USERS[$name];
            $email = strtolower($name).'@garage.test';
            $nik = $role === 'renter' ? sprintf('999900000000%04d', $number) : null;

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                // forceFill keeps the original direct assignments, independent of the model's $fillable.
                $user = (new User)->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'nik' => $nik,
                    'phone' => sprintf('08120000%04d', $number),
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'registration_status' => $registrationStatus,
                ]);
                $user->save();
            } elseif ($user->name !== $name || $user->role !== $role || ($nik !== null && $user->nik !== $nik)) {
                throw new RuntimeException("Account {$email} exists but is not the expected demo identity.");
            }

            $users[$name] = $user;
        }

        return $users;
    }

    /**
     * Create garages 001-010 for each type (S = bicycle/motorcycle, C = car, E = electric car).
     */
    private function seedGarages(): array
    {
        $garages = [];

        foreach (self::GARAGES as $type => [$prefix, $dailyRate, $electricityRate]) {
            for ($number = 1; $number <= 10; $number++) {
                $code = $prefix.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
                $garage = Garage::query()->where('garage_code', $code)->first();

                if ($garage === null) {
                    $garage = (new Garage)->forceFill([
                        'garage_code' => $code,
                        'garage_type' => $type,
                        'daily_rate' => $dailyRate,
                        'electricity_rate_per_kwh' => $electricityRate,
                        'status' => in_array($code, self::OCCUPIED_ON_FIRST_SEED, true) ? 'used' : 'available',
                    ]);
                    $garage->save();
                } elseif (
                    $garage->garage_type !== $type
                    || (int) $garage->daily_rate !== $dailyRate
                    || ($garage->electricity_rate_per_kwh === null ? null : (int) $garage->electricity_rate_per_kwh) !== $electricityRate
                ) {
                    throw new RuntimeException("Garage {$code} already exists with conflicting type or rates.");
                }

                $garages[$code] = $garage;
            }
        }

        return $garages;
    }

    /**
     * Create one vehicle for each approved renter used in a rental scenario.
     */
    private function seedVehicles(array $users): array
    {
        $vehicles = [];

        foreach (self::VEHICLES as $name => [$type, $plate, $brand, $model, $capacity]) {
            $id = $this->fixtureUuid('vehicle:'.$name);
            $userId = $users[$name]->user_id;
            $vehicle = Vehicle::query()->find($id);

            if ($vehicle === null) {
                if ($plate !== null && Vehicle::query()->where('license_plate', $plate)->exists()) {
                    throw new RuntimeException("License plate {$plate} is already used by a different vehicle.");
                }

                $vehicle = (new Vehicle)->forceFill([
                    'vehicle_id' => $id,
                    'user_id' => $userId,
                    'type' => $type,
                    'license_plate' => $plate,
                    'brand' => $brand,
                    'model' => $model,
                    'battery_capacity' => $capacity,
                ]);
                $vehicle->save();
            } elseif (
                $vehicle->user_id !== $userId
                || $vehicle->type !== $type
                || $vehicle->license_plate !== $plate
                || $vehicle->brand !== $brand
                || $vehicle->model !== $model
            ) {
                throw new RuntimeException("Vehicle for {$name} conflicts with the existing demo fixture.");
            }

            $vehicles[$name] = $vehicle;
        }

        return $vehicles;
    }

    /**
     * Create rentals for each demo scenario: active daily, EV in electric and normal garages,
     * monthly, an extension, an expired EV rental, and a future rental.
     */
    private function seedRentals(CarbonImmutable $anchor, array $vehicles, array $garages, string $paymentPath): array
    {
        $rentals = [];

        foreach (self::RENTALS as $key => [$name, $code, $type, $startOffset, $duration, $originalKey]) {
            $vehicle = $vehicles[$name];
            $garage = $garages[$code];
            $start = $anchor->addDays($startOffset);
            $id = $this->fixtureUuid('rental:'.$key);
            $originalId = $originalKey === null ? null : $this->fixtureUuid('rental:'.$originalKey);

            $this->ensureVehicleGarageCompatibility($vehicle, $garage);
            $this->validateDemoDuration($start, $type, $duration);

            $rental = Rental::query()->find($id);

            if ($rental !== null) {
                if (
                    $rental->vehicle_id !== $vehicle->vehicle_id
                    || $rental->garage_id !== $garage->garage_id
                    || $rental->original_rental_id !== $originalId
                    || $rental->rental_type !== $type
                    || $rental->start_date->toDateString() !== $start->toDateString()
                    || $rental->duration !== $duration
                ) {
                    throw new RuntimeException("Existing rental {$key} differs from the demo fixture. No data was overwritten.");
                }
            } else {
                $this->ensureGarageDoesNotOverlap($garage, $start, $this->rentalEnd($start, $type, $duration), $id);

                $rental = (new Rental)->forceFill([
                    'rental_id' => $id,
                    'vehicle_id' => $vehicle->vehicle_id,
                    'garage_id' => $garage->garage_id,
                    'original_rental_id' => $originalId,
                    'rental_type' => $type,
                    'start_date' => $start->toDateString(),
                    'duration' => $duration,
                    'rental_amount' => $this->calculateDemoRentalAmount($garage, $start, $type, $duration),
                    'payment_path' => $paymentPath,
                ]);
                $rental->save();
            }

            $rentals[$key] = $rental;
        }

        return $rentals;
    }

    /**
     * Fail fast if a fixture parks a vehicle in a garage type the app would reject.
     */
    private function ensureVehicleGarageCompatibility(Vehicle $vehicle, Garage $garage): void
    {
        $allowedTypes = match ($vehicle->type) {
            'bicycle', 'motorcycle' => ['bicycle'],
            'car' => ['car'],
            'electric_car' => ['car', 'electric_car'],
            default => [],
        };

        if (! in_array($garage->garage_type, $allowedTypes, true)) {
            throw new RuntimeException('A demo vehicle has an incompatible garage assignment.');
        }
    }

    /**
     * Mirror RentalService's duration limits so fixtures stay valid business data.
     */
    private function validateDemoDuration(CarbonImmutable $start, string $type, ?int $duration): void
    {
        $valid = match ($type) {
            'monthly' => $duration === null,
            'daily' => $duration !== null && $duration >= 1 && $duration <= $start->daysInMonth - 1,
            'extension' => $duration !== null && $duration >= 1 && $duration <= 65535,
            default => false,
        };

        if (! $valid) {
            throw new RuntimeException('Invalid demo rental duration or type.');
        }
    }

    /**
     * Mirror existing pricing: a full calendar month costs 25 days' rate, leftover days are daily.
     */
    private function calculateDemoRentalAmount(Garage $garage, CarbonImmutable $start, string $type, ?int $duration): int
    {
        $rate = (int) $garage->daily_rate;

        if ($type === 'monthly') {
            return $rate * 25;
        }

        if ($type === 'daily') {
            return $rate * $duration;
        }

        $remaining = $duration;
        $cursor = $start;
        $total = 0;

        while ($remaining >= $cursor->daysInMonth) {
            $days = $cursor->daysInMonth;
            $total += 25 * $rate;
            $remaining -= $days;
            $cursor = $cursor->addDays($days);
        }

        return $total + $remaining * $rate;
    }

    /**
     * Exclusive end date: a monthly rental lasts the number of days in its starting month.
     */
    private function rentalEnd(CarbonImmutable $start, string $type, ?int $duration): CarbonImmutable
    {
        return $start->addDays($type === 'monthly' ? $start->daysInMonth : (int) $duration);
    }

    /**
     * Refuse to insert a fixture that would double-book a garage already used by other data.
     */
    private function ensureGarageDoesNotOverlap(Garage $garage, CarbonImmutable $start, CarbonImmutable $end, string $fixtureRentalId): void
    {
        $candidates = Rental::query()
            ->where('garage_id', $garage->garage_id)
            ->where('rental_id', '!=', $fixtureRentalId)
            ->where('start_date', '<', $end->toDateString())
            ->get();

        foreach ($candidates as $existing) {
            $existingStart = CarbonImmutable::parse($existing->start_date)->startOfDay();
            $existingEnd = $this->rentalEnd($existingStart, $existing->rental_type, $existing->duration);

            if ($start->lt($existingEnd) && $existingStart->lt($end)) {
                throw new RuntimeException("Garage {$garage->garage_code} has a conflicting rental. No demo rental was inserted.");
            }
        }
    }

    /**
     * Create one paid EV charging record, priced from the energy needed to reach 100%.
     */
    private function seedChargingRecord(Rental $rental, string $paymentPath): void
    {
        $id = $this->fixtureUuid('charging:rent-ev:50-percent');
        $record = ChargingRecord::query()->find($id);

        if ($record !== null) {
            if ($record->rental_id !== $rental->rental_id || (float) $record->current_battery_percentage !== self::CHARGING_BATTERY_PERCENTAGE) {
                throw new RuntimeException('Existing demo charging record does not match its fixture.');
            }

            return;
        }

        $rental->loadMissing(['vehicle', 'garage']);

        if ($rental->vehicle->type !== 'electric_car' || $rental->garage->garage_type !== 'electric_car') {
            throw new RuntimeException('Demo charging requires an EV in an electric garage.');
        }

        $energyNeeded = (100 - self::CHARGING_BATTERY_PERCENTAGE) / 100 * (float) $rental->vehicle->battery_capacity;
        $amount = (int) round($energyNeeded * (int) $rental->garage->electricity_rate_per_kwh);

        if ($amount < 1) {
            throw new RuntimeException('Demo charging amount must be positive.');
        }

        (new ChargingRecord)->forceFill([
            'charging_record_id' => $id,
            'rental_id' => $rental->rental_id,
            'current_battery_percentage' => self::CHARGING_BATTERY_PERCENTAGE,
            'amount' => $amount,
            'payment_path' => $paymentPath,
        ])->save();
    }

    /**
     * Copy the committed sample image to the private disk; existing proofs are never overwritten.
     */
    private function ensureDemoPaymentProofs(): array
    {
        $paths = [
            'rental' => 'garage-demo/rental-demo-proof.png',
            'charging' => 'garage-demo/charging-demo-proof.png',
        ];

        $source = database_path(self::SAMPLE_PROOF);

        if (! is_file($source)) {
            throw new RuntimeException('Missing sample payment proof: database/'.self::SAMPLE_PROOF);
        }

        $disk = Storage::disk('local');

        foreach ($paths as $path) {
            if (! $disk->exists($path) && ! $disk->put($path, file_get_contents($source))) {
                throw new RuntimeException("Unable to create private demo proof: {$path}");
            }
        }

        return $paths;
    }

    /**
     * UUIDv5 ids stay identical across runs, which is what makes the seeder idempotent.
     */
    private function fixtureUuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'fiuj-certification/garage-demo/'.$key)->toString();
    }
}
