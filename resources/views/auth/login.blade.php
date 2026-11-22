<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-black">Log in</h1>
        <p class="mt-1 text-sm text-[#666666]">Access your garage rental account.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[#666666] text-black focus:ring-black" name="remember">
                <span class="ms-2 text-sm text-[#333333]">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-[#666666] hover:text-black rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="mt-6 w-full" x-bind:disabled="submitting">
            {{ __('Log in') }}
        </x-primary-button>

        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-[#666666]">
                No account yet?
                <a href="{{ route('register') }}" class="font-semibold text-black underline hover:text-[#333333] focus:outline-none focus:ring-2 focus:ring-black rounded">
                    Register as a renter
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
