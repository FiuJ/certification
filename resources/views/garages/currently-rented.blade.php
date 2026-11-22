<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Currently Rented" description="Rentals occupying a garage today, {{ now()->format('d M Y') }}.">
            <x-slot name="actions">
                <x-link-button :href="route('garages.index')">All Garages</x-link-button>
                <x-link-button :href="route('admin.rentals.index')">Rentals &amp; Extensions</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($activeRentals->isEmpty())
                <x-empty-state title="No garages are rented today" message="Rentals starting in the future appear here on their start date." />
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Vehicle</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Renter</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Start Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Last Occupied Day</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Type</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($activeRentals as $rental)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-semibold text-black">{{ $rental->garage->garage_code }}</div>
                                        <div class="text-xs text-[#666666]"><x-type-label kind="garage" :value="$rental->garage->garage_type" /></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-black">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</div>
                                        <div class="text-xs text-[#666666]">
                                            <x-type-label kind="vehicle" :value="$rental->vehicle->type" />
                                            · {{ $rental->vehicle->license_plate ?? 'No plate' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-black">{{ $rental->vehicle->user->name }}</div>
                                        <div class="text-xs text-[#666666] break-all">{{ $rental->vehicle->user->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $rental->start_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap"><x-rental-period :rental="$rental" /></td>
                                    <td class="px-4 py-3">
                                        <x-type-label kind="rental" :value="$rental->rental_type" />
                                        @if ($rental->original_rental_id)
                                            <div class="text-xs text-[#666666]">Extends an earlier rental</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right"><x-rupiah :amount="$rental->rental_amount" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
