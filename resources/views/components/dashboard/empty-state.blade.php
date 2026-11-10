@props(['message', 'icon' => 'cube', 'href' => null, 'cta' => null])

<div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-gms-border px-4 py-8 text-center">
    <x-dashboard.icon :name="$icon" class="h-8 w-8 text-gms-muted" />
    <p class="max-w-sm text-sm text-gms-muted">{{ $message }}</p>
    @if ($href && $cta)
        <a href="{{ $href }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-4 py-2 text-sm font-bold text-[#080B12] shadow-[0_0_20px_rgba(0,240,255,0.25)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">{{ $cta }}</a>
    @endif
</div>
