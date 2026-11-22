@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full bg-white text-black border-[#666666] focus:border-black focus:ring-black rounded-md disabled:bg-neutral-100 disabled:text-neutral-500']) }}>
    {{ $slot }}
</select>
