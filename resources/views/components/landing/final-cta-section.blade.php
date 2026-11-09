@php
    $finalCta = config('landing.content.final_cta');
    $gameShots = config('landing.images.game_screenshots');
    $primaryHref = auth()->check() ? route('groups.create') : route('register');
    $secondaryHref = auth()->check() ? route('dashboard') : route('login');
    $secondaryLabel = auth()->check() ? $finalCta['authenticated_secondary_cta'] : $finalCta['secondary_cta'];
@endphp

<section
    aria-labelledby="final-cta-heading"
    class="border-b border-gms-border bg-[#080B12] py-20 sm:py-24"
>
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border-2 border-gms-cyan/40 bg-[#101522] px-6 py-14 text-center shadow-2xl sm:px-12">
            {{-- In-Game ETS2 & ATS Convoy Background --}}
            <img
                src="{{ asset($gameShots['hero_convoy']['path']) }}"
                alt=""
                aria-hidden="true"
                width="{{ $gameShots['hero_convoy']['width'] }}"
                height="{{ $gameShots['hero_convoy']['height'] }}"
                loading="lazy"
                class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-20"
            />
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#080B12] via-[#101522]/85 to-[#080B12]/90" aria-hidden="true"></div>

            <div class="relative z-10">
                <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                    {{ $finalCta['eyebrow'] }}
                </p>

                <h2
                    id="final-cta-heading"
                    class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
                >
                    {{ $finalCta['heading'] }}
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-gms-muted sm:text-lg">
                    {{ $finalCta['supporting_text'] }}
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a
                        href="{{ $primaryHref }}"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-gms-cyan px-7 py-3.5 font-display text-lg font-bold uppercase tracking-wider text-[#080B12] shadow-[0_0_25px_rgba(0,240,255,0.3)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan sm:w-auto"
                    >
                        {{ $finalCta['primary_cta'] }}
                    </a>

                    <a
                        href="{{ $secondaryHref }}"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-gms-border bg-[#080B12]/90 px-7 py-3.5 font-display text-lg font-bold uppercase tracking-wider text-gms-text transition hover:border-gms-cyan/60 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan sm:w-auto"
                    >
                        {{ $secondaryLabel }}
                    </a>
                </div>

                <x-landing.social-share />
            </div>
        </div>
    </div>
</section>
