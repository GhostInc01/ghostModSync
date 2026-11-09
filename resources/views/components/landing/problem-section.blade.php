@php
    $problem = config('landing.content.problem');
    $gameShots = config('landing.images.game_screenshots');
@endphp

<section
    id="problem"
    aria-labelledby="problem-heading"
    class="border-b border-gms-border bg-[#080B12] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-amber-400">
                {{ $problem['eyebrow'] }}
            </p>
            <h2
                id="problem-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $problem['heading'] }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $problem['intro'] }}
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($problem['items'] as $index => $item)
                <article class="flex flex-col rounded-2xl border border-gms-border bg-gms-card p-6 transition hover:-translate-y-1 hover:border-amber-400/50">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-amber-400/30 bg-amber-400/10 text-amber-400">
                        <x-landing.icon :name="$item['icon']" class="h-6 w-6" />
                    </div>
                    <p class="mt-4 font-mono text-xs font-semibold text-amber-400/80">
                        ISSUE 0{{ $index + 1 }}
                    </p>
                    <h3 class="mt-1 font-display text-xl font-bold uppercase tracking-wide text-gms-text">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-gms-muted">
                        {{ $item['description'] }}
                    </p>
                </article>
            @endforeach
        </div>

        <div class="relative mt-12 overflow-hidden rounded-2xl border border-gms-cyan/40 bg-[#101522] p-6 text-center shadow-xl sm:p-8">
            <img
                src="{{ asset($gameShots['ats_convoy']['path']) }}"
                alt=""
                aria-hidden="true"
                width="{{ $gameShots['ats_convoy']['width'] }}"
                height="{{ $gameShots['ats_convoy']['height'] }}"
                loading="lazy"
                class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-15"
            />
            <div class="relative z-10">
                <p class="font-display text-xl font-bold uppercase tracking-wide text-gms-text sm:text-2xl">
                    {{ $problem['closing'] }}
                </p>
            </div>
        </div>
    </div>
</section>
