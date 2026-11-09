@php
    $howItWorks = config('landing.content.how_it_works');
    $stepImages = config('landing.images.how_it_works');
@endphp

<section
    id="how-it-works"
    aria-labelledby="how-it-works-heading"
    class="border-b border-gms-border bg-[#101522] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $howItWorks['eyebrow'] }}
            </p>
            <h2
                id="how-it-works-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $howItWorks['heading'] }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $howItWorks['subtitle'] }}
            </p>
        </div>

        {{-- Progression Bar showing Leader -> Central Platform -> Members --}}
        <div class="mt-10 flex flex-wrap items-center justify-center gap-3 rounded-xl border border-gms-border bg-[#080B12] px-4 py-3 text-xs font-semibold text-gms-muted sm:text-sm">
            <span class="inline-flex items-center gap-1.5 text-gms-purple">
                <span class="h-2 w-2 rounded-full bg-gms-purple" aria-hidden="true"></span>
                <span>VTC Leader (Web Platform)</span>
            </span>
            <span aria-hidden="true" class="text-gms-border">&rarr;</span>
            <span class="inline-flex items-center gap-1.5 text-gms-cyan">
                <span class="h-2 w-2 rounded-full bg-gms-cyan" aria-hidden="true"></span>
                <span>GhostModsSync Central Library</span>
            </span>
            <span aria-hidden="true" class="text-gms-border">&rarr;</span>
            <span class="inline-flex items-center gap-1.5 text-emerald-400">
                <span class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                <span>Approved Members (Desktop Client)</span>
            </span>
        </div>

        <ol class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($howItWorks['steps'] as $step)
                @php
                    $img = $stepImages[$step['image_key']] ?? null;
                    $isDesktop = str_contains(strtolower($step['environment']), 'desktop');
                @endphp
                <li class="flex flex-col justify-between rounded-2xl border border-gms-border bg-gms-card p-6 transition hover:-translate-y-1 hover:border-gms-cyan/50">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-extrabold tracking-wider text-gms-cyan">
                                {{ $step['number'] }}
                            </span>
                            <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold {{ $isDesktop ? 'border border-gms-cyan/40 bg-gms-cyan/10 text-gms-cyan' : 'border border-gms-purple/40 bg-gms-purple/10 text-purple-300' }}">
                                {{ $step['environment'] }}
                            </span>
                        </div>

                        <h3 class="mt-4 font-display text-2xl font-bold uppercase tracking-wide text-gms-text">
                            {{ $step['title'] }}
                        </h3>

                        <p class="mt-2.5 text-sm leading-relaxed text-gms-muted">
                            {{ $step['description'] }}
                        </p>
                    </div>

                    @if ($img)
                        <div class="mt-6 overflow-hidden rounded-xl border border-gms-border bg-[#080B12]">
                            <img
                                src="{{ asset($img['path']) }}"
                                alt="{{ $img['alt'] }}"
                                width="{{ $img['width'] }}"
                                height="{{ $img['height'] }}"
                                loading="lazy"
                                class="h-auto w-full"
                            />
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
