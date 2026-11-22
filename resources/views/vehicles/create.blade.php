<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Add Vehicle" description="Register a vehicle to use for garage rentals.">
            <x-slot name="actions">
                <x-link-button :href="route('vehicles.index')">Back to Vehicles</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('vehicles.store') }}"
                  class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                  x-data="{ type: @js(old('type', '')), submitting: false }"
                  @submit="submitting = true">
                @csrf

                <x-form-field name="type" label="Vehicle type" required>
                    <x-select-input id="type" name="type" x-model="type" required>
                        <option value="" disabled @selected(! old('type'))>Select a vehicle type</option>
                        <option value="bicycle" @selected(old('type') === 'bicycle')>Bicycle</option>
                        <option value="motorcycle" @selected(old('type') === 'motorcycle')>Motorcycle</option>
                        <option value="car" @selected(old('type') === 'car')>Normal Car</option>
                        <option value="electric_car" @selected(old('type') === 'electric_car')>Electric Car</option>
                    </x-select-input>
                </x-form-field>

                {{-- Bicycles have no license plate; the field is disabled so nothing is submitted. --}}
                <div x-show="type !== 'bicycle'">
                    <x-form-field name="license_plate" label="License plate" hint="Required for motorcycles and cars. Maximum 20 characters.">
                        <x-text-input id="license_plate" name="license_plate" type="text" maxlength="20" class="block w-full"
                                      :value="old('license_plate')"
                                      x-bind:disabled="type === 'bicycle'"
                                      x-bind:required="type !== '' && type !== 'bicycle'" />
                    </x-form-field>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <x-form-field name="brand" label="Brand" required>
                        <x-text-input id="brand" name="brand" type="text" maxlength="100" class="block w-full" :value="old('brand')" required />
                    </x-form-field>

                    <x-form-field name="model" label="Model" required>
                        <x-text-input id="model" name="model" type="text" maxlength="100" class="block w-full" :value="old('model')" required />
                    </x-form-field>
                </div>

                {{-- Battery capacity applies to electric cars only. --}}
                <div x-show="type === 'electric_car'">
                    <x-form-field name="battery_capacity" label="Battery capacity (kWh)" hint="Electric cars only. A positive number with up to two decimal places.">
                        <x-text-input id="battery_capacity" name="battery_capacity" type="number" min="0.01" step="0.01" class="block w-full"
                                      :value="old('battery_capacity')"
                                      x-bind:disabled="type !== 'electric_car'"
                                      x-bind:required="type === 'electric_car'" />
                    </x-form-field>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('vehicles.index')">Cancel</x-link-button>
                    <x-primary-button x-bind:disabled="submitting">Save Vehicle</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
