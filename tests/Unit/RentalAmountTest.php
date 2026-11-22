<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Services\RentalService;
use ReflectionMethod;
use Tests\TestCase;

class RentalAmountTest extends TestCase
{
    private function amount(int $dailyRate, string $rentalType, ?int $duration): int
    {
        $method = new ReflectionMethod(RentalService::class, 'calculateAmount');

        return $method->invoke(
            new RentalService(),
            new Garage(['daily_rate' => $dailyRate]),
            $rentalType,
            $duration
        );
    }

    public function test_monthly_price_is_twenty_five_times_daily_rate(): void 
    {
        $this->assertSame(625000, $this->amount(25000, 'monthly', null));
    }

    public function test_bicycle_daily_price_for_five_days(): void 
    {
        $this->assertSame(50000, $this->amount(10000, 'daily', 5));
    }

    public function test_normal_car_daily_price_for_five_days(): void 
    {
        $this->assertSame(125000, $this->amount(25000, 'daily', 5));
    }

    public function test_ev_daily_price_for_five_days(): void 
    {
        $this->assertSame(200000, $this->amount(40000, 'daily', 5));
    }

    public function test_bicycle_monthly_price(): void 
    {
        $this->assertSame(250000, $this->amount(10000, 'monthly', null));
    }

    public function test_normal_car_monthly_price(): void 
    {
        $this->assertSame(625000, $this->amount(25000, 'monthly', null));
    }

    public function test_ev_monthly_price(): void 
    {
        $this->assertSame(1000000, $this->amount(40000, 'monthly', null));
    }

    public function test_one_day_is_charged_at_one_daily_rate(): void
    {
        $this->assertSame(25000, $this->amount(25000, 'daily', 1));
    }
}
