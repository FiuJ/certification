@props(['title', 'description' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold leading-tight text-black">
            {{ $title }}
        </h2>

        @if ($description)
            <p class="mt-1 text-sm text-[#666666]">
                {{ $description }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
