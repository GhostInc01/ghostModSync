@props(['href', 'active' => false, 'icon' => 'home'])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->class([
        'group flex min-h-[44px] items-center gap-3 rounded-lg border-l-2 px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan',
        'border-gms-cyan bg-gms-cyan/10 text-gms-cyan' => $active,
        'border-transparent text-gms-muted hover:bg-gms-card hover:text-white' => ! $active,
   ]) }}>
    <x-dashboard.icon :name="$icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
