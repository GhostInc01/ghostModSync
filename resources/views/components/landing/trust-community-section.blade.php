@php
    $trust = config('landing.content.trust_community');
    $supportedGames = config('landing.site.supported_games', []);
    $gameShots = config('landing.images.game_screenshots');
    $discordUrl = config('landing.socials.discord_invite');
    $youtubeDemoUrl = config('landing.socials.youtube_demo_url');
@endphp

<section
    id="community"
    aria-labelledby="trust-community-heading"
    class="border-b border-gms-border bg-[#080B12] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $trust['eyebrow'] }}
            </p>
            <h2
                id="trust-community-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $trust['heading'] }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $trust['subtitle'] }}
            </p>
        </div>

        {{-- Featured Supported Games Showcase Cards (ETS2 & ATS In-Game Screenshots) --}}
        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2">
            {{-- Euro Truck Simulator 2 Card --}}
            <article class="group overflow-hidden rounded-2xl border border-gms-cyan/40 bg-gms-card shadow-2xl">
                <div class="relative h-60 w-full overflow-hidden">
                    <img
                        src="{{ asset($gameShots['ets2_convoy']['path']) }}"
                        alt="{{ $gameShots['ets2_convoy']['alt'] }}"
                        width="{{ $gameShots['ets2_convoy']['width'] }}"
                        height="{{ $gameShots['ets2_convoy']['height'] }}"
                        loading="lazy"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gms-card via-transparent to-transparent"></div>
                    <span class="absolute left-4 top-4 rounded-lg bg-gms-cyan px-3 py-1 font-mono text-xs font-extrabold uppercase text-[#080B12]">
                        ETS2 • Confirmed Support
                    </span>
                </div>
                <div class="p-6">
                    <h3 class="font-display text-2xl font-bold uppercase tracking-wide text-gms-text">
                        Euro Truck Simulator 2
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-gms-muted">
                        Built for European VTC fleets—synchronise private company skins, trailer packs, sound mods, and supported Steam Workshop items into your local ETS2 mod folder.
                    </p>
                </div>
            </article>

            {{-- American Truck Simulator Card --}}
            <article class="group overflow-hidden rounded-2xl border border-amber-400/40 bg-gms-card shadow-2xl">
                <div class="relative h-60 w-full overflow-hidden">
                    <img
                        src="{{ asset($gameShots['ats_convoy']['path']) }}"
                        alt="{{ $gameShots['ats_convoy']['alt'] }}"
                        width="{{ $gameShots['ats_convoy']['width'] }}"
                        height="{{ $gameShots['ats_convoy']['height'] }}"
                        loading="lazy"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gms-card via-transparent to-transparent"></div>
                    <span class="absolute left-4 top-4 rounded-lg bg-amber-400 px-3 py-1 font-mono text-xs font-extrabold uppercase text-[#080B12]">
                        ATS • Confirmed Support
                    </span>
                </div>
                <div class="p-6">
                    <h3 class="font-display text-2xl font-bold uppercase tracking-wide text-gms-text">
                        American Truck Simulator
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-gms-muted">
                        Built for North American heavy-haul and interstate VTC convoys—keep every driver on matching custom rigs, cargo packs, and verified mod versions.
                    </p>
                </div>
            </article>
        </div>

        {{-- Non-Fabricated Transparent Placeholders (Partner VTCs, Testimonials, Statistics, Featured Groups) --}}
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($trust['placeholders'] as $placeholder)
                <div class="flex flex-col justify-between rounded-2xl border border-dashed border-gms-border bg-[#101522]/80 p-6">
                    <div>
                        <span class="inline-flex items-center rounded-md border border-gms-border bg-gms-card px-2.5 py-1 font-mono text-[11px] font-semibold text-gms-muted">
                            {{ $placeholder['status'] }}
                        </span>
                        <h3 class="mt-4 font-display text-xl font-bold uppercase tracking-wide text-gms-text">
                            {{ $placeholder['category'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gms-muted">
                            {{ $placeholder['description'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Discord Community CTA & Product Demo Video Placeholder --}}
        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Discord Community Banner --}}
            {{-- TODO: Replace the placeholder Discord URL in config/landing/socials.php with the official community invite. --}}
            <div
                id="community-discord"
                class="flex flex-col justify-between rounded-2xl border border-gms-purple/40 bg-gradient-to-br from-[#151D2B] to-[#101522] p-8 lg:col-span-7"
            >
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-gms-purple/40 bg-gms-purple/15 px-3 py-1 font-mono text-xs font-bold text-purple-300">
                        OFFICIAL COMMUNITY HUB
                    </span>
                    <h3 class="mt-4 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-4xl">
                        {{ $trust['discord']['heading'] }}
                    </h3>
                    <p class="mt-3 text-base leading-relaxed text-gms-muted">
                        {{ $trust['discord']['copy'] }}
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a
                        href="{{ $discordUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gms-purple px-6 py-3.5 font-display text-base font-bold uppercase tracking-wider text-white transition hover:bg-purple-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                    >
                        <span>{{ $trust['discord']['button'] }}</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <span class="text-xs text-gms-muted">
                        {{ $trust['discord']['todo_note'] }}
                    </span>
                </div>
            </div>

            {{-- Optional YouTube Trailer / Demo Placeholder --}}
            {{-- TODO: Add the official product demonstration video in config/landing/socials.php when available. --}}
            <div class="flex flex-col justify-between rounded-2xl border border-gms-border bg-gms-card p-8 lg:col-span-5">
                <div>
                    <span class="inline-flex items-center rounded-full border border-gms-cyan/30 bg-gms-cyan/10 px-3 py-1 font-mono text-xs font-bold text-gms-cyan">
                        {{ $trust['video_demo']['badge'] }}
                    </span>
                    <h3 class="mt-4 font-display text-2xl font-bold uppercase tracking-wide text-gms-text">
                        {{ $trust['video_demo']['heading'] }}
                    </h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-gms-muted">
                        {{ $trust['video_demo']['description'] }}
                    </p>
                </div>

                @if (!empty($youtubeDemoUrl))
                    <div class="mt-6">
                        <a
                            href="{{ $youtubeDemoUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-bold text-gms-cyan hover:underline"
                        >
                            Watch Product Demo &rarr;
                        </a>
                    </div>
                @else
                    <div class="mt-6 flex h-28 items-center justify-center rounded-xl border border-dashed border-gms-border bg-[#080B12] px-4 text-center">
                        <p class="font-mono text-xs text-gms-muted">
                            [ Video Demo Placeholder — Pending Official Walkthrough Recording ]
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
