<?php

namespace Tests\Unit;

use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class RentalDateTest extends TestCase
{
    private function endDate(string $start, string $type, ?int $duration): string
    {
        $method = new ReflectionMethod(RentalService::class, 'calculateEndDate');

        return $method->invoke(
            new RentalService(),
            CarbonImmutable::parse($start),
            $type,
            $duration
        )->toDateString();
    }

    private function assertInvalidDuration(string $start, int $duration): void
    {
        try {
            $this->endDate($start, 'daily', $duration);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('duration', $exception->errors());

            return;
        }

        $this->fail('Expected invalid daily duration to be rejected.');
    }

    public function test_daily_rental_for_five_days(): void 
    {
        $this->assertSame('2026-11-15', $this->endDate('2026-11-10', 'daily', 5));
    }

    public function test_daily_rental_can_cross_a_month_boundary(): void 
    {
        $this->assertSame('2026-12-05', $this->endDate('2026-11-30', 'daily', 5));
    }

    public function test_negative_daily_duration_is_rejected(): void 
    {
        $this->assertInvalidDuration('2026-11-10', -1);
    }

    public function test_daily_duration_above_monthly_limit_is_rejected(): void 
    {
        $this->assertInvalidDuration('2026-11-10', 30);
    }

    public function test_unknown_rental_type_is_rejected(): void 
    {
        $this->expectException(ValidationException::class);

        $this->endDate('2026-11-10', 'weekly', 5);
    }

    public function test_extension_type_is_not_a_new_rental_type(): void 
    {
        $this->expectException(ValidationException::class);

        $this->endDate('2026-11-10', 'extension', 5);
    }

    public function test_minimum_daily_duration_boundary(): void 
    {
        $this->assertInvalidDuration('2026-11-10', 0);
        $this->assertSame('2026-11-11', $this->endDate('2026-11-10', 'daily', 1));
        $this->assertSame('2026-11-12', $this->endDate('2026-11-10', 'daily', 2));
    }

    public function test_november_daily_maximum_boundary(): void 
    {
        $this->assertSame('2026-12-08', $this->endDate('2026-11-10', 'daily', 28));
        $this->assertSame('2026-12-09', $this->endDate('2026-11-10', 'daily', 29));
        $this->assertInvalidDuration('2026-11-10', 30);
    }

    public function test_non_leap_february_daily_maximum_boundary(): void 
    {
        $this->assertSame('2026-03-11', $this->endDate('2026-02-13', 'daily', 26));
        $this->assertSame('2026-03-12', $this->endDate('2026-02-13', 'daily', 27));
        $this->assertInvalidDuration('2026-02-13', 28);
    }

    public function test_leap_february_daily_maximum_boundary(): void 
    {
        $this->assertSame('2028-03-11', $this->endDate('2028-02-13', 'daily', 27));
        $this->assertSame('2028-03-12', $this->endDate('2028-02-13', 'daily', 28));
        $this->assertInvalidDuration('2028-02-13', 29);
    }

    public function test_monthly_rental_starting_in_november(): void 
    {
        $this->assertSame('2026-12-10', $this->endDate('2026-11-10', 'monthly', null));
    }

    public function test_monthly_rental_starting_in_december(): void 
    {
        $this->assertSame('2027-01-10', $this->endDate('2026-12-10', 'monthly', null));
    }

    public function test_monthly_rental_starting_in_non_leap_february(): void 
    {
        $this->assertSame('2026-03-15', $this->endDate('2026-02-15', 'monthly', null));
    }

    public function test_monthly_rental_starting_in_leap_february(): void 
    {
        $this->assertSame('2028-03-15', $this->endDate('2028-02-15', 'monthly', null));
    }

    public function test_monthly_rental_starting_on_january_thirty_first(): void 
    {
        $this->assertSame('2026-03-03', $this->endDate('2026-01-31', 'monthly', null));
    }
}
