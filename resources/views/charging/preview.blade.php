<x-app-layout>
    {{-- This page is the response to a POST. Without a Referer, a failed submission
         redirects back to the charging form instead of this POST-only URL. --}}
    <x-slot name="head">
        <meta name="referrer" content="no-referrer">
    </x-slot>

    <x-slot name="header">
        <x-page-heading title="Charging Cost Preview" description="Review the server-calculated cost, then upload your payment proof.">
            <x-slot name="actions">
                <x-link-button :href="route('charging.create', $rentalId)">Change Battery Level</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="bg-white border border-neutral-200 rounded-md">
                <dl class="divide-y divide-neutral-200 text-sm">
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <dt class="text-[#666666]">Current battery percentage</dt>
                        <dd class="font-medium text-black tabular-nums">{{ rtrim(rtrim(number_format($preview['current_battery_percentage'], 2, '.', ''), '0'), '.') }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <dt class="text-[#666666]">Battery capacity</dt>
                        <dd class="font-medium text-black tabular-nums">{{ number_format($preview['battery_capacity'], 2, '.', ',') }} kWh</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <dt class="text-[#666666]">Energy required <span class="text-xs">((100 − battery %) ÷ 100 × capacity)</span></dt>
                        <dd class="font-medium text-black tabular-nums">{{ number_format($preview['energy_needed_kwh'], 4, '.', ',') }} kWh</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <dt class="text-[#666666]">Electricity price</dt>
                        <dd class="font-medium text-black"><x-rupiah :amount="$preview['electricity_rate_per_kwh']" /> per kWh</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-4 bg-neutral-50">
                        <dt class="font-semibold text-black">Total amount <span class="text-xs font-normal text-[#666666]">(energy × price, rounded to whole rupiah)</span></dt>
                        <dd class="text-xl font-semibold text-black"><x-rupiah :amount="$preview['amount']" /></dd>
                    </div>
                </dl>
            </section>

            <form method="POST" action="{{ route('charging.store', $rentalId) }}" enctype="multipart/form-data"
                  class="bg-white border border-neutral-200 rounded-md p-6 space-y-6"
                  x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                {{-- Only the battery level is carried forward; the server recalculates the amount. --}}
                <input type="hidden" name="current_battery_percentage" value="{{ $preview['current_battery_percentage'] }}">

                <x-form-field name="payment_proof" label="Payment proof" hint="Pay the total amount above, then upload a JPG, JPEG, PNG or WebP image (maximum 5 MB). Stored privately." required>
                    <x-file-input id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required />
                </x-form-field>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('charging.create', $rentalId)">Back</x-link-button>
                    <x-primary-button x-bind:disabled="submitting">Submit Charging Payment</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
