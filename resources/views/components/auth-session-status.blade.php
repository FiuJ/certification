@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-black border-l-2 border-black pl-2', 'role' => 'status']) }}>
        {{ $status }}
    </div>
@endif
