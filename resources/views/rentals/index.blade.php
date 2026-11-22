<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="My Rentals" description="All garage rentals for your vehicles, including extensions.">
            <x-slot name="actions">
                <x-link-button :href="route('rentals.create')" variant="primary">Create Rental</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($rentals->isEmpty())
                <x-empty-state title="No rentals yet" message="Create a rental to reserve a garage for one of your vehicles.">
                    <x-link-button :href="route('rentals.create')" variant="primary">Create Rental</x-link-button>
                </x-empty-state>
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Vehicle</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Type</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Start Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Last Occupied Day</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Duration</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Amount</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Status</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($rentals as $rental)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-semibold text-black">{{ $rental->garage->garage_code }}</div>
                                        <div class="text-xs text-[#666666]"><x-type-label kind="garage" :value="$rental->garage->garage_type" /></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-black">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</div>
                                        <div class="text-xs font-mono text-[#666666]">{{ $rental->vehicle->license_plate ?? 'No plate' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-type-label kind="rental" :value="$rental->rental_type" />
                                        @if ($rental->original_rental_id)
                                            <div class="text-xs text-[#666666]">Extension of an earlier rental</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $rental->start_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap"><x-rental-period :rental="$rental" /></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap"><x-rental-period :rental="$rental" show="days" /></td>
                                    <td class="px-4 py-3 text-right"><x-rupiah :amount="$rental->rental_amount" /></td>
                                    <td class="px-4 py-3"><x-rental-period :rental="$rental" show="status" /></td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('rentals.show', $rental->rental_id) }}" class="font-medium underline hover:text-[#333333]">Details</a>
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
