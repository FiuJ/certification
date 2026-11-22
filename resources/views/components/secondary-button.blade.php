<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-white border border-neutral-200 rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 disabled:text-neutral-500 disabled:cursor-not-allowed transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
