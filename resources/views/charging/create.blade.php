<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Charge EV" description="Enter the current battery level to preview the charging cost.">
            <x-slot name="actions">
                <x-link-button :href="route('charging.index', $rental->rental_id)">Charging History</x-link-button>
                <x-link-button :href="route('rentals.show', $rental->rental_id)">Back to Rental</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-input-error :messages="$errors->get('rental_id')" />

            @if ($errors->has('payment_proof'))
                <div class="rounded-md border border-black bg-white px-4 py-3 text-sm" role="alert">
                    <p class="font-semibold text-black">The charging payment was not submitted.</p>
                    <x-input-error :messages="$errors->get('payment_proof')" class="mt-2" />
                    <p class="mt-2 text-[#666666]">Preview the cost again and attach a valid payment proof.</p>
                </div>
            @endif

            <section class="bg-white border border-neutral-200 rounded-md">
                <dl class="grid grid-cols-1 divide-y divide-neutral-200 text-sm sm:grid-cols-2 sm:divide-y-0">
                    <div class="px-5 py-4 sm:border-r sm:border-b sm:border-neutral-200">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Vehicle</dt>
                        <dd class="mt-1 text-black">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}
                            <span class="font-mono text-[#666666]">· {{ $rental->vehicle->license_plate }}</span>
                        </dd>
                    </div>
                    <div class="px-5 py-4 sm:border-b sm:border-neutral-200">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage</dt>
                        <dd class="mt-1 text-black"><span class="font-mono font-semibold">{{ $rental->garage->garage_code }}</span> · <x-type-label kind="garage" :value="$rental->garage->garage_type" /></dd>
                    </div>
                    <div class="px-5 py-4 sm:border-r sm:border-neutral-200">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Battery capacity</dt>
                        <dd class="mt-1 text-black tabular-nums">{{ $rental->vehicle->battery_capacity }} kWh</dd>
                    </div>
                    <div class="px-5 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Electricity rate</dt>
                        <dd class="mt-1 text-black"><x-rupiah :amount="$rental->garage->electricity_rate_per_kwh" /> per kWh</dd>
                    </div>
                </dl>
            </section>

            <form method="POST" action="{{ route('charging.preview', $rental->rental_id) }}"
                  class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                  x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                <x-form-field name="current_battery_percentage" label="Current battery percentage (%)"
                              hint="Between 0 and 99.99, up to two decimal places. The battery is charged to 100%." required>
                    <x-text-input id="current_battery_percentage" name="current_battery_percentage" type="number"
                                  min="0" max="99.99" step="0.01" inputmode="decimal" class="block w-full sm:w-48"
                                  :value="old('current_battery_percentage')" required autofocus />
                </x-form-field>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('rentals.show', $rental->rental_id)">Cancel</x-link-button>
                    <x-primary-button x-bind:disabled="submitting">Preview Cost</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
