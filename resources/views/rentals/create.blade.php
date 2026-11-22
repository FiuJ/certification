@php
    $today = now()->toDateString();

    $vehicleOptions = $vehicles->map(fn ($vehicle) => [
        'id' => $vehicle->vehicle_id,
        'type' => $vehicle->type,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading
            title="Create Rental"
            description="Choose your vehicle, rental period, and available garage."
        >
            <x-slot name="actions">
                <x-link-button :href="route('rentals.index')">
                    My Rentals
                </x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            @if ($vehicles->isEmpty())
                <x-empty-state
                    title="No vehicles registered"
                    message="Add a vehicle before creating a rental."
                >
                    <x-link-button
                        :href="route('vehicles.create')"
                        variant="primary"
                    >
                        Add Vehicle
                    </x-link-button>
                </x-empty-state>
            @else

                <form
                    method="POST"
                    action="{{ route('rentals.store') }}"
                    enctype="multipart/form-data"
                    class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"

                    x-data="{
                        vehicles: @js($vehicleOptions),
                        quoteUrl: @js(route('rentals.quote')),

                        vehicleId: @js(old('vehicle_id', request('vehicle', ''))),
                        garageType: @js(old('garage_type', '')),
                        rentalType: @js(old('rental_type', 'daily')),
                        startDate: @js(old('start_date', $today)),
                        duration: @js(old('duration', '')),
                        garageId: @js(old('garage_id', '')),

                        garages: [],
                        quoteError: '',
                        quoteLoading: false,
                        quoteRequestId: 0,
                        submitting: false,

                        get vehicleType() {
                            return this.vehicles.find(
                                vehicle => vehicle.id === this.vehicleId
                            )?.type ?? null;
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
                            if (this.isElectric) {
                                return this.garageType;
                            }

                            return {
                                bicycle: 'bicycle',
                                motorcycle: 'bicycle',
                                car: 'car'
                            }[this.vehicleType] ?? '';
                        },

                        get selectedGarage() {
                            return this.garages.find(
                                garage => garage.garage_id === this.garageId
                            ) ?? null;
                        },

                        get garageTypeLabel() {
                            return {
                                bicycle: 'Bicycle Garage',
                                car: 'Normal Car Garage',
                                electric_car: 'Electric Car Garage'
                            }[this.selectedGarageType] ?? 'Garage';
                        },

                        parts() {
                            const [y, m, d] = (this.startDate || '')
                                .split('-')
                                .map(Number);

                            return y && m && d
                                ? { y, m, d }
                                : null;
                        },

                        daysInMonth() {
                            const parts = this.parts();

                            if (!parts) {
                                return null;
                            }

                            return new Date(
                                Date.UTC(parts.y, parts.m, 0)
                            ).getUTCDate();
                        },

                        get maxDuration() {
                            const days = this.daysInMonth();

                            return days ? days - 1 : null;
                        },

                        get periodDays() {
                            if (this.rentalType === 'monthly') {
                                return this.daysInMonth();
                            }

                            const days = Number(this.duration);

                            return Number.isInteger(days)
                                && days >= 1
                                && days <= (this.maxDuration ?? 0)
                                    ? days
                                    : null;
                        },

                        formatDate(offset) {
                            const parts = this.parts();

                            if (!parts) {
                                return '';
                            }

                            const date = new Date(
                                Date.UTC(
                                    parts.y,
                                    parts.m - 1,
                                    parts.d + offset
                                )
                            );

                            return date.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                timeZone: 'UTC'
                            });
                        },

                        get periodText() {
                            if (!this.parts() || !this.periodDays) {
                                return null;
                            }

                            return this.periodDays
                                + ' days: '
                                + this.formatDate(0)
                                + ' to '
                                + this.formatDate(this.periodDays - 1)
                                + ' (last occupied day)';
                        },

                        get canSearchGarages() {
                            return Boolean(
                                this.vehicleId
                                && this.selectedGarageType
                                && this.parts()
                                && this.periodDays
                            );
                        },

                        formatRupiah(amount) {
                            return 'Rp'
                                + new Intl.NumberFormat('id-ID')
                                    .format(amount);
                        },

                        async refreshGarages(resetSelection = true) {
                            const requestId = ++this.quoteRequestId;

                            this.garages = [];
                            this.quoteError = '';
                            this.quoteLoading = false;

                            if (resetSelection) {
                                this.garageId = '';
                            }

                            if (!this.canSearchGarages) {
                                return;
                            }

                            this.quoteLoading = true;

                            const params = new URLSearchParams({
                                vehicle_id: this.vehicleId,
                                garage_type: this.selectedGarageType,
                                rental_type: this.rentalType,
                                start_date: this.startDate
                            });

                            if (this.rentalType === 'daily') {
                                params.set('duration', this.duration);
                            }

                            try {
                                const response = await fetch(
                                    this.quoteUrl
                                        + '?'
                                        + params.toString(),
                                    {
                                        headers: {
                                            Accept: 'application/json'
                                        }
                                    }
                                );

                                const result = await response.json();

                                if (requestId !== this.quoteRequestId) {
                                    return;
                                }

                                if (!response.ok) {
                                    this.quoteError =
                                        Object.values(
                                            result.errors ?? {}
                                        ).flat()[0]
                                        ?? result.message
                                        ?? 'Unable to load available garages.';

                                    this.garageId = '';
                                    return;
                                }

                                this.garages = result.garages ?? [];

                                const selectedStillAvailable =
                                    this.garages.some(
                                        garage =>
                                            garage.garage_id
                                                === this.garageId
                                    );

                                if (!selectedStillAvailable) {
                                    this.garageId = '';
                                }

                            } catch (error) {
                                if (requestId === this.quoteRequestId) {
                                    this.quoteError =
                                        'Unable to load available garages. Please try again.';

                                    this.garageId = '';
                                }
                            } finally {
                                if (requestId === this.quoteRequestId) {
                                    this.quoteLoading = false;
                                }
                            }
                        }
                    }"

                    x-init="
                        [
                            'vehicleId',
                            'garageType',
                            'rentalType',
                            'startDate',
                            'duration'
                        ].forEach(
                            key => $watch(
                                key,
                                () => refreshGarages(true)
                            )
                        );

                        refreshGarages(false);
                    "

                    @submit="submitting = true"
                >
                    @csrf

                    @if ($errors->has('garage_id'))
                        <x-input-error
                            :messages="$errors->get('garage_id')"
                        />
                    @endif

                    <!-- Vehicle -->
                    <x-form-field
                        name="vehicle_id"
                        label="Vehicle"
                        required
                    >
                        <x-select-input
                            id="vehicle_id"
                            name="vehicle_id"
                            x-model="vehicleId"
                            required
                        >
                            <option
                                value=""
                                disabled
                                @selected(! old('vehicle_id', request('vehicle')))
                            >
                                Select one of your vehicles
                            </option>

                            @foreach ($vehicles as $vehicle)
                                <option
                                    value="{{ $vehicle->vehicle_id }}"
                                    @selected(
                                        old(
                                            'vehicle_id',
                                            request('vehicle')
                                        ) === $vehicle->vehicle_id
                                    )
                                >
                                    {{ $vehicle->brand }}
                                    {{ $vehicle->model }}
                                    —
                                    {{ $vehicle->license_plate ?? 'No plate' }}
                                    (
                                    <x-type-label
                                        kind="vehicle"
                                        :value="$vehicle->type"
                                    />
                                    )
                                </option>
                            @endforeach

                        </x-select-input>
                    </x-form-field>

                    <!-- Garage Type -->
                    <div>
                        <!-- Non-EV vehicles -->
                        <div
                            x-show="fixedGarageLabel"
                            x-cloak
                            class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm"
                        >
                            <span class="font-medium text-[#333333]">
                                Garage type:
                            </span>

                            <span
                                class="text-black"
                                x-text="fixedGarageLabel"
                            ></span>

                            <p class="mt-1 text-xs text-[#666666]">
                                Garage type is determined by your vehicle.
                            </p>
                        </div>

                        <!-- Electric Vehicles -->
                        <div x-show="isElectric" x-cloak>
                            <x-form-field
                                name="garage_type"
                                label="Garage type"
                                hint="Electric cars may use Normal Car Garages or Electric Car Garages. EV charging is only available in Electric Car Garages."
                            >
                                <x-select-input
                                    id="garage_type"
                                    name="garage_type"
                                    x-model="garageType"
                                    x-bind:disabled="!isElectric"
                                    x-bind:required="isElectric"
                                >
                                    <option value="">
                                        Select a garage type
                                    </option>

                                    <option value="car">
                                        Normal Car Garage
                                    </option>

                                    <option value="electric_car">
                                        Electric Car Garage (charging available)
                                    </option>

                                </x-select-input>
                            </x-form-field>
                        </div>
                    </div>

                    <!-- Rental Type -->
                    <fieldset>
                        <legend
                            class="block font-medium text-sm text-[#333333]"
                        >
                            Rental type
                            <span
                                class="text-[#666666]"
                                aria-hidden="true"
                            >*</span>
                        </legend>

                        <div
                            class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2"
                        >
                            <!-- Daily -->
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-md border border-neutral-200 p-4 has-[:checked]:border-black"
                            >
                                <input
                                    type="radio"
                                    name="rental_type"
                                    value="daily"
                                    x-model="rentalType"
                                    class="mt-0.5 border-[#666666] text-black focus:ring-black"
                                    @checked(
                                        old('rental_type', 'daily') === 'daily'
                                    )
                                >

                                <span>
                                    <span
                                        class="block text-sm font-semibold text-black"
                                    >
                                        Daily
                                    </span>

                                    <span
                                        class="block text-xs text-[#666666]"
                                    >
                                        Daily rate × number of days.
                                        Up to the starting month's days
                                        minus one.
                                    </span>
                                </span>
                            </label>

                            <!-- Monthly -->
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-md border border-neutral-200 p-4 has-[:checked]:border-black"
                            >
                                <input
                                    type="radio"
                                    name="rental_type"
                                    value="monthly"
                                    x-model="rentalType"
                                    class="mt-0.5 border-[#666666] text-black focus:ring-black"
                                    @checked(
                                        old('rental_type') === 'monthly'
                                    )
                                >

                                <span>
                                    <span
                                        class="block text-sm font-semibold text-black"
                                    >
                                        Monthly
                                    </span>

                                    <span
                                        class="block text-xs text-[#666666]"
                                    >
                                        25 × daily rate.
                                        Lasts as many days as the
                                        starting month has.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <x-input-error
                            :messages="$errors->get('rental_type')"
                            class="mt-2"
                        />
                    </fieldset>

                    <!-- Start Date and Duration -->
                    <div
                        class="grid grid-cols-1 gap-6 sm:grid-cols-2"
                    >
                        <x-form-field
                            name="start_date"
                            label="Start date"
                            hint="Today or later."
                            required
                        >
                            <x-text-input
                                id="start_date"
                                name="start_date"
                                type="date"
                                class="block w-full"
                                min="{{ $today }}"
                                :value="old('start_date', $today)"
                                x-model="startDate"
                                required
                            />
                        </x-form-field>

                        <div
                            x-show="rentalType === 'daily'"
                            x-cloak
                        >
                            <x-form-field
                                name="duration"
                                label="Duration (days)"
                            >
                                <x-text-input
                                    id="duration"
                                    name="duration"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="block w-full"
                                    :value="old('duration')"
                                    x-model="duration"
                                    x-bind:max="maxDuration"
                                    x-bind:disabled="rentalType !== 'daily'"
                                    x-bind:required="rentalType === 'daily'"
                                />
                            </x-form-field>

                            <p
                                class="mt-1 text-xs text-[#666666]"
                                x-show="maxDuration"
                            >
                                Maximum for this start month:
                                <span
                                    class="font-semibold text-black"
                                    x-text="maxDuration"
                                ></span>
                                days.
                            </p>
                        </div>
                    </div>

                    <!-- Rental Period Preview -->
                    <div
                        class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm"
                        x-show="periodText"
                        x-cloak
                    >
                        <span
                            class="font-medium text-[#333333]"
                        >
                            Rental period:
                        </span>

                        <span
                            class="text-black"
                            x-text="periodText"
                        ></span>
                    </div>

                    <!-- Available Garages -->
                    <section
                        class="border border-neutral-200 rounded-md p-4 space-y-4"
                    >
                        <div>
                            <h3 class="text-sm font-semibold text-black">
                                Select Available Garage
                            </h3>

                            <p class="mt-1 text-xs text-[#666666]">
                                Garages are filtered by vehicle type,
                                rental period and availability.
                                Select the garage you want to rent.
                            </p>
                        </div>

                        <p
                            class="text-sm text-[#666666]"
                            x-show="!canSearchGarages"
                        >
                            Select your vehicle and a valid rental
                            period to view available garages.
                        </p>

                        <p
                            class="text-sm text-[#666666]"
                            x-show="quoteLoading"
                            x-cloak
                        >
                            Checking available garages...
                        </p>

                        <p
                            class="text-sm text-[#666666]"
                            x-show="quoteError"
                            x-text="quoteError"
                            x-cloak
                        ></p>

                        <div
                            x-show="
                                canSearchGarages
                                && !quoteLoading
                                && !quoteError
                                && garages.length === 0
                            "
                            x-cloak
                            class="rounded-md border border-neutral-200 bg-neutral-50 p-4"
                        >
                            <p class="text-sm text-black">
                                No garages available for the selected
                                type and rental period.
                            </p>

                            <p class="mt-1 text-xs text-[#666666]">
                                Choose a different rental period.
                                Electric car owners may also choose
                                another compatible garage type.
                            </p>
                        </div>

                        <!-- Garage Options -->
                        <div
                            class="space-y-3"
                            x-show="garages.length > 0"
                            x-cloak
                        >
                            <p class="text-xs text-[#666666]">
                                Available garages:
                                <span
                                    class="font-semibold text-black"
                                    x-text="garages.length"
                                ></span>
                            </p>

                            <template
                                x-for="garage in garages"
                                :key="garage.garage_id"
                            >
                                <label
                                    class="flex items-start gap-3 rounded-md border border-neutral-200 p-4 cursor-pointer has-[:checked]:border-black"
                                >
                                    <input
                                        type="radio"
                                        name="garage_id"
                                        :value="garage.garage_id"
                                        x-model="garageId"
                                        class="mt-1 border-[#666666] text-black focus:ring-black"
                                        required
                                    >

                                    <div class="flex-1 min-w-0 space-y-2">
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-sm font-semibold text-black"
                                            >
                                                <span
                                                    x-text="garageTypeLabel"
                                                ></span>
                                                <span
                                                    class="font-mono"
                                                    x-text="garage.garage_code"
                                                ></span>
                                            </span>

                                            <span
                                                class="text-xs text-[#666666]"
                                            >
                                                Available
                                            </span>
                                        </div>

                                        <div
                                            class="flex justify-between gap-3 text-sm"
                                        >
                                            <span class="text-[#666666]">
                                                Daily rate
                                            </span>

                                            <span
                                                class="font-medium text-black"
                                                x-text="formatRupiah(garage.daily_rate)"
                                            ></span>
                                        </div>

                                        <!-- Electricity tariff for EV garages -->
                                        <div
                                            class="flex justify-between gap-3 text-sm"
                                            x-show="garage.garage_type === 'electric_car'"
                                        >
                                            <span class="text-[#666666]">
                                                Electricity rate
                                            </span>

                                            <span
                                                class="font-medium text-black"
                                                x-text="formatRupiah(garage.electricity_rate_per_kwh ?? 0) + '/kWh'"
                                            ></span>
                                        </div>

                                        <div
                                            class="flex justify-between gap-3 border-t border-neutral-200 pt-2 text-sm"
                                        >
                                            <span class="font-medium text-black">
                                                Rental amount
                                            </span>

                                            <span
                                                class="font-semibold text-black"
                                                x-text="formatRupiah(garage.amount)"
                                            ></span>
                                        </div>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <button
                            type="button"
                            class="text-xs font-medium text-black underline underline-offset-2 disabled:opacity-50"
                            x-show="canSearchGarages"
                            x-bind:disabled="quoteLoading"
                            @click="refreshGarages(false)"
                        >
                            Refresh availability
                        </button>
                    </section>

                    <!-- Selected Garage and Total Amount -->
                    <section
                        class="rounded-md border border-neutral-200 bg-neutral-50 p-4 space-y-3"
                        aria-live="polite"
                    >
                        <h3 class="text-sm font-semibold text-black">
                            Rental Payment Summary
                        </h3>

                        <p
                            class="text-sm text-[#666666]"
                            x-show="!selectedGarage"
                        >
                            Select an available garage to view
                            the total rental amount.
                        </p>

                        <div
                            class="space-y-3"
                            x-show="selectedGarage"
                            x-cloak
                        >
                            <div
                                class="flex justify-between gap-3 text-sm"
                            >
                                <span class="text-[#666666]">
                                    Selected garage
                                </span>

                                <span
                                    class="font-semibold text-black"
                                    x-text="selectedGarage?.garage_code"
                                ></span>
                            </div>

                            <div
                                class="flex justify-between gap-3 text-sm"
                            >
                                <span class="text-[#666666]">
                                    Rental type
                                </span>

                                <span
                                    class="text-black capitalize"
                                    x-text="rentalType"
                                ></span>
                            </div>

                            <div
                                class="flex justify-between gap-3 text-sm"
                            >
                                <span class="text-[#666666]">
                                    Duration
                                </span>

                                <span
                                    class="text-black"
                                    x-text="periodDays + ' days'"
                                ></span>
                            </div>

                            <div
                                class="flex justify-between gap-3 text-sm"
                            >
                                <span class="text-[#666666]">
                                    Daily rate
                                </span>

                                <span
                                    class="text-black"
                                    x-text="formatRupiah(selectedGarage?.daily_rate ?? 0)"
                                ></span>
                            </div>

                            <div
                                class="flex justify-between items-center gap-4 border-t border-neutral-200 pt-3"
                            >
                                <span
                                    class="text-sm font-semibold text-black"
                                >
                                    Total Rental Amount
                                </span>

                                <span
                                    class="text-xl font-bold text-black"
                                    x-text="formatRupiah(selectedGarage?.amount ?? 0)"
                                ></span>
                            </div>
                        </div>

                        <p class="text-xs text-[#666666]">
                            The total covers garage rental only.
                            EV charging is billed separately using
                            the garage's electricity rate.
                        </p>

                        <p class="text-xs text-[#666666]">
                            Availability and total amount are
                            rechecked by the server when submitted.
                        </p>
                    </section>

                    <!-- Payment Proof -->
                    <x-form-field
                        name="payment_proof"
                        label="Payment proof"
                        hint="JPG, JPEG, PNG or WebP image, maximum 5 MB. Stored privately."
                        required
                    >
                        <x-file-input
                            id="payment_proof"
                            name="payment_proof"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        />
                    </x-form-field>

                    @if ($errors->any())
                        <p class="text-xs text-[#666666]">
                            Please select the payment proof file again
                            before resubmitting.
                        </p>
                    @endif

                    <!-- Actions -->
                    <div
                        class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6"
                    >
                        <x-link-button :href="route('rentals.index')">
                            Cancel
                        </x-link-button>

                        <x-primary-button
                            x-bind:disabled="
                                submitting
                                || quoteLoading
                                || !selectedGarage
                            "
                        >
                            Submit Rental
                        </x-primary-button>
                    </div>

                </form>
            @endif
        </div>
    </div>
</x-app-layout>
