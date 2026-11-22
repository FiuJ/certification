<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Garage Management" description="All garages with today's occupancy status.">
            <x-slot name="actions">
                <x-link-button :href="route('garages.create')" variant="primary">Create Garages</x-link-button>
                <x-link-button :href="route('admin.garages.currently-rented')">Currently Rented</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($garages->isEmpty())
                <x-empty-state title="No garages yet" message="Create a batch of garages to make them available for rental.">
                    <x-link-button :href="route('garages.create')" variant="primary">Create Garages</x-link-button>
                </x-empty-state>
            @else
                <p class="mb-4 text-sm text-[#666666]">
                    {{ $garages->count() }} {{ Str::plural('garage', $garages->count()) }}.
                    Status reflects rentals active today; future bookings are checked when a rental is created.
                </p>

                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Code</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage Type</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Daily Rate</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Electricity Rate / kWh</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($garages as $garage)
                                <tr>
                                    <td class="px-4 py-3 font-mono font-semibold text-black">{{ $garage->garage_code }}</td>
                                    <td class="px-4 py-3"><x-type-label kind="garage" :value="$garage->garage_type" /></td>
                                    <td class="px-4 py-3 text-right"><x-rupiah :amount="$garage->daily_rate" /></td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($garage->electricity_rate_per_kwh !== null)
                                            <x-rupiah :amount="$garage->electricity_rate_per_kwh" />
                                        @else
                                            <span class="text-[#666666]">Not applicable</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><x-status-label :status="$garage->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
