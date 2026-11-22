<?php

namespace Tests\Unit;

use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class RentalExtensionDurationTest extends TestCase
{
    private function duration(string $start, string $inclusiveEnd): int
    {
        $method = new ReflectionMethod(RentalService::class, 'calculateExtensionDuration');

        return $method->invoke(
            new RentalService(),
            CarbonImmutable::parse($start),
            $inclusiveEnd
        );
    }

    public function test_five_day_extension(): void 
    {
        $this->assertSame(5, $this->duration('2026-11-10', '2026-11-14'));
    }

    public function test_extension_end_before_start_is_rejected(): void 
    {
        $this->expectException(ValidationException::class);

        $this->duration('2026-11-10', '2026-11-09');
    }

    public function test_extension_end_equal_to_start_is_one_day(): void 
    {
        $this->assertSame(1, $this->duration('2026-11-10', '2026-11-10'));
    }
}
