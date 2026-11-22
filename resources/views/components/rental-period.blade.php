@props(['rental', 'show' => 'last-day'])

@php
    // Display-only mirror of RentalService: monthly rentals occupy the
    // starting month's number of days; daily and extension rentals use
    // the stored duration. The computed end is exclusive.
    $periodStart = \Carbon\CarbonImmutable::parse($rental->start_date)->startOfDay();
    $periodDays = $rental->rental_type === 'monthly'
        ? $periodStart->daysInMonth
        : (int) $rental->duration;
    $periodEnd = $periodStart->addDays($periodDays);
    $periodToday = \Carbon\CarbonImmutable::today();
    $periodStatus = match (true) {
        $periodToday->lt($periodStart) => 'Upcoming',
        $periodToday->lt($periodEnd) => 'Active',
        default => 'Expired',
    };
@endphp

@switch($show)
    @case('status')
        <x-status-label :status="$periodStatus" {{ $attributes }} />
        @break
    @case('days')
        <span {{ $attributes }}>{{ $periodDays }} {{ Str::plural('day', $periodDays) }}</span>
        @break
    @case('range')
        <span {{ $attributes }}>{{ $periodStart->format('d M Y') }} – {{ $periodEnd->subDay()->format('d M Y') }}</span>
        @break
    @default
        <span {{ $attributes }}>{{ $periodEnd->subDay()->format('d M Y') }}</span>
@endswitch
