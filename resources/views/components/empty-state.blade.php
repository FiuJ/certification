@props(['title', 'message' => null])

<div {{ $attributes->merge(['class' => 'border border-dashed border-neutral-200 rounded-md bg-neutral-50 px-6 py-10 text-center']) }}>
    <p class="text-sm font-semibold text-black">{{ $title }}</p>

    @if ($message)
        <p class="mt-1 text-sm text-[#666666]">{{ $message }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
