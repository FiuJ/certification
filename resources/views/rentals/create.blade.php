
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
                          quoteUrl: @js(route('rentals.quote')),
                          vehicleId: @js(old('vehicle_id', request('vehicle', ''))),
                          garageType: @js(old('garage_type', '')),
                          rentalType: @js(old('rental_type', 'daily')),
                          startDate: @js(old('start_date', $today)),
                          duration: @js(old('duration', '')),
                          submitting: false,
                          quote: null,
                          quoteError: '',
                          quoteLoading: false,
                          quoteRequestId: 0,

                          get vehicleType() {
                              return this.vehicles.find(v => v.id === this.vehicleId)?.type ?? null;
                          },
                          get isElectric() {
                              return this.vehicleType === 'electric_car';
                          },
                          get fixedGarageLabel() {
                              return {
                                  bicycle: 'Bicycle Garage',
                                  motorcycle: 'Bicycle Garage',
                                  car: 'Normal Car Garage'
                              }[this.vehicleType] ?? null;
                          },
                          get selectedGarageType() {
                              if (this.isElectric) return this.garageType;

                              return {
                                  bicycle: 'bicycle',
                                  motorcycle: 'bicycle',
                                  car: 'car'
                              }[this.vehicleType] ?? '';
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
                              const n = Number(this.duration);
                              return Number.isInteger(n) && n >= 1 && n <= (this.maxDuration ?? 0)
                                  ? n
                                  : null;
                          },
                          formatDate(offset) {
                              const p = this.parts();
                              const date = new Date(Date.UTC(p.y, p.m - 1, p.d + offset));
                              return date.toLocaleDateString('en-GB', {
                                  day: '2-digit',
                                  month: 'short',
                                  year: 'numeric',
                                  timeZone: 'UTC'
                              });
                          },
                          get periodText() {
                              if (!this.parts() || !this.periodDays) return null;
                              return this.periodDays + ' days: ' + this.formatDate(0)
                                  + ' to ' + this.formatDate(this.periodDays - 1)
                                  + ' (last occupied day)';
                          },
                          formatRupiah(amount) {
                              return 'Rp' + new Intl.NumberFormat('id-ID').format(amount);
                          },
                          async refreshQuote() {
                              const requestId = ++this.quoteRequestId;
                              this.quote = null;
                              this.quoteError = '';
                              this.quoteLoading = false;

                              if (!this.vehicleId || !this.selectedGarageType || !this.parts() || !this.periodDays) {
                                  return;
                              }

                              const params = new URLSearchParams({
                                  vehicle_id: this.vehicleId,
                                  garage_type: this.selectedGarageType,
                                  rental_type: this.rentalType,
                                  start_date: this.startDate
                              });

                              if (this.rentalType === 'daily') {
                                  params.set('duration', this.duration);
                              }

                              this.quoteLoading = true;

                              try {
                                  const response = await fetch(this.quoteUrl + '?' + params.toString(), {
                                      headers: { Accept: 'application/json' }
                                  });
                                  const result = await response.json();

                                  if (requestId !== this.quoteRequestId) return;

                                  if (!response.ok) {
                                      this.quoteError = Object.values(result.errors ?? {}).flat()[0]
                                          ?? result.message
                                          ?? 'Unable to calculate the rental price.';
                                      return;
                                  }

                                  this.quote = result;
                              } catch (error) {
                                  if (requestId === this.quoteRequestId) {
                                      this.quoteError = 'Unable to load the rental price. Please try again.';
                                  }
                              } finally {
                                  if (requestId === this.quoteRequestId) {
                                      this.quoteLoading = false;
                                  }
                              }
                          }
                      }"
                      x-init="
                          ['vehicleId', 'garageType', 'rentalType', 'startDate', 'duration']
                              .forEach(key => $watch(key, () => refreshQuote()));
                          refreshQuote();
                      "
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
                        <div x-show="fixedGarageLabel" x-cloak class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm">
                            <span class="font-medium text-[#333333]">Garage type:</span>
                            <span class="text-black" x-text="fixedGarageLabel"></span>
                            <p class="mt-1 text-xs text-[#666666]">Assigned automatically from your vehicle type.</p>
                        </div>

                        <div x-show="isElectric" x-cloak>
                            <x-form-field name="garage_type" label="Garage type" hint="Electric cars may use a Normal Car Garage or an Electric Car Garage. EV charging is only available in an Electric Car Garage.">
                                <x-select-input id="garage_type" name="garage_type" x-model="garageType"
                                                x-bind:disabled="!isElectric"
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
                        <div x-show="rentalType === 'daily'" x-cloak>
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

                    <!-- Price preview, calculated by RentalService on the server -->
                    <div class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-4 text-sm" aria-live="polite">
                        <p class="font-semibold text-black">Rental Price Preview</p>

                        <p class="mt-2 text-[#666666]" x-show="!quote && !quoteLoading && !quoteError">
                            Select a vehicle, garage type and valid rental period to see the price.
                        </p>

                        <p class="mt-2 text-[#666666]" x-show="quoteLoading" x-cloak>
                            Calculating rental price...
                        </p>

                        <p class="mt-2 text-[#666666]" x-show="quoteError" x-text="quoteError" x-cloak></p>

                        <div class="mt-3 space-y-2" x-show="quote" x-cloak>
                            <div class="flex justify-between gap-4">
                                <span class="text-[#666666]">Estimated garage</span>
                                <span class="font-medium text-black" x-text="quote?.garage_code"></span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-[#666666]">Daily rate</span>
                                <span class="text-black" x-text="formatRupiah(quote?.daily_rate ?? 0)"></span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-[#666666]">Duration</span>
                                <span class="text-black" x-text="(quote?.duration_days ?? 0) + ' days'"></span>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-neutral-200 pt-3">
                                <span class="font-semibold text-black">Total Rental Amount</span>
                                <span class="text-xl font-bold text-black" x-text="formatRupiah(quote?.amount ?? 0)"></span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-[#666666]">
                            The estimate uses a currently available garage. The server rechecks availability and recalculates the final price on submission.
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
                        <x-primary-button x-bind:disabled="submitting || quoteLoading || !quote">Submit Rental</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
