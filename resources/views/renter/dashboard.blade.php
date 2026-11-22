@php
    // Display-only: rentals whose period covers today (end date is exclusive).
    // Monthly rentals use the starting month's day count, as in RentalService.
    $today = \Carbon\CarbonImmutable::today();
    $activeRentals = $rentals->filter(function ($rental) use ($today) {
        $start = \Carbon\CarbonImmutable::parse($rental->start_date)->startOfDay();
        $days = $rental->rental_type === 'monthly' ? $start->daysInMonth : (int) $rental->duration;

        return $today->gte($start) && $today->lt($start->addDays($days));
    });
    $upcomingCount = $rentals->filter(fn ($rental) => \Carbon\CarbonImmutable::parse($rental->start_date)->gt($today))->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Dashboard" description="Welcome back, {{ Auth::user()->name }}.">
            <x-slot name="actions">
                <x-link-button :href="route('rentals.create')" variant="primary">Create Rental</x-link-button>
                <x-link-button :href="route('vehicles.create')">Add Vehicle</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Summary -->
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="bg-white border border-neutral-200 rounded-md p-5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">My Vehicles</dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">{{ $vehicles->count() }}</dd>
                    <a href="{{ route('vehicles.index') }}" class="mt-3 inline-block text-sm font-medium underline hover:text-[#333333]">View vehicles</a>
                </div>
                <div class="bg-white border border-neutral-200 rounded-md p-5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">My Rentals</dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">{{ $rentals->count() }}</dd>
                    <a href="{{ route('rentals.index') }}" class="mt-3 inline-block text-sm font-medium underline hover:text-[#333333]">View rental history</a>
                </div>
                <div class="bg-white border border-neutral-200 rounded-md p-5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Active Today / Upcoming</dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">{{ $activeRentals->count() }} / {{ $upcomingCount }}</dd>
                    <p class="mt-3 text-sm text-[#666666]">As of {{ $today->format('d M Y') }}</p>
                </div>
            </dl>

            <!-- Active Rentals -->
            <section aria-labelledby="active-heading" class="bg-white border border-neutral-200 rounded-md">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 id="active-heading" class="font-semibold text-black">Active Rentals</h3>
                </div>

                <div class="p-5">
                    @if ($vehicles->isEmpty())
                        <x-empty-state title="Register a vehicle first" message="You need at least one vehicle before you can rent a garage.">
                            <x-link-button :href="route('vehicles.create')" variant="primary">Add Vehicle</x-link-button>
                        </x-empty-state>
                    @elseif ($activeRentals->isEmpty())
                        <x-empty-state title="No rental is active today" message="Create a rental to reserve a compatible garage.">
                            <x-link-button :href="route('rentals.create')" variant="primary">Create Rental</x-link-button>
                        </x-empty-state>
                    @else
                        <ul class="divide-y divide-neutral-200">
                            @foreach ($activeRentals as $rental)
                                @php
                                    $canCharge = $rental->vehicle->type === 'electric_car'
                                        && $rental->garage->garage_type === 'electric_car';
                                @endphp
                                <li class="py-4 first:pt-0 last:pb-0 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-semibold">{{ $rental->garage->garage_code }}</span>
                                            <x-status-label status="Active" />
                                        </div>
                                        <p class="mt-1 text-sm text-[#333333]">
                                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}
                                            · <x-type-label kind="rental" :value="$rental->rental_type" />
                                            · until <x-rental-period :rental="$rental" />
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @if ($canCharge)
                                            <x-link-button :href="route('charging.create', $rental->rental_id)" variant="primary">Charge EV</x-link-button>
                                        @endif
                                        <x-link-button :href="route('rentals.show', $rental->rental_id)">Details</x-link-button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <!-- Vehicles -->
            <section aria-labelledby="vehicles-heading" class="bg-white border border-neutral-200 rounded-md">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between gap-4">
                    <h3 id="vehicles-heading" class="font-semibold text-black">My Vehicles</h3>
                    <a href="{{ route('vehicles.index') }}" class="text-sm font-medium underline hover:text-[#333333]">View all</a>
                </div>

                <div class="p-5">
                    @if ($vehicles->isEmpty())
                        <p class="text-sm text-[#666666]">No vehicles registered yet.</p>
                    @else
                        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($vehicles->take(6) as $vehicle)
                                <li>
                                    <a href="{{ route('vehicles.show', $vehicle->vehicle_id) }}" class="block border border-neutral-200 rounded-md p-4 hover:border-black focus:outline-none focus:ring-2 focus:ring-black">
                                        <div class="text-xs uppercase tracking-wide text-[#666666]"><x-type-label kind="vehicle" :value="$vehicle->type" /></div>
                                        <div class="mt-1 font-semibold">{{ $vehicle->brand }} {{ $vehicle->model }}</div>
                                        <div class="text-sm font-mono text-[#333333]">{{ $vehicle->license_plate ?? 'No plate' }}</div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
