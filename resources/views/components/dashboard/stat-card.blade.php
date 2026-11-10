@props(['label', 'value', 'icon' => 'cube', 'tone' => 'cyan'])

@php
    $tones = [
        'cyan' => 'text-gms-cyan bg-gms-cyan/10',
        'purple' => 'text-gms-purple bg-gms-purple/10',
        'amber' => 'text-gms-amber bg-gms-amber/10',
    ];
@endphp

<div class="flex items-center gap-4 rounded-xl border border-gms-border bg-gms-card/60 p-4">
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['cyan'] }}">
        <x-dashboard.icon :name="$icon" class="h-6 w-6" />
    </span>
    <div class="min-w-0">
        <p class="font-display text-3xl font-bold leading-none text-white">{{ $value }}</p>
        <p class="mt-1 truncate text-xs text-gms-muted">{{ $label }}</p>
    </div>
</div>
