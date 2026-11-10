@props(['title', 'description' => null, 'actionHref' => null, 'actionLabel' => null])

<section {{ $attributes->class('rounded-xl border border-gms-border bg-gms-card/60') }}>
    <header class="flex items-start justify-between gap-3 border-b border-gms-border px-5 py-4">
        <div class="min-w-0">
            <h2 class="font-display text-lg font-bold tracking-wide text-white">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-xs text-gms-muted">{{ $description }}</p>
            @endif
        </div>
        @if ($actionHref && $actionLabel)
            <a href="{{ $actionHref }}" class="shrink-0 rounded-md px-1.5 py-1 text-xs font-semibold text-gms-cyan hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">{{ $actionLabel }}</a>
        @endif
    </header>
    <div class="p-5">{{ $slot }}</div>
</section>
