<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-black">Renter registration</h1>
        <p class="mt-1 text-sm text-[#666666]">
            New accounts are reviewed by an administrator before renter features become available.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <!-- NIK -->
        <x-form-field name="nik" label="NIK" hint="16-digit national identity number." required>
            <x-text-input id="nik" class="block w-full" type="text" name="nik" :value="old('nik')"
                          required autofocus inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                          title="NIK must contain exactly 16 digits." />
        </x-form-field>

        <!-- Name -->
        <x-form-field name="name" label="Full name" required>
            <x-text-input id="name" class="block w-full" type="text" name="name" :value="old('name')" required maxlength="255" autocomplete="name" />
        </x-form-field>

        <!-- Phone -->
        <x-form-field name="phone" label="Phone number" required>
            <x-text-input id="phone" class="block w-full" type="tel" name="phone" :value="old('phone')" required maxlength="20" autocomplete="tel" />
        </x-form-field>

        <!-- Email Address -->
        <x-form-field name="email" label="Email" required>
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required maxlength="255" autocomplete="username" />
        </x-form-field>

        <!-- Password -->
        <x-form-field name="password" label="Password" required>
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" />
        </x-form-field>

        <!-- Confirm Password -->
        <x-form-field name="password_confirmation" label="Confirm password" required>
            <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </x-form-field>

        <x-primary-button class="w-full" x-bind:disabled="submitting">
            {{ __('Register') }}
        </x-primary-button>

        <p class="text-center text-sm text-[#666666]">
            Already registered?
            <a class="font-semibold text-black underline hover:text-[#333333] rounded focus:outline-none focus:ring-2 focus:ring-black" href="{{ route('login') }}">
                Log in
            </a>
        </p>
    </form>
</x-guest-layout>
