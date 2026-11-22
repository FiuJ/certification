<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Rentals &amp; Extensions" description="Original rentals with their extensions. Select a rental to extend it.">
            <x-slot name="actions">
                <x-link-button :href="route('admin.garages.currently-rented')">Currently Rented</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($rentals->isEmpty())
                <x-empty-state title="No rentals yet" message="Rentals created by renters will appear here." />
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Renter</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Vehicle</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Type</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Start Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Last Occupied Day</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Amount</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($rentals as $rental)
                                @php
                                    $lastOccupiedDate = $lastOccupiedDates[$rental->rental_id];
                                    $extensionCount = $rental->extensions->count();
                                @endphp
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <div class="text-black">{{ $rental->vehicle->user->name }}</div>
                                        <div class="text-xs text-[#666666] break-all">{{ $rental->vehicle->user->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-black">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</div>
                                        <div class="text-xs text-[#666666]">
                                            <x-type-label kind="vehicle" :value="$rental->vehicle->type" />
                                            · <span class="font-mono">{{ $rental->vehicle->license_plate ?? 'No plate' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-semibold text-black">{{ $rental->garage->garage_code }}</div>
                                        <div class="text-xs text-[#666666]"><x-type-label kind="garage" :value="$rental->garage->garage_type" /></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-type-label kind="rental" :value="$rental->rental_type" />
                                        @if ($extensionCount > 0)
                                            <div class="text-xs text-[#666666]">+ {{ $extensionCount }} {{ Str::plural('extension', $extensionCount) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $rental->start_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $lastOccupiedDate->format('d M Y') }}
                                        @if ($lastOccupiedDate->lt(today()))
                                            <div class="text-xs text-[#666666]">Ended</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-rupiah :amount="$rental->rental_amount" />
                                        @if ($extensionCount > 0)
                                            <div class="text-xs text-[#666666]">Extensions: <x-rupiah :amount="$rental->extensions->sum('rental_amount')" /></div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-link-button :href="route('admin.rentals.extension', $rental->rental_id)" variant="primary">Extend</x-link-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
