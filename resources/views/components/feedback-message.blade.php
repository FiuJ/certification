@props(['message' => session('success')])

@if ($message)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-md border border-black bg-white px-4 py-3 text-sm text-black']) }} role="status">
        <span class="font-semibold uppercase tracking-wide text-xs mt-0.5">Success</span>
        <span>{{ $message }}</span>
    </div>
@endif
