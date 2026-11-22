@php
    // The most recent record in the chain holds the current garage.
    $rentalChain = collect([$rental, ...$rental->extensions])->sortBy('start_date')->values();
    $currentRecord = $rentalChain->last();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Extend Rental" description="Choose the new last occupied day. Duration and price are calculated by the server.">
            <x-slot name="actions">
                <x-link-button :href="route('admin.rentals.index')">Back to Rentals</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @foreach (['garage_id', 'start_date', 'duration'] as $field)
                <x-input-error :messages="$errors->get($field)" />
            @endforeach

            <section class="bg-white border border-neutral-200 rounded-md">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="font-semibold text-black">Current rental</h3>
                </div>
                <dl class="divide-y divide-neutral-200 text-sm">
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Renter</dt>
                        <dd class="sm:col-span-2 text-black">{{ $rental->vehicle->user->name }} <span class="text-[#666666]">· {{ $rental->vehicle->user->email }}</span></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Vehicle</dt>
                        <dd class="sm:col-span-2 text-black">
                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}
                            · <x-type-label kind="vehicle" :value="$rental->vehicle->type" />
                            · <span class="font-mono">{{ $rental->vehicle->license_plate ?? 'No plate' }}</span>
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Original rental</dt>
                        <dd class="sm:col-span-2 text-black">
                            <x-type-label kind="rental" :value="$rental->rental_type" />
                            · <x-rental-period :rental="$rental" show="range" />
                            · <x-rupiah :amount="$rental->rental_amount" />
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Current garage</dt>
                        <dd class="sm:col-span-2 text-black">
                            <span class="font-mono font-semibold">{{ $currentRecord->garage->garage_code }}</span>
                            · <x-type-label kind="garage" :value="$currentRecord->garage->garage_type" />
                            · <x-rupiah :amount="$currentRecord->garage->daily_rate" /> per day
                        </dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-5 py-4 sm:grid-cols-3">
                        <dt class="font-medium text-[#666666]">Current period</dt>
                        <dd class="sm:col-span-2 text-black">
                            {{ $rental->start_date->format('d M Y') }} – {{ $lastOccupiedDate->format('d M Y') }}
                            <span class="text-[#666666]">(last occupied day{{ $rental->extensions->isNotEmpty() ? ', including extensions' : '' }})</span>
                        </dd>
                    </div>
                </dl>

                @if ($rental->extensions->isNotEmpty())
                    <div class="border-t border-neutral-200 px-5 py-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Previous extensions</h4>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($rentalChain->skip(1) as $extension)
                                <li class="flex flex-wrap gap-x-2">
                                    <x-rental-period :rental="$extension" show="range" />
                                    <span class="text-[#666666]">· <x-rental-period :rental="$extension" show="days" /> · {{ $extension->garage->garage_code }} · <x-rupiah :amount="$extension->rental_amount" /></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <form method="POST" action="{{ route('admin.rentals.extend', $rental->rental_id) }}" enctype="multipart/form-data"
                  class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                  x-data="{
                      startDate: @js($extensionStartDate->toDateString()),
                      endDate: @js(old('end_date', '')),
                      submitting: false,
                      get days() {
                          if (!this.endDate) return null;
                          const toUtc = (value) => { const [y, m, d] = value.split('-').map(Number); return Date.UTC(y, m - 1, d); };
                          const days = Math.round((toUtc(this.endDate) - toUtc(this.startDate)) / 86400000) + 1;
                          return days >= 1 ? days : null;
                      },
                  }"
                  @submit="submitting = true">
                @csrf

                <div class="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm">
                    <span class="font-medium text-[#333333]">Extension starts:</span>
                    <span class="text-black">{{ $extensionStartDate->format('d M Y') }}</span>
                    <span class="text-[#666666]">(the day after the current last occupied day)</span>
                </div>

                <x-form-field name="end_date" label="New last occupied day" hint="Inclusive. The extension may span several months." required>
                    <x-text-input id="end_date" name="end_date" type="date" class="block w-full sm:w-60"
                                  min="{{ $extensionStartDate->toDateString() }}"
                                  :value="old('end_date')" x-model="endDate" required />
                </x-form-field>

                <p class="text-sm text-[#333333]" x-show="days" x-cloak>
                    Extension length: <span class="font-semibold text-black" x-text="days"></span> days.
                </p>

                <div class="rounded-md border border-dashed border-[#666666] px-4 py-3 text-sm text-[#333333]">
                    <p class="font-semibold text-black">How the extension is priced</p>
                    <p class="mt-1">
                        The garage type stays <x-type-label kind="garage" :value="$currentRecord->garage->garage_type" />.
                        The current garage is kept when available; otherwise another available garage of the same type is assigned.
                        Starting from the extension start date, each full period as long as its starting month is charged at 25 × the daily rate,
                        and any remaining days at the daily rate.
                        The final amount is calculated by the server and appears on the extension record.
                    </p>
                </div>

                <x-form-field name="payment_proof" label="Payment proof" hint="JPG, JPEG, PNG or WebP image, maximum 5 MB. Stored privately." required>
                    <x-file-input id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required />
                </x-form-field>

                @if ($errors->any())
                    <p class="text-xs text-[#666666]">Please select the payment proof file again before resubmitting.</p>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('admin.rentals.index')">Cancel</x-link-button>
                    <x-primary-button x-bind:disabled="submitting">Extend Rental</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
