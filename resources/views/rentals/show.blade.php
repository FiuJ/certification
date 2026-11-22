@php
    // Display-only period check; ChargingService re-validates eligibility on the server.
    $periodStart = \Carbon\CarbonImmutable::parse($rental->start_date)->startOfDay();
    $periodDays = $rental->rental_type === 'monthly' ? $periodStart->daysInMonth : (int) $rental->duration;
    $isActiveToday = \Carbon\CarbonImmutable::today()->betweenIncluded($periodStart, $periodStart->addDays($periodDays - 1));
    $isElectricRental = $rental->vehicle->type === 'electric_car' && $rental->garage->garage_type === 'electric_car';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Rental {{ $rental->garage->garage_code }}" description="Rental details and transaction information.">
            <x-slot name="actions">
                @if ($isElectricRental && $isActiveToday)
                    <x-link-button :href="route('charging.create', $rental->rental_id)" variant="primary">Charge EV</x-link-button>
                @endif
                @if ($isElectricRental)
                    <x-link-button :href="route('charging.index', $rental->rental_id)">Charging History</x-link-button>
                @endif
                <x-link-button :href="route('rentals.index')">Back to Rentals</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-input-error :messages="$errors->get('rental_id')" />

            <section class="bg-white border border-neutral-200 rounded-md">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-black">Rental</h3>
                    <x-rental-period :rental="$rental" show="status" />
                </div>

                <dl class="divide-y divide-neutral-200 text-sm">
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Rental type</dt>
                        <dd class="sm:col-span-2 text-black">
                            <x-type-label kind="rental" :value="$rental->rental_type" />
                            @if ($rental->originalRental)
                                <span class="text-[#666666]">— extends the rental starting {{ $rental->originalRental->start_date->format('d M Y') }}.</span>
                                <a href="{{ route('rentals.show', $rental->original_rental_id) }}" class="underline hover:text-[#333333]">View original rental</a>
                            @endif
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Period</dt>
                        <dd class="sm:col-span-2 text-black">
                            <x-rental-period :rental="$rental" show="range" />
                            <span class="text-[#666666]">(<x-rental-period :rental="$rental" show="days" />)</span>
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Garage</dt>
                        <dd class="sm:col-span-2 text-black">
                            <span class="font-mono font-semibold">{{ $rental->garage->garage_code }}</span>
                            · <x-type-label kind="garage" :value="$rental->garage->garage_type" />
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Garage daily rate</dt>
                        <dd class="sm:col-span-2 text-black"><x-rupiah :amount="$rental->garage->daily_rate" /></dd>
                    </div>
                    @if ($rental->garage->electricity_rate_per_kwh !== null)
                        <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                            <dt class="font-medium text-[#666666]">Electricity rate</dt>
                            <dd class="sm:col-span-2 text-black"><x-rupiah :amount="$rental->garage->electricity_rate_per_kwh" /> per kWh</dd>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Vehicle</dt>
                        <dd class="sm:col-span-2 text-black">
                            <a href="{{ route('vehicles.show', $rental->vehicle_id) }}" class="underline hover:text-[#333333]">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</a>
                            · <x-type-label kind="vehicle" :value="$rental->vehicle->type" />
                            · <span class="font-mono">{{ $rental->vehicle->license_plate ?? 'No plate' }}</span>
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Rental amount</dt>
                        <dd class="sm:col-span-2 text-lg font-semibold text-black"><x-rupiah :amount="$rental->rental_amount" /></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Payment proof</dt>
                        <dd class="sm:col-span-2 text-black">{{ $rental->payment_path ? 'Submitted' : 'Not submitted' }}</dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Created</dt>
                        <dd class="sm:col-span-2 text-black">{{ $rental->created_at?->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
            </section>

            @if ($rental->extensions->isNotEmpty())
                <section class="bg-white border border-neutral-200 rounded-md">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h3 class="font-semibold text-black">Extensions</h3>
                    </div>
                    <ul class="divide-y divide-neutral-200 text-sm">
                        @foreach ($rental->extensions->sortBy('start_date') as $extension)
                            <li class="px-5 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <x-rental-period :rental="$extension" show="range" />
                                    <span class="text-[#666666]">· <x-rental-period :rental="$extension" show="days" /> · <x-rupiah :amount="$extension->rental_amount" /></span>
                                </div>
                                <a href="{{ route('rentals.show', $extension->rental_id) }}" class="font-medium underline hover:text-[#333333]">Details</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($isElectricRental)
                <section class="bg-white border border-neutral-200 rounded-md px-5 py-4 text-sm text-[#333333]">
                    <h3 class="font-semibold text-black">EV Charging</h3>
                    <p class="mt-1">
                        @if ($isActiveToday)
                            Charging is available while this rental is active.
                        @else
                            Charging is only available while this rental is active ({{ $periodStart->format('d M Y') }} – {{ $periodStart->addDays($periodDays - 1)->format('d M Y') }}).
                        @endif
                    </p>
                </section>
            @endif

            <p class="text-xs text-[#666666]">
                Rentals cannot be extended by renters. Contact an administrator to request an extension.
            </p>
        </div>
    </div>
</x-app-layout>
