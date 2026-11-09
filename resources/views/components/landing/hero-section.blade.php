@php
    $hero = config('landing.content.hero');
    $heroImage = config('landing.images.hero_illustration');
    $gameShots = config('landing.images.game_screenshots');
    $supportedGames = config('landing.site.supported_games', []);
    $primaryHref = auth()->check() ? route('groups.create') : route('register');
    $secondaryHref = auth()->check() ? route('dashboard') : route('login');
    $secondaryLabel = auth()->check() ? $hero['authenticated_secondary_cta'] : $hero['secondary_cta'];
@endphp

<section
    id="hero"
    aria-labelledby="hero-heading"
    class="relative overflow-hidden border-b border-gms-border bg-[#080B12] pt-10 pb-20 sm:pt-14 sm:pb-28"
>
    {{-- Full-Bleed In-Game ETS2 & ATS Convoy Atmospheric Backdrop --}}
    <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
        <img
            src="{{ asset($gameShots['hero_convoy']['path']) }}"
            alt=""
            width="{{ $gameShots['hero_convoy']['width'] }}"
            height="{{ $gameShots['hero_convoy']['height'] }}"
            class="h-full w-full object-cover object-center opacity-25 scale-105 filter saturate-125"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-[#080B12] via-[#080B12]/90 to-[#080B12]/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#080B12] via-transparent to-[#080B12]/80"></div>
    </div>

    {{-- Ambient Gaming Accent Glows --}}
    <div class="pointer-events-none absolute -top-24 left-1/4 h-96 w-96 rounded-full bg-gms-cyan/15 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute top-32 right-1/4 h-96 w-96 rounded-full bg-amber-500/10 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Top Simulator Badges Strip (Unmistakably ETS2 & ATS) --}}
        <div class="mb-6 flex flex-wrap items-center gap-2.5">
            <span class="inline-flex items-center gap-2 rounded-lg border border-gms-cyan/40 bg-[#101522]/90 px-3.5 py-1.5 font-display text-xs font-bold uppercase tracking-widest text-gms-cyan shadow-lg backdrop-blur-md">
                <span class="h-2 w-2 rounded-full bg-gms-cyan animate-pulse" aria-hidden="true"></span>
                <span>{{ $hero['eyebrow'] }}</span>
            </span>

            @foreach ($supportedGames as $game)
                <span class="inline-flex items-center gap-2 rounded-lg border border-amber-400/40 bg-amber-500/10 px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-amber-300 backdrop-blur-md">
                    <span class="rounded bg-amber-400 px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[#080B12]">{{ $game['code'] }}</span>
                    <span>{{ $game['name'] }}</span>
                </span>
            @endforeach
        </div>

        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
            {{-- Left Column: High-Impact Gaming Headline, Copy & CTAs --}}
            <div class="lg:col-span-6">
                <h1
                    id="hero-heading"
                    class="font-display text-4xl font-bold uppercase tracking-wide text-gms-text sm:text-6xl lg:text-[3.65rem] leading-[1.04] drop-shadow-[0_4px_24px_rgba(0,0,0,0.8)]"
                >
                    {{ $hero['headline'] }}
                </h1>

                <p class="mt-5 text-lg font-semibold leading-relaxed text-gms-text/95 sm:text-xl">
                    {{ $hero['supporting_headline'] }}
                </p>

                <p class="mt-4 text-base leading-relaxed text-gms-muted">
                    {{ $hero['supporting_copy'] }}
                </p>

                {{-- Primary & Secondary CTAs --}}
                <div class="mt-8 flex flex-col gap-3.5 sm:flex-row sm:items-center">
                    <a
                        href="{{ $primaryHref }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gms-cyan px-7 py-4 font-display text-lg font-bold uppercase tracking-wider text-[#080B12] shadow-[0_0_30px_rgba(0,240,255,0.35)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                    >
                        <span>{{ $hero['primary_cta'] }}</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                    <a
                        href="{{ $secondaryHref }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gms-border bg-gms-card/90 px-7 py-4 font-display text-lg font-bold uppercase tracking-wider text-gms-text backdrop-blur-md transition hover:border-gms-cyan/60 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                    >
                        {{ $secondaryLabel }}
                    </a>
                </div>

                {{-- Supporting Indicators --}}
                <ul class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="Key platform highlights">
                    @foreach ($hero['indicators'] as $indicator)
                        <li class="flex items-center gap-2.5 rounded-lg border border-gms-border/60 bg-[#101522]/75 px-3 py-2 text-sm font-medium text-gms-text backdrop-blur-sm">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>{{ $indicator }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Right Column: In-Game ETS2 & ATS Convoy Visual + Central Sync HUD Overlay --}}
            <div class="lg:col-span-6 space-y-4">
                {{-- Primary In-Game Convoy Showcase Frame --}}
                <div class="gms-float-slow relative overflow-hidden rounded-2xl border-2 border-gms-cyan/40 bg-gms-card shadow-[0_0_45px_rgba(0,240,255,0.15)]">
                    <div class="flex items-center justify-between border-b border-gms-border bg-[#080B12]/95 px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400" aria-hidden="true"></span>
                            <span class="font-display text-xs font-bold uppercase tracking-widest text-gms-text">
                                ETS2 &amp; ATS MULTIPLAYER VTC CONVOY ENVIRONMENT
                            </span>
                        </div>
                        <span class="rounded bg-gms-cyan/15 px-2 py-0.5 font-mono text-[11px] font-bold text-gms-cyan">
                            MOD LOADOUT MATCHED
                        </span>
                    </div>

                    <div class="relative">
                        <img
                            src="{{ asset($gameShots['hero_convoy']['path']) }}"
                            alt="{{ $gameShots['hero_convoy']['alt'] }}"
                            width="{{ $gameShots['hero_convoy']['width'] }}"
                            height="{{ $gameShots['hero_convoy']['height'] }}"
                            loading="eager"
                            fetchpriority="high"
                            class="h-auto w-full object-cover"
                        />

                        {{-- In-Game Style HUD Overlay Bar --}}
                        <div class="absolute inset-x-3 bottom-3 rounded-xl border border-gms-border/90 bg-[#080B12]/90 p-3.5 backdrop-blur-md">
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-lg border border-gms-purple/40 bg-[#151D2B]/90 px-2 py-2">
                                    <p class="font-mono text-[10px] font-bold uppercase text-purple-300">1. VTC Leader</p>
                                    <p class="mt-0.5 font-display text-xs font-bold text-gms-text">Uploads &amp; Approves</p>
                                </div>
                                <div class="rounded-lg border border-gms-cyan/40 bg-[#151D2B]/90 px-2 py-2">
                                    <p class="font-mono text-[10px] font-bold uppercase text-gms-cyan">2. Central Hub</p>
                                    <p class="mt-0.5 font-display text-xs font-bold text-gms-text">Private VTC Library</p>
                                </div>
                                <div class="rounded-lg border border-emerald-500/40 bg-[#151D2B]/90 px-2 py-2">
                                    <p class="font-mono text-[10px] font-bold uppercase text-emerald-400">3. Desktop Client</p>
                                    <p class="mt-0.5 font-display text-xs font-bold text-gms-text">Syncs Mod Folder</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dual Simulator Showcase Strip (ETS2 & ATS Side-by-Side) --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="group relative overflow-hidden rounded-xl border border-gms-border bg-gms-card">
                        <img
                            src="{{ asset($gameShots['ets2_convoy']['path']) }}"
                            alt="{{ $gameShots['ets2_convoy']['alt'] }}"
                            width="{{ $gameShots['ets2_convoy']['width'] }}"
                            height="{{ $gameShots['ets2_convoy']['height'] }}"
                            loading="eager"
                            class="h-36 w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#080B12] via-[#080B12]/40 to-transparent"></div>
                        <div class="absolute bottom-2.5 left-3 right-3 flex items-center justify-between">
                            <div>
                                <span class="rounded bg-gms-cyan px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[#080B12]">ETS2</span>
                                <p class="mt-1 font-display text-sm font-bold uppercase tracking-wide text-white">Euro Truck Simulator 2</p>
                            </div>
                            <span class="font-mono text-[10px] text-emerald-400">European VTC Convoys</span>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-xl border border-gms-border bg-gms-card">
                        <img
                            src="{{ asset($gameShots['ats_convoy']['path']) }}"
                            alt="{{ $gameShots['ats_convoy']['alt'] }}"
                            width="{{ $gameShots['ats_convoy']['width'] }}"
                            height="{{ $gameShots['ats_convoy']['height'] }}"
                            loading="eager"
                            class="h-36 w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#080B12] via-[#080B12]/40 to-transparent"></div>
                        <div class="absolute bottom-2.5 left-3 right-3 flex items-center justify-between">
                            <div>
                                <span class="rounded bg-amber-400 px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[#080B12]">ATS</span>
                                <p class="mt-1 font-display text-sm font-bold uppercase tracking-wide text-white">American Truck Simulator</p>
                            </div>
                            <span class="font-mono text-[10px] text-amber-300">Interstate Hauls</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3-Part Architecture Strip (Web Platform + Sync Infrastructure + Desktop Client) --}}
        <div class="mt-16 grid grid-cols-1 gap-6 border-t border-gms-border/80 pt-10 md:grid-cols-3">
            @foreach ($hero['architecture_pillars'] as $pillar)
                <div class="rounded-xl border border-gms-border bg-gms-card/85 p-5 backdrop-blur-md transition hover:border-gms-cyan/50">
                    <p class="font-mono text-xs font-bold tracking-wider text-gms-cyan">
                        {{ $pillar['code'] }}
                    </p>
                    <h2 class="mt-1.5 font-display text-xl font-bold uppercase tracking-wide text-gms-text">
                        {{ $pillar['title'] }}
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-gms-muted">
                        {{ $pillar['summary'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
