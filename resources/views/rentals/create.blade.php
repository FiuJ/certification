@php
    $today = now()->toDateString();
    $vehicleOptions = $vehicles->map(fn ($vehicle) => [
        'id' => $vehicle->vehicle_id,
        'type' => $vehicle->type,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Create Rental" description="Reserve a compatible garage for one of your vehicles.">
            <x-slot name="actions">
                <x-link-button :href="route('rentals.index')">My Rentals</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($vehicles->isEmpty())
                <x-empty-state title="No vehicles registered" message="Add a vehicle before creating a rental.">
                    <x-link-button :href="route('vehicles.create')" variant="primary">Add Vehicle</x-link-button>
                </x-empty-state>
            @else
                <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data"
                      class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                      x-data="{
                          vehicles: @js($vehicleOptions),
                          vehicleId: @js(old('vehicle_id', request('vehicle', ''))),
                          garageType: @js(old('garage_type', '')),
                          rentalType: @js(old('rental_type', 'daily')),
                          startDate: @js(old('start_date', $today)),
                          duration: @js(old('duration', '')),
                          submitting: false,
                          get vehicleType() {
                              return this.vehicles.find(v => v.id === this.vehicleId)?.type ?? null;
                          },
                          get isElectric() {
                              return this.vehicleType === 'electric_car';
                          },
                          get fixedGarageLabel() {
                              return { bicycle: 'Bicycle Garage', motorcycle: 'Bicycle Garage', car: 'Normal Car Garage' }[this.vehicleType] ?? null;
                          },
                          parts() {
                              const [y, m, d] = (this.startDate || '').split('-').map(Number);
                              return y && m && d ? { y, m, d } : null;
                          },
                          daysInMonth() {
                              const p = this.parts();
                              return p ? new Date(Date.UTC(p.y, p.m, 0)).getUTCDate() : null;
                          },
                          get maxDuration() {
                              const days = this.daysInMonth();
                              return days ? days - 1 : null;
                          },
                          get periodDays() {
                              if (this.rentalType === 'monthly') return this.daysInMonth();
                              const n = parseInt(this.duration, 10);
                              return n >= 1 && n <= (this.maxDuration ?? 0) ? n : null;
                          },
                          formatDate(offset) {
                              const p = this.parts();
                              const date = new Date(Date.UTC(p.y, p.m - 1, p.d + offset));
                              return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
                          },
                          get periodText() {
                              if (!this.parts() || !this.periodDays) return null;
                              return this.periodDays + ' days: ' + this.formatDate(0) + ' to ' + this.formatDate(this.periodDays - 1) + ' (last occupied day)';
                          },
                      }"
                      @submit="submitting = true">
                    @csrf

                    @if ($errors->has('garage_id'))
                        <x-input-error :messages="$errors->get('garage_id')" />
                    @endif

                    <!-- Vehicle -->
                    <x-form-field name="vehicle_id" label="Vehicle" required>
                        <x-select-input id="vehicle_id" name="vehicle_id" x-model="vehicleId" required>
                            <option value="" disabled @selected(! old('vehicle_id', request('vehicle')))>Select one of your vehicles</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->vehicle_id }}" @selected(old('vehicle_id', request('vehicle')) === $vehicle->vehicle_id)>
                                    {{ $vehicle->brand }} {{ $vehicle->model }}
                                    — {{ $vehicle->license_plate ?? 'No plate' }}
                                    (<x-type-label kind="vehicle" :value="$vehicle->type" />)
                                </option>
                            @endforeach
                        </x-select-input>
                    </x-form-field>

                    <!-- Garage type -->
                    <div>
                        {{-- Non-electric vehicles have exactly one compatible garage type. --}}
                        <div x-show="fixedGarageLabel" x-cloak class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm">
                            <span class="font-medium text-[#333333]">Garage type:</span>
                            <span class="text-black" x-text="fixedGarageLabel"></span>
                            <p class="mt-1 text-xs text-[#666666]">Assigned automatically from your vehicle type.</p>
                        </div>

                        {{-- Electric cars choose between a normal and an electric (charging) garage. --}}
                        <div x-show="isElectric">
                            <x-form-field name="garage_type" label="Garage type" hint="Electric cars may use a Normal Car Garage or an Electric Car Garage. EV charging is only available in an Electric Car Garage.">
                                <x-select-input id="garage_type" name="garage_type" x-model="garageType"
                                                x-bind:disabled="! isElectric"
                                                x-bind:required="isElectric">
                                    <option value="" @selected(! old('garage_type'))>Select a garage type</option>
                                    <option value="car" @selected(old('garage_type') === 'car')>Normal Car Garage</option>
                                    <option value="electric_car" @selected(old('garage_type') === 'electric_car')>Electric Car Garage (charging available)</option>
                                </x-select-input>
                            </x-form-field>
                        </div>
                    </div>

                    <!-- Rental type -->
                    <fieldset>
                        <legend class="block font-medium text-sm text-[#333333]">Rental type <span class="text-[#666666]" aria-hidden="true">*</span></legend>
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border border-neutral-200 p-4 has-[:checked]:border-black">
                                <input type="radio" name="rental_type" value="daily" x-model="rentalType" class="mt-0.5 border-[#666666] text-black focus:ring-black" @checked(old('rental_type', 'daily') === 'daily')>
                                <span>
                                    <span class="block text-sm font-semibold text-black">Daily</span>
                                    <span class="block text-xs text-[#666666]">Daily rate × number of days. Up to the starting month's days minus one.</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border border-neutral-200 p-4 has-[:checked]:border-black">
                                <input type="radio" name="rental_type" value="monthly" x-model="rentalType" class="mt-0.5 border-[#666666] text-black focus:ring-black" @checked(old('rental_type') === 'monthly')>
                                <span>
                                    <span class="block text-sm font-semibold text-black">Monthly</span>
                                    <span class="block text-xs text-[#666666]">25 × daily rate. Lasts as many days as the starting month has.</span>
                                </span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('rental_type')" class="mt-2" />
                    </fieldset>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Start date -->
                        <x-form-field name="start_date" label="Start date" hint="Today or later." required>
                            <x-text-input id="start_date" name="start_date" type="date" class="block w-full" min="{{ $today }}"
                                          :value="old('start_date', $today)" x-model="startDate" required />
                        </x-form-field>

                        <!-- Duration (daily only) -->
                        <div x-show="rentalType === 'daily'">
                            <x-form-field name="duration" label="Duration (days)">
                                <x-text-input id="duration" name="duration" type="number" min="1" step="1" class="block w-full"
                                              :value="old('duration')" x-model="duration"
                                              x-bind:max="maxDuration"
                                              x-bind:disabled="rentalType !== 'daily'"
                                              x-bind:required="rentalType === 'daily'" />
                            </x-form-field>
                            <p class="mt-1 text-xs text-[#666666]" x-show="maxDuration">
                                Maximum for this start month: <span class="font-semibold text-black" x-text="maxDuration"></span> days.
                            </p>
                        </div>
                    </div>

                    <!-- Period preview -->
                    <div class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm" x-show="periodText" x-cloak>
                        <span class="font-medium text-[#333333]">Rental period:</span>
                        <span class="text-black" x-text="periodText"></span>
                    </div>

                    <!-- Pricing note -->
                    <div class="rounded-md border border-dashed border-[#666666] px-4 py-3 text-sm text-[#333333]">
                        <p class="font-semibold text-black">How the price is calculated</p>
                        <p class="mt-1">
                            An available garage of the chosen type is assigned automatically when you submit.
                            The amount is calculated by the server from that garage's daily rate and is shown on your rental record.
                        </p>
                    </div>

                    <!-- Payment proof -->
                    <x-form-field name="payment_proof" label="Payment proof" hint="JPG, JPEG, PNG or WebP image, maximum 5 MB. Stored privately." required>
                        <x-file-input id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required />
                    </x-form-field>

                    @if ($errors->any())
                        <p class="text-xs text-[#666666]">Please select the payment proof file again before resubmitting.</p>
                    @endif

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                        <x-link-button :href="route('rentals.index')">Cancel</x-link-button>
                        <x-primary-button x-bind:disabled="submitting">Submit Rental</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
