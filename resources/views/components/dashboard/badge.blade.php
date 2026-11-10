@props(['tone' => 'neutral'])

@php
    // Full static class names so Tailwind can detect them.
    $tones = [
        'neutral' => 'border-gms-border bg-gms-surface text-gms-muted',
        'cyan' => 'border-gms-cyan/40 bg-gms-cyan/10 text-gms-cyan',
        'purple' => 'border-gms-purple/40 bg-gms-purple/10 text-gms-purple',
        'amber' => 'border-gms-amber/40 bg-gms-amber/10 text-gms-amber',
        'red' => 'border-red-400/40 bg-red-400/10 text-red-300',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold', $tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>
