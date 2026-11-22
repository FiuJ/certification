<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class RentalCompatibilityTest extends TestCase
{
    private function garageType(string $vehicleType, ?string $selectedType = null): string
    {
        $method = new ReflectionMethod(RentalService::class, 'getGarageType');

        return $method->invoke(
            new RentalService(),
            new Vehicle(['type' => $vehicleType]),
            $selectedType
        );
    }

    public function test_bicycle_uses_bicycle_garage(): void 
    {
        $this->assertSame('bicycle', $this->garageType('bicycle'));
    }

    public function test_motorcycle_uses_bicycle_garage(): void 
    {
        $this->assertSame('bicycle', $this->garageType('motorcycle'));
    }

    public function test_car_uses_normal_car_garage(): void 
    {
        $this->assertSame('car', $this->garageType('car'));
    }

    public function test_ev_can_use_normal_car_garage(): void 
    {
        $this->assertSame('car', $this->garageType('electric_car', 'car'));
    }

    public function test_ev_can_use_electric_car_garage(): void
    {
        $this->assertSame('electric_car', $this->garageType('electric_car', 'electric_car'));
    }

    public function test_bicycle_cannot_use_normal_car_garage(): void 
    {
        $this->expectException(ValidationException::class);

        $this->garageType('bicycle', 'car');
    }

    public function test_motorcycle_cannot_use_electric_garage(): void 
    {
        $this->expectException(ValidationException::class);

        $this->garageType('motorcycle', 'electric_car');
    }

    public function test_car_cannot_use_bicycle_garage(): void 
    {
        $this->expectException(ValidationException::class);

        $this->garageType('car', 'bicycle');
    }

    public function test_ev_must_select_a_garage_type(): void 
    {
        $this->expectException(ValidationException::class);

        $this->garageType('electric_car');
    }
}
