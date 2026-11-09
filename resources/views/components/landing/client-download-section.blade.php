@php
    $clientCopy = config('landing.content.client_download');
    $clientConfig = config('landing.site.client');
    $clientMockup = config('landing.images.client_mockup');
@endphp

<section
    id="download-client"
    aria-labelledby="download-client-heading"
    class="border-b border-gms-border bg-[#080B12] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12">
            {{-- Left Column: Copy, Non-Fabricated Download State & Specifications --}}
            <div class="lg:col-span-5">
                <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                    {{ $clientCopy['eyebrow'] }}
                </p>

                <h2
                    id="download-client-heading"
                    class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
                >
                    {{ $clientCopy['heading'] }}
                </h2>

                <p class="mt-4 text-base leading-relaxed text-gms-muted">
                    {{ $clientCopy['description'] }}
                </p>

                {{-- Download Action Box (Honest Coming Soon State unless official URL is configured) --}}
                {{-- TODO: Connect the official client release/download URL in config/landing/site.php when available. --}}
                <div class="mt-7 rounded-2xl border border-gms-border bg-gms-card p-6">
                    @if (!empty($clientConfig['available']) && !empty($clientConfig['download_url']))
                        <a
                            href="{{ $clientConfig['download_url'] }}"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-gms-cyan px-6 py-3.5 text-base font-extrabold text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                        >
                            {{ $clientCopy['button_label'] }}
                        </a>
                    @else
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <span
                                role="button"
                                aria-disabled="true"
                                class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-gms-border bg-[#101522] px-5 py-3 text-sm font-bold text-gms-muted opacity-80"
                            >
                                <svg class="h-4 w-4 text-gms-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $clientCopy['button_label'] }} (Unavailable)</span>
                            </span>

                            <span class="inline-flex items-center gap-2 rounded-full border border-amber-400/40 bg-amber-400/10 px-3.5 py-1.5 text-xs font-bold text-amber-300">
                                <span class="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true"></span>
                                <span>{{ $clientCopy['coming_soon_badge'] }}</span>
                            </span>
                        </div>
                    @endif

                    <p class="mt-4 border-t border-gms-border pt-4 text-xs font-medium leading-relaxed text-gms-text">
                        {{ $clientCopy['required_note'] }}
                    </p>
                </div>

                {{-- Technical Placeholders (Version, OS, System Requirements, Installation, Release Notes) --}}
                <dl class="mt-6 space-y-3">
                    @foreach ($clientCopy['specs'] as $spec)
                        <div class="rounded-xl border border-gms-border bg-[#101522] px-4 py-3">
                            <dt class="font-mono text-xs font-bold uppercase tracking-wider text-gms-cyan">
                                {{ $spec['label'] }}
                            </dt>
                            <dd class="mt-1 text-xs leading-relaxed text-gms-muted sm:text-sm">
                                {{ $spec['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Right Column: Desktop Client Interface Mockup --}}
            <div class="lg:col-span-7">
                <div class="overflow-hidden rounded-2xl border border-gms-border bg-gms-card p-3 shadow-2xl">
                    <img
                        src="{{ asset($clientMockup['path']) }}"
                        alt="{{ $clientMockup['alt'] }}"
                        width="{{ $clientMockup['width'] }}"
                        height="{{ $clientMockup['height'] }}"
                        loading="lazy"
                        class="h-auto w-full rounded-xl"
                    />
                </div>
            </div>
        </div>
    </div>
</section>
