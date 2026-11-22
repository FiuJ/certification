<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Registration Status" />
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="bg-white border border-neutral-200 rounded-md p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-lg font-semibold text-black">
                        {{ $status === 'Reject' ? 'Registration Rejected' : 'Registration Pending' }}
                    </h3>

                    <x-status-label :status="$status" />
                </div>

                @if ($status === 'Reject')
                    <p class="mt-4 text-sm text-[#333333]">
                        An administrator has reviewed and rejected your renter registration.
                        Renter features such as vehicles, rentals and charging are not available for this account.
                    </p>
                @else
                    <p class="mt-4 text-sm text-[#333333]">
                        Your renter registration has been received and is waiting for an administrator to approve it.
                    </p>
                    <p class="mt-2 text-sm text-[#333333]">
                        Until it is approved you cannot register vehicles, create rentals or use charging.
                        Return to this page later to check your status.
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap gap-3 border-t border-neutral-200 pt-6">
                    <x-link-button :href="route('profile.edit')">Profile</x-link-button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-primary-button>Log Out</x-primary-button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
