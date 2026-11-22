<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Create Garages" description="Add a batch of garages of one type. Codes are generated automatically.">
            <x-slot name="actions">
                <x-link-button :href="route('garages.index')">Back to Garages</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('garages.store') }}"
                  class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                  x-data="{ garageType: @js(old('garage_type', '')), submitting: false }"
                  @submit="submitting = true">
                @csrf

                <x-form-field name="garage_type" label="Garage type" required>
                    <x-select-input id="garage_type" name="garage_type" x-model="garageType" required>
                        <option value="" disabled>Select a garage type</option>
                        <option value="bicycle" @selected(old('garage_type') === 'bicycle')>Bicycle Garage — codes S001–S999</option>
                        <option value="car" @selected(old('garage_type') === 'car')>Normal Car Garage — codes C001–C999</option>
                        <option value="electric_car" @selected(old('garage_type') === 'electric_car')>Electric Car Garage — codes E001–E999</option>
                    </x-select-input>
                </x-form-field>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <x-form-field name="quantity" label="Quantity" hint="Between 1 and 999. Each type supports at most 999 garages in total." required>
                        <x-text-input id="quantity" name="quantity" type="number" min="1" max="999" step="1" class="block w-full" :value="old('quantity', 1)" required />
                    </x-form-field>

                    <x-form-field name="daily_rate" label="Daily rate (IDR)" hint="Whole rupiah per day. Monthly rentals cost 25 × this rate." required>
                        <x-text-input id="daily_rate" name="daily_rate" type="number" min="1" step="1" class="block w-full" :value="old('daily_rate')" required />
                    </x-form-field>
                </div>

                {{-- Only electric car garages carry an electricity rate; the server enforces this too.
                     Disabled inputs are not submitted, so a hidden value never reaches the server. --}}
                <div x-show="garageType === 'electric_car'">
                    <x-form-field name="electricity_rate_per_kwh" label="Electricity rate per kWh (IDR)" hint="Electric Car Garage only. Used to calculate EV charging costs.">
                        <x-text-input id="electricity_rate_per_kwh" name="electricity_rate_per_kwh" type="number" min="1" step="1" class="block w-full"
                                      :value="old('electricity_rate_per_kwh')"
                                      x-bind:disabled="garageType !== 'electric_car'"
                                      x-bind:required="garageType === 'electric_car'" />
                    </x-form-field>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('garages.index')">Cancel</x-link-button>
                    <x-primary-button x-bind:disabled="submitting">Create Garages</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
