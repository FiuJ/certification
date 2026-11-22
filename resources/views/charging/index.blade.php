<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Charging History" description="EV charging transactions for this rental.">
            <x-slot name="actions">
                <x-link-button :href="route('charging.create', $rentalId)" variant="primary">Charge EV</x-link-button>
                <x-link-button :href="route('rentals.show', $rentalId)">Back to Rental</x-link-button>
            </x-slot>
        </x-page-heading>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-input-error :messages="$errors->get('rental_id')" />

            @if ($chargingRecords->isEmpty())
                <x-empty-state title="No charging transactions yet" message="Charging is available for electric cars in an Electric Car Garage while the rental is active." />
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Transaction Date</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Battery Before Charging</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Amount</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Payment Proof</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Rental</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($chargingRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-black">{{ $record->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $record->current_battery_percentage }}%</td>
                                    <td class="px-4 py-3 text-right"><x-rupiah :amount="$record->amount" /></td>
                                    <td class="px-4 py-3">{{ $record->payment_path ? 'Submitted' : 'Not submitted' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('rentals.show', $record->rental_id) }}" class="font-medium underline hover:text-[#333333]">View rental</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-black">
                            <tr class="font-semibold">
                                <td class="px-4 py-3" colspan="2">Total</td>
                                <td class="px-4 py-3 text-right"><x-rupiah :amount="$chargingRecords->sum('amount')" /></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
