@php
    $footerCopy = config('landing.content.footer');
    $logo = config('landing.images.logo_full');
    $platformLinks = config('landing.navigation.footer_platform', []);
    $legalLinks = config('landing.navigation.footer_legal', []);
    $socialPlatforms = config('landing.socials.platforms', []);
    $copyright = str_replace(':year', (string) now()->year, $footerCopy['copyright_template']);
@endphp

<footer class="bg-[#080B12] text-gms-muted" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>

    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            {{-- Brand & Description --}}
            <div class="lg:col-span-5">
                <a
                    href="{{ route('welcome') }}"
                    class="inline-flex items-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    <img
                        src="{{ asset($logo['path']) }}"
                        alt="{{ $logo['alt'] }}"
                        width="{{ $logo['width'] }}"
                        height="{{ $logo['height'] }}"
                        loading="lazy"
                        class="h-10 w-auto"
                    />
                </a>

                <p class="mt-4 max-w-md text-sm leading-relaxed text-gms-muted">
                    {{ $footerCopy['description'] }}
                </p>

                <p class="mt-3 font-mono text-xs font-semibold text-gms-cyan">
                    "{{ config('landing.site.tagline') }}"
                </p>
            </div>

            {{-- Platform Navigation --}}
            <div class="lg:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gms-text">
                    {{ $footerCopy['platform_heading'] }}
                </h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($platformLinks as $link)
                        <li>
                            <a
                                href="{{ $link['href'] }}"
                                class="transition hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan rounded"
                            >
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Legal & Support --}}
            <div class="lg:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gms-text">
                    {{ $footerCopy['legal_heading'] }}
                </h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($legalLinks as $legal)
                        <li>
                            <a
                                href="{{ route($legal['route']) }}"
                                class="transition hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan rounded"
                            >
                                {{ $legal['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Community & Social Links --}}
            <div class="lg:col-span-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gms-text">
                    {{ $footerCopy['community_heading'] }}
                </h3>
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($socialPlatforms as $social)
                        <li>
                            <a
                                href="{{ $social['url'] }}"
                                aria-label="{{ $social['label'] }}"
                                class="inline-flex items-center rounded-lg border border-gms-border bg-gms-card px-3 py-1.5 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                            >
                                {{ $social['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-12 border-t border-gms-border pt-8 flex flex-col items-center justify-between gap-4 sm:flex-row text-xs text-gms-muted">
            <p>{{ $copyright }}</p>
            <p>{{ config('landing.site.supporting_tagline') }}</p>
        </div>
    </div>
</footer>
