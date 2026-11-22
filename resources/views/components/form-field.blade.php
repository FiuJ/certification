@props(['name', 'label', 'for' => null, 'hint' => null, 'required' => false])

<div {{ $attributes }}>
    <x-input-label :for="$for ?? $name">
        {{ $label }}
        @if ($required)
            <span class="text-[#666666]" aria-hidden="true">*</span>
        @endif
    </x-input-label>

    <div class="mt-1">
        {{ $slot }}
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-[#666666]">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
