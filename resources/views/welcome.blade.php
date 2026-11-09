@php
    $site = config('landing.site');
    $seo = $site['seo'];
    $images = config('landing.images');
    $navCopy = config('landing.content.navigation');
    $canonicalUrl = url('/');
    $ogImageUrl = asset($images['og_image']['path']);
    $faviconUrl = asset($images['favicon']['path']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="{{ $seo['robots'] }}">
        <meta name="theme-color" content="{{ $seo['theme_color'] }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        {{-- Favicons --}}
        <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        {{-- Open Graph Metadata --}}
        <meta property="og:type" content="{{ $seo['og_type'] }}">
        <meta property="og:site_name" content="{{ $site['name'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta property="og:image:width" content="{{ $images['og_image']['width'] }}">
        <meta property="og:image:height" content="{{ $images['og_image']['height'] }}">
        <meta property="og:image:alt" content="{{ $images['og_image']['alt'] }}">

        {{-- Twitter / X Card Metadata --}}
        <meta name="twitter:card" content="{{ $seo['twitter_card'] }}">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">

        {{-- Gaming & Simulation Typography (Rajdhani + Chakra Petch display headings, Plus Jakarta Sans UI body, JetBrains Mono telemetry) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:600,700|jetbrains-mono:500,700|plus-jakarta-sans:400,500,600,700|rajdhani:600,700&display=swap" rel="stylesheet" />

        {{-- Vite CSS & JS --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Valid JSON-LD Structured Data (Organization & SoftwareApplication) --}}
        <script type="application/ld+json">
            {!! json_encode(array_merge($site['structured_data']['organization'], ['url' => $canonicalUrl]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/ld+json">
            {!! json_encode(array_merge($site['structured_data']['software_application'], ['url' => $canonicalUrl]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </head>
    <body class="min-h-screen bg-[#080B12] font-sans text-gms-text antialiased selection:bg-gms-cyan selection:text-[#080B12] overflow-x-hidden">
        {{-- Skip to Content Link for Keyboard Accessibility --}}
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-gms-cyan focus:px-4 focus:py-2 focus:text-sm focus:font-extrabold focus:text-[#080B12]"
        >
            {{ $navCopy['skip_to_content'] }}
        </a>

        {{-- SECTION 1: ANNOUNCEMENT BAR --}}
        <x-landing.announcement-bar />

        {{-- SECTION 2: STICKY NAVIGATION --}}
        <x-landing.navbar />

        <main id="main-content">
            {{-- SECTION 3: HERO --}}
            <x-landing.hero-section />

            {{-- SECTION 4: THE PROBLEM --}}
            <x-landing.problem-section />

            {{-- SECTION 5: HOW IT WORKS --}}
            <x-landing.how-it-works-section />

            {{-- SECTION 6: FEATURES --}}
            <x-landing.features-section />

            {{-- SECTION 7: FOR VTC LEADERS AND MEMBERS --}}
            <x-landing.roles-section />

            {{-- SECTION 8: DOWNLOAD GHOSTMODSYNC CLIENT --}}
            <x-landing.client-download-section />

            {{-- SECTION 9: PRICING – COMING SOON --}}
            <x-landing.pricing-section />

            {{-- SECTION 10: TRUST AND COMMUNITY --}}
            <x-landing.trust-community-section />

            {{-- SECTION 11: FAQ --}}
            <x-landing.faq-section />

            {{-- SECTION 12: FINAL CALL TO ACTION --}}
            <x-landing.final-cta-section />
        </main>

        {{-- SECTION 13: FOOTER --}}
        <x-landing.footer />

        {{-- COOKIE CONSENT --}}
        <x-landing.cookie-consent />
    </body>
</html>
