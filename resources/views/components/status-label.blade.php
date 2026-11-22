@props(['status'])

@php
    // Normalise stored values (e.g. "Approve", "used") into display labels.
    $label = match ($status) {
        'Approve' => 'Approved',
        'Reject' => 'Rejected',
        default => ucfirst((string) $status),
    };

    // Shape, not colour, distinguishes each group of statuses.
    $classes = match ($label) {
        'Active', 'Approved', 'Available' => 'bg-black text-white border border-black',
        'Pending', 'Upcoming', 'Used' => 'bg-white text-black border border-black',
        default => 'bg-neutral-100 text-[#666666] border border-dashed border-[#666666]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide {$classes}"]) }}>
    {{ $label }}
</span>
