@props(['kind', 'value'])

{{-- Display names for stored type keys. --}}
{{ match ($kind) {
    'garage' => match ($value) {
        'bicycle' => 'Bicycle Garage',
        'car' => 'Normal Car Garage',
        'electric_car' => 'Electric Car Garage',
        default => $value,
    },
    'vehicle' => match ($value) {
        'bicycle' => 'Bicycle',
        'motorcycle' => 'Motorcycle',
        'car' => 'Normal Car',
        'electric_car' => 'Electric Car',
        default => $value,
    },
    'rental' => match ($value) {
        'daily' => 'Daily',
        'monthly' => 'Monthly',
        'extension' => 'Extension',
        default => $value,
    },
    default => $value,
} }}
