@props(['status'])

@php
    // Match by string value to support both vendor and local enum instances
    $classes = match ($status->value) {
        'sent'    => 'bg-emerald-50 text-emerald-700',
        'failed'  => 'bg-rose-50 text-rose-700',
        'pending' => 'bg-amber-50 text-amber-700',
        default   => 'bg-zinc-50 text-zinc-700',
    };
    $dot = match ($status->value) {
        'sent'    => 'bg-emerald-500',
        'failed'  => 'bg-rose-500',
        'pending' => 'bg-amber-500',
        default   => 'bg-zinc-400',
    };
    $label = match ($status->value) {
        'sent' => 'gesendet',
        'failed' => 'fehlgeschlagen',
        'pending' => 'ausstehend',
        default => strtolower($status->getLabel()),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
</span>
