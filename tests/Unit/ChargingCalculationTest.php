<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\ChargingService;
use ReflectionMethod;
use Tests\TestCase;

class ChargingCalculationTest extends TestCase
{
    private function charging(
        float $batteryPercentage,
        float $capacity = 60.0,
        int $rate = 2500
    ): array {
        $rental = new Rental();
        $rental->rental_id = '00000000-0000-4000-8000-000000000001';

        $rental->setRelation('vehicle', new Vehicle([
            'type' => 'electric_car',
            'battery_capacity' => $capacity,
        ]));

        $rental->setRelation('garage', new Garage([
            'garage_type' => 'electric_car',
            'electricity_rate_per_kwh' => $rate,
        ]));

        $method = new ReflectionMethod(ChargingService::class, 'calculateCharging');

        return $method->invoke(new ChargingService(), $rental, $batteryPercentage);
    }

    public function test_charging_from_fifty_percent(): void // U50
    {
        $result = $this->charging(50.0);

        $this->assertSame(30.0, $result['energy_needed_kwh']);
        $this->assertSame(75000, $result['amount']);
    }

    public function test_zero_percent_is_valid(): void
    {
        $result = $this->charging(0.0);

        $this->assertSame(60.0, $result['energy_needed_kwh']);
        $this->assertSame(150000, $result['amount']);
    }

    public function test_zero_point_zero_one_percent_is_valid(): void 
    {
        $result = $this->charging(0.01);

        $this->assertEqualsWithDelta(59.994, $result['energy_needed_kwh'], 0.0001);
        $this->assertSame(149985, $result['amount']);
    }

    public function test_ninety_nine_point_ninety_nine_percent_is_valid(): void 
    {
        $result = $this->charging(99.99);

        $this->assertEqualsWithDelta(0.006, $result['energy_needed_kwh'], 0.0001);
        $this->assertSame(15, $result['amount']);
    }

    public function test_fractional_rupiah_is_rounded(): void 
    {
        $result = $this->charging(batteryPercentage: 50.0, capacity: 1.0, rate: 333);

        // 0.5 kWh × Rp333 = Rp166.5 → Rp167.
        $this->assertSame(167, $result['amount']);
    }
}
