@php
    $roles = config('landing.content.roles');
    $leader = $roles['leader_panel'];
    $member = $roles['member_panel'];
    $gameShots = config('landing.images.game_screenshots');

    $leaderCtaHref = auth()->check() ? route('groups.create') : route('register');
    $memberCtaHref = auth()->check() ? route('dashboard') : route('register');
    $memberCtaLabel = auth()->check() ? $member['authenticated_cta'] : $member['cta'];
@endphp

<section
    aria-labelledby="roles-heading"
    class="border-b border-gms-border bg-[#101522] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $roles['eyebrow'] }}
            </p>
            <h2
                id="roles-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $roles['section_heading'] }}
            </h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
            {{-- PANEL A: FOR VTC LEADERS (With In-Game Mod Garage Banner) --}}
            <div
                id="{{ $leader['id'] }}"
                class="flex flex-col justify-between overflow-hidden rounded-2xl border border-gms-purple/50 bg-gms-card shadow-2xl"
            >
                <div>
                    <div class="relative h-52 w-full overflow-hidden border-b border-gms-border">
                        <img
                            src="{{ asset($gameShots['mod_garage']['path']) }}"
                            alt="{{ $gameShots['mod_garage']['alt'] }}"
                            width="{{ $gameShots['mod_garage']['width'] }}"
                            height="{{ $gameShots['mod_garage']['height'] }}"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-gms-card via-gms-card/40 to-transparent"></div>
                        <span class="absolute left-6 bottom-4 inline-flex items-center rounded-full border border-gms-purple/50 bg-[#080B12]/90 px-3.5 py-1 font-mono text-xs font-bold text-purple-300 backdrop-blur-md">
                            {{ $leader['badge'] }}
                        </span>
                    </div>

                    <div class="p-8 pt-6">
                        <h3 class="font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-4xl">
                            {{ $leader['heading'] }}
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-gms-muted sm:text-base">
                            {{ $leader['intro'] }}
                        </p>

                        <ul class="mt-6 space-y-3">
                            @foreach ($leader['points'] as $point)
                                <li class="flex items-start gap-3 text-sm text-gms-text sm:text-base">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gms-purple/20 text-purple-300" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="px-8 pb-8">
                    <a
                        href="{{ $leaderCtaHref }}"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-gms-cyan px-6 py-3.5 text-center font-display text-lg font-bold uppercase tracking-wider text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan sm:w-auto"
                    >
                        {{ $leader['cta'] }}
                    </a>
                </div>
            </div>

            {{-- PANEL B: FOR MEMBERS (With In-Game Convoy Highway Banner) --}}
            <div
                id="{{ $member['id'] }}"
                class="flex flex-col justify-between overflow-hidden rounded-2xl border border-gms-cyan/50 bg-gms-card shadow-2xl"
            >
                <div>
                    <div class="relative h-52 w-full overflow-hidden border-b border-gms-border">
                        <img
                            src="{{ asset($gameShots['ets2_convoy']['path']) }}"
                            alt="{{ $gameShots['ets2_convoy']['alt'] }}"
                            width="{{ $gameShots['ets2_convoy']['width'] }}"
                            height="{{ $gameShots['ets2_convoy']['height'] }}"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-gms-card via-gms-card/40 to-transparent"></div>
                        <span class="absolute left-6 bottom-4 inline-flex items-center rounded-full border border-gms-cyan/50 bg-[#080B12]/90 px-3.5 py-1 font-mono text-xs font-bold text-gms-cyan backdrop-blur-md">
                            {{ $member['badge'] }}
                        </span>
                    </div>

                    <div class="p-8 pt-6">
                        <h3 class="font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-4xl">
                            {{ $member['heading'] }}
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-gms-muted sm:text-base">
                            {{ $member['intro'] }}
                        </p>

                        <ul class="mt-6 space-y-3">
                            @foreach ($member['points'] as $point)
                                <li class="flex items-start gap-3 text-sm text-gms-text sm:text-base">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gms-cyan/20 text-gms-cyan" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="px-8 pb-8">
                    <a
                        href="{{ $memberCtaHref }}"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-gms-cyan bg-[#080B12] px-6 py-3.5 text-center font-display text-lg font-bold uppercase tracking-wider text-gms-cyan transition hover:bg-gms-cyan hover:text-[#080B12] focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan sm:w-auto"
                    >
                        {{ $memberCtaLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
