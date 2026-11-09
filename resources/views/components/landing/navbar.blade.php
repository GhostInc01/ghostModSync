@php
    $navCopy = config('landing.content.navigation');
    $navLinks = config('landing.navigation.header', []);
    $logo = config('landing.images.logo_full');
@endphp

<header
    x-data="{ mobileMenuOpen: false }"
    @keydown.escape.window="mobileMenuOpen = false"
    class="sticky top-0 z-40 border-b border-gms-border/80 bg-[#080B12]/90 backdrop-blur-md"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        {{-- Brand Logo --}}
        <a
            href="{{ route('welcome') }}"
            class="inline-flex items-center gap-2 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
        >
            <img
                src="{{ asset($logo['path']) }}"
                alt="{{ $logo['alt'] }}"
                width="{{ $logo['width'] }}"
                height="{{ $logo['height'] }}"
                class="h-10 w-auto"
            />
        </a>

        {{-- Desktop Navigation Links --}}
        <nav aria-label="Primary navigation" class="hidden lg:flex lg:items-center lg:gap-7">
            @foreach ($navLinks as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="rounded-md px-1.5 py-1 text-sm font-medium text-gms-muted transition hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Desktop Auth Actions (Server-rendered using existing Breeze session state) --}}
        <div class="hidden lg:flex lg:items-center lg:gap-3">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-gms-cyan px-4 py-2 text-sm font-bold text-[#080B12] shadow-[0_0_20px_rgba(0,240,255,0.25)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $navCopy['dashboard_cta'] }}
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gms-border bg-gms-card/60 px-4 py-2 text-sm font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $navCopy['login_cta'] }}
                </a>
                @if (Route::has('register'))
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-gms-cyan px-4 py-2 text-sm font-bold text-[#080B12] shadow-[0_0_20px_rgba(0,240,255,0.25)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                    >
                        {{ $navCopy['signup_cta'] }}
                    </a>
                @endif
            @endauth
        </div>

        {{-- Mobile Hamburger Toggle --}}
        <div class="flex lg:hidden">
            <button
                type="button"
                @click="mobileMenuOpen = !mobileMenuOpen"
                :aria-expanded="mobileMenuOpen.toString()"
                aria-controls="gms-mobile-menu"
                :aria-label="mobileMenuOpen ? '{{ $navCopy['mobile_close_label'] }}' : '{{ $navCopy['mobile_open_label'] }}'"
                class="inline-flex items-center justify-center rounded-lg border border-gms-border bg-gms-card p-2 text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
            >
                <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileMenuOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu Panel --}}
    <nav
        id="gms-mobile-menu"
        aria-label="Mobile navigation"
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="max-h-[calc(100vh-4.5rem)] overflow-y-auto border-t border-gms-border bg-[#101522] px-4 pb-6 pt-3 lg:hidden"
        style="display: none;"
    >
        <div class="flex flex-col space-y-1">
            @foreach ($navLinks as $item)
                <a
                    href="{{ $item['href'] }}"
                    @click="mobileMenuOpen = false"
                    class="rounded-lg px-3 py-2.5 text-base font-medium text-gms-text transition hover:bg-gms-card hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-2.5 border-t border-gms-border pt-4">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    @click="mobileMenuOpen = false"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-gms-cyan px-4 py-2.5 text-center text-sm font-bold text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $navCopy['dashboard_cta'] }}
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    @click="mobileMenuOpen = false"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-gms-border bg-gms-card px-4 py-2.5 text-center text-sm font-semibold text-gms-text transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                >
                    {{ $navCopy['login_cta'] }}
                </a>
                @if (Route::has('register'))
                    <a
                        href="{{ route('register') }}"
                        @click="mobileMenuOpen = false"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-gms-cyan px-4 py-2.5 text-center text-sm font-bold text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                    >
                        {{ $navCopy['signup_cta'] }}
                    </a>
                @endif
            @endauth
        </div>
    </nav>
</header>
