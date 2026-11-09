<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }} — {{ config('landing.site.name') }}</title>
        <meta name="robots" content="noindex, follow">
        <link rel="icon" type="image/svg+xml" href="{{ asset(config('landing.images.favicon.path')) }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#080B12] font-sans text-gms-text antialiased">
        <x-landing.navbar />

        <main class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:px-8">
            {{-- TODO: Implement and connect the official legal documents before public launch. --}}
            <div class="rounded-2xl border border-gms-border bg-gms-card p-8 shadow-xl">
                <span class="inline-flex items-center rounded-full border border-amber-400/40 bg-amber-400/10 px-3 py-1 font-mono text-xs font-bold text-amber-300">
                    Placeholder Document — Pending Official Pre-Launch Publication
                </span>
                <h1 class="mt-4 text-3xl font-extrabold text-gms-text">
                    {{ $pageTitle }}
                </h1>
                <p class="mt-4 text-base leading-relaxed text-gms-muted">
                    {{ $pageSummary }}
                </p>
                <div class="mt-8 border-t border-gms-border pt-6">
                    <a
                        href="{{ route('welcome') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-gms-cyan px-5 py-2.5 text-sm font-extrabold text-[#080B12] transition hover:bg-white"
                    >
                        &larr; Return to GhostModsSync Home
                    </a>
                </div>
            </div>
        </main>

        <x-landing.footer />
    </body>
</html>
