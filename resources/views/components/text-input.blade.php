@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white text-black border-[#666666] focus:border-black focus:ring-black rounded-md disabled:bg-neutral-100 disabled:text-neutral-500']) }}>
