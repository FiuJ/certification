<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Renter Approvals" description="Review pending renter registrations. Only pending accounts can be approved or rejected." />
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-input-error :messages="$errors->get('registration_status')" />

            @if ($pendingRenters->isEmpty())
                <x-empty-state title="No pending registrations" message="New renter registrations will appear here for review." />
            @else
                <div class="bg-white border border-neutral-200 rounded-md overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">NIK</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Email</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Phone</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Registered</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#666666]">Status</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#666666]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($pendingRenters as $renter)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-black">{{ $renter->nik }}</td>
                                    <td class="px-4 py-3 text-black">{{ $renter->name }}</td>
                                    <td class="px-4 py-3 text-[#333333] break-all">{{ $renter->email }}</td>
                                    <td class="px-4 py-3 text-[#333333] whitespace-nowrap">{{ $renter->phone }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $renter->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="px-4 py-3"><x-status-label :status="$renter->registration_status" /></td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.renters.approve', $renter->user_id) }}"
                                                  onsubmit="return confirm(@js('Approve the registration for '.$renter->name.'?'));">
                                                @csrf
                                                @method('PATCH')
                                                <x-primary-button>Approve</x-primary-button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.renters.reject', $renter->user_id) }}"
                                                  onsubmit="return confirm(@js('Reject the registration for '.$renter->name.'? This cannot be undone from this page.'));">
                                                @csrf
                                                @method('PATCH')
                                                <x-danger-button>Reject</x-danger-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
