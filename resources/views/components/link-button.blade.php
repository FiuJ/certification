@props(['variant' => 'secondary'])

@php
    $variantClasses = $variant === 'primary'
        ? 'bg-black border-black text-white hover:bg-[#333333]'
        : 'bg-white border-neutral-200 text-black hover:bg-neutral-100';
@endphp

<a {{ $attributes->merge(['class' => "inline-flex items-center justify-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 transition ease-in-out duration-150 {$variantClasses}"]) }}>
    {{ $slot }}
</a>
