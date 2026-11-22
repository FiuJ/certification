@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[#333333]']) }}>
    {{ $value ?? $slot }}
</label>
