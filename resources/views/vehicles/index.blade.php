<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="My Vehicles" description="Vehicles you can use when creating a rental.">
            <x-slot name="actions">
                <x-link-button :href="route('vehicles.create')" variant="primary">Add Vehicle</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($vehicles->isEmpty())
                <x-empty-state title="No vehicles yet" message="Register a vehicle to start renting a garage.">
                    <x-link-button :href="route('vehicles.create')" variant="primary">Add Vehicle</x-link-button>
                </x-empty-state>
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Type</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">License Plate</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Brand</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Model</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Battery Capacity</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($vehicles as $vehicle)
                                <tr>
                                    <td class="px-4 py-3 text-black"><x-type-label kind="vehicle" :value="$vehicle->type" /></td>
                                    <td class="px-4 py-3 font-mono">{{ $vehicle->license_plate ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $vehicle->brand }}</td>
                                    <td class="px-4 py-3">{{ $vehicle->model }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        {{ $vehicle->battery_capacity !== null ? $vehicle->battery_capacity.' kWh' : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('vehicles.show', $vehicle->vehicle_id) }}" class="font-medium underline hover:text-[#333333]">Details</a>
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
