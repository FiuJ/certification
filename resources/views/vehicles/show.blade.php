<x-app-layout>
    <x-slot name="header">
        <x-page-heading :title="$vehicle->brand.' '.$vehicle->model" description="Vehicle details.">
            <x-slot name="actions">
                <x-link-button :href="route('rentals.create', ['vehicle' => $vehicle->vehicle_id])" variant="primary">Rent a Garage</x-link-button>
                <x-link-button :href="route('vehicles.index')">Back to Vehicles</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="bg-white border border-neutral-200 rounded-md">
                <dl class="divide-y divide-neutral-200 text-sm">
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Type</dt>
                        <dd class="sm:col-span-2 text-black"><x-type-label kind="vehicle" :value="$vehicle->type" /></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">License plate</dt>
                        <dd class="sm:col-span-2 font-mono text-black">{{ $vehicle->license_plate ?? 'Not applicable' }}</dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Brand</dt>
                        <dd class="sm:col-span-2 text-black">{{ $vehicle->brand }}</dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Model</dt>
                        <dd class="sm:col-span-2 text-black">{{ $vehicle->model }}</dd>
                    </div>
                    @if ($vehicle->type === 'electric_car')
                        <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                            <dt class="font-medium text-[#666666]">Battery capacity</dt>
                            <dd class="sm:col-span-2 text-black tabular-nums">{{ $vehicle->battery_capacity }} kWh</dd>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Compatible garages</dt>
                        <dd class="sm:col-span-2 text-black">
                            @switch($vehicle->type)
                                @case('electric_car')
                                    Normal Car Garage or Electric Car Garage (charging available in Electric Car Garage only)
                                    @break
                                @case('car')
                                    Normal Car Garage
                                    @break
                                @default
                                    Bicycle Garage
                            @endswitch
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Registered</dt>
                        <dd class="sm:col-span-2 text-black">{{ $vehicle->created_at?->format('d M Y') }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</x-app-layout>
