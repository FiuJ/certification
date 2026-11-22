@php
    $monthNames = collect(range(1, 12))->mapWithKeys(
        fn ($month) => [$month => \Carbon\CarbonImmutable::create(2000, $month, 1)->format('F')]
    );
    $selectedMonthName = $monthNames[$selectedMonth];
    $monthlyRows = collect($monthlyReport);
    $dailyRows = collect($dailyReport);
    $garageTypeRows = collect($garageTypeReport);
    $renterRows = collect($renterReport);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Admin Dashboard" description="Garage, renter and rental overview.">
            <x-slot name="actions">
                <x-link-button :href="route('garages.create')" variant="primary">Create Garages</x-link-button>
                <x-link-button :href="route('admin.renters.pending')">Renter Approvals</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Summary -->
            <section aria-labelledby="summary-heading">
                <h3 id="summary-heading" class="sr-only">Summary</h3>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white border border-neutral-200 rounded-md p-5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Garages</dt>
                        <dd class="mt-2 text-2xl font-semibold text-black tabular-nums">{{ number_format($summary['total_garages'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="bg-white border border-neutral-200 rounded-md p-5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Renters</dt>
                        <dd class="mt-2 text-2xl font-semibold text-black tabular-nums">{{ number_format($summary['total_renters'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="bg-white border border-neutral-200 rounded-md p-5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Rentals</dt>
                        <dd class="mt-2 text-2xl font-semibold text-black tabular-nums">{{ number_format($summary['total_rentals'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="bg-white border border-neutral-200 rounded-md p-5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Rental Revenue</dt>
                        <dd class="mt-2 text-2xl font-semibold text-black"><x-rupiah :amount="$summary['total_rental_revenue']" /></dd>
                    </div>
                </dl>
            </section>

            <!-- Reports -->
            <section id="reports" aria-labelledby="reports-heading" class="space-y-6 scroll-mt-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 id="reports-heading" class="text-lg font-semibold text-black">Rental Reports</h3>
                        <p class="mt-1 text-sm text-[#666666]">
                            Revenue is recognised on each rental's start date. "Rentals in period" counts every rental occupying a garage during that period.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="year" value="Year" />
                            <x-text-input id="year" name="year" type="number" min="1000" max="9999" class="mt-1 block w-28" :value="old('year', $selectedYear)" required />
                        </div>
                        <div>
                            <x-input-label for="month" value="Month" />
                            <x-select-input id="month" name="month" class="mt-1 w-40">
                                @foreach ($monthNames as $number => $name)
                                    <option value="{{ $number }}" @selected($number === $selectedMonth)>{{ $name }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <x-primary-button>Apply</x-primary-button>
                    </form>
                </div>

                <x-input-error :messages="$errors->get('year')" />
                <x-input-error :messages="$errors->get('month')" />

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <!-- Monthly Rental Report -->
                    <div class="bg-white border border-neutral-200 rounded-md">
                        <div class="px-5 py-4 border-b border-neutral-200">
                            <h4 class="font-semibold text-black">Monthly Rental Report — {{ $selectedYear }}</h4>
                        </div>

                        @if ($monthlyRows->sum('rentals_in_period') === 0)
                            <div class="p-5">
                                <x-empty-state title="No rentals in {{ $selectedYear }}" message="No rental occupied a garage during the selected year." />
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                    <thead class="bg-neutral-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Month</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">New Rentals</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rentals in Period</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($monthlyRows as $row)
                                            <tr class="{{ $row['month'] === $selectedMonth ? 'bg-neutral-100 font-semibold' : '' }}">
                                                <td class="px-4 py-3 text-black">{{ $row['month_name'] }}</td>
                                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['new_rentals'] }}</td>
                                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['rentals_in_period'] }}</td>
                                                <td class="px-4 py-3 text-right"><x-rupiah :amount="$row['rental_revenue']" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t-2 border-black">
                                        <tr class="font-semibold">
                                            <td class="px-4 py-3">Total</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $monthlyRows->sum('new_rentals') }}</td>
                                            <td class="px-4 py-3 text-right text-[#666666]">—</td>
                                            <td class="px-4 py-3 text-right"><x-rupiah :amount="$monthlyRows->sum('rental_revenue')" /></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Daily Rental Report -->
                    <div class="bg-white border border-neutral-200 rounded-md">
                        <div class="px-5 py-4 border-b border-neutral-200">
                            <h4 class="font-semibold text-black">Daily Rental Report — {{ $selectedMonthName }} {{ $selectedYear }}</h4>
                        </div>

                        @if ($dailyRows->sum('rentals_in_period') === 0)
                            <div class="p-5">
                                <x-empty-state title="No rentals in {{ $selectedMonthName }} {{ $selectedYear }}" message="No rental occupied a garage during the selected month." />
                            </div>
                        @else
                            <div class="overflow-x-auto max-h-[32rem] overflow-y-auto">
                                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                    <thead class="bg-neutral-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Date</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">New Rentals</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rentals in Period</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($dailyRows as $row)
                                            <tr class="{{ $row['rentals_in_period'] === 0 ? 'text-[#666666]' : 'text-black' }}">
                                                <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\CarbonImmutable::parse($row['date'])->format('D, d M Y') }}</td>
                                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['new_rentals'] }}</td>
                                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['rentals_in_period'] }}</td>
                                                <td class="px-4 py-3 text-right"><x-rupiah :amount="$row['rental_revenue']" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t-2 border-black bg-white">
                                        <tr class="font-semibold">
                                            <td class="px-4 py-3">Total</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $dailyRows->sum('new_rentals') }}</td>
                                            <td class="px-4 py-3 text-right text-[#666666]">—</td>
                                            <td class="px-4 py-3 text-right"><x-rupiah :amount="$dailyRows->sum('rental_revenue')" /></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Garage Type Report -->
                    <div class="bg-white border border-neutral-200 rounded-md">
                        <div class="px-5 py-4 border-b border-neutral-200">
                            <h4 class="font-semibold text-black">Garage Type Report — All Time</h4>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                <thead class="bg-neutral-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Garage Type</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Garages</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Rentals</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-200">
                                    @foreach ($garageTypeRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-black"><x-type-label kind="garage" :value="$row['garage_type']" /></td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['total_garages'] }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['total_rentals'] }}</td>
                                            <td class="px-4 py-3 text-right"><x-rupiah :amount="$row['rental_revenue']" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Renter Report -->
                    <div class="bg-white border border-neutral-200 rounded-md">
                        <div class="px-5 py-4 border-b border-neutral-200">
                            <h4 class="font-semibold text-black">Renter Report — All Time</h4>
                        </div>

                        @if ($renterRows->isEmpty())
                            <div class="p-5">
                                <x-empty-state title="No renter rentals yet" message="Renters appear here once they have created a rental." />
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                    <thead class="bg-neutral-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Renter</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Email</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Total Rentals</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($renterRows as $row)
                                            <tr>
                                                <td class="px-4 py-3 text-black">{{ $row['name'] }}</td>
                                                <td class="px-4 py-3 text-[#333333] break-all">{{ $row['email'] }}</td>
                                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['total_rentals'] }}</td>
                                                <td class="px-4 py-3 text-right"><x-rupiah :amount="$row['rental_revenue']" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
