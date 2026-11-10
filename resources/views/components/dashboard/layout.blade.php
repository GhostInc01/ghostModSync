@props(['title' => 'Dashboard', 'subtitle' => null, 'attentionCount' => null])

@php
    $user = Auth::user();
    // A route makes a live link. A null route is a planned feature, shown as a non-clickable "Soon" item.
    $nav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'home'],
        ['label' => 'My Groups', 'route' => 'groups.index', 'active' => request()->routeIs('groups.*') && ! request()->routeIs('groups.discover'), 'icon' => 'users'],
        ['label' => 'Discover Groups', 'route' => 'groups.discover', 'active' => request()->routeIs('groups.discover'), 'icon' => 'search'],
        ['label' => 'My Mods', 'route' => null, 'active' => false, 'icon' => 'cube'],
        ['label' => 'Convoy Profiles', 'route' => null, 'active' => false, 'icon' => 'truck'],
        ['label' => 'Synchronisation', 'route' => null, 'active' => false, 'icon' => 'sync'],
        ['label' => 'My Devices', 'route' => 'devices.index', 'active' => request()->routeIs('devices.*'), 'icon' => 'device'],
        ['label' => 'Notifications', 'route' => null, 'active' => false, 'icon' => 'bell'],
        ['label' => 'Account Settings', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*'), 'icon' => 'user'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gms-bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} · {{ config('app.name', 'Ghost ModSync') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:600,700|jetbrains-mono:500,700|plus-jakarta-sans:400,500,600,700|rajdhani:600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gms-bg font-sans text-gms-text antialiased selection:bg-gms-cyan selection:text-[#080B12]">
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-gms-cyan focus:px-4 focus:py-2 focus:text-sm focus:font-extrabold focus:text-[#080B12]">
            Skip to content
        </a>

        <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen">
            {{-- Mobile backdrop --}}
            <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/60 lg:hidden" aria-hidden="true"></div>

            {{-- Sidebar --}}
            <aside id="dashboard-sidebar"
                   class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gms-border bg-gms-surface transition-transform duration-200 lg:translate-x-0"
                   :class="{ 'translate-x-0': sidebarOpen }"
                   aria-label="Primary">
                <div class="flex h-16 items-center justify-between gap-3 border-b border-gms-border px-5">
                    <a href="{{ route('welcome') }}" class="flex items-center gap-2 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                        <img src="{{ asset('images/brand/logo-icon.svg') }}" alt="" class="h-8 w-8">
                        <span class="font-display text-xl font-bold tracking-wide text-white">Ghost ModSync</span>
                    </a>
                    <button type="button" @click="sidebarOpen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gms-muted hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan lg:hidden"
                            aria-label="Close navigation">
                        <x-dashboard.icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @foreach ($nav as $item)
                        @if ($item['route'])
                            <x-dashboard.nav-item :href="route($item['route'])" :active="$item['active']" :icon="$item['icon']">
                                {{ $item['label'] }}
                            </x-dashboard.nav-item>
                        @else
                            <div class="flex min-h-[44px] cursor-not-allowed items-center gap-3 rounded-lg border-l-2 border-transparent px-3 text-sm font-semibold text-gms-muted/60" aria-disabled="true">
                                <x-dashboard.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span>{{ $item['label'] }}</span>
                                <span class="ml-auto rounded-full border border-gms-border px-2 py-0.5 text-[10px] uppercase tracking-wide">Soon</span>
                            </div>
                        @endif
                    @endforeach
                </nav>

                <div class="border-t border-gms-border p-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-[44px] w-full items-center gap-3 rounded-lg px-3 text-sm font-semibold text-gms-muted transition hover:bg-gms-card hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                            <x-dashboard.icon name="logout" class="h-5 w-5 shrink-0" />
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="lg:pl-64">
                {{-- Top bar --}}
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gms-border bg-gms-bg/90 px-4 backdrop-blur sm:px-6">
                    <button type="button" @click="sidebarOpen = true"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gms-border bg-gms-card text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan lg:hidden"
                            aria-controls="dashboard-sidebar" :aria-expanded="sidebarOpen.toString()" aria-label="Open navigation">
                        <x-dashboard.icon name="menu" class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <nav aria-label="Breadcrumb" class="hidden text-xs text-gms-muted sm:block">
                            <a href="{{ route('dashboard') }}" class="hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Ghost ModSync</a>
                            <span aria-hidden="true">/</span> <span>{{ $title }}</span>
                        </nav>
                        <h1 class="truncate font-display text-xl font-bold tracking-wide text-white sm:text-2xl">{{ $title }}</h1>
                    </div>

                    {{-- Attention indicator: a real count of invitations/approvals awaiting action (not a notification inbox). --}}
                    <a href="{{ route('dashboard') }}#attention"
                       class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gms-border bg-gms-card text-gms-muted transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                       aria-label="{{ $attentionCount ? ($attentionCount === 1 ? '1 item needs your attention' : $attentionCount.' items need your attention') : 'Items needing your attention' }}">
                        <x-dashboard.icon name="bell" class="h-5 w-5" />
                        @if ($attentionCount)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gms-amber px-1 text-[11px] font-bold text-[#080B12]" aria-hidden="true">{{ $attentionCount }}</span>
                        @endif
                    </a>

                    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                                class="flex min-h-[44px] items-center gap-2 rounded-lg border border-gms-border bg-gms-card px-3 text-sm font-semibold text-gms-text transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                                aria-haspopup="menu" :aria-expanded="open.toString()">
                            @if ($user->avatar_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="" class="h-7 w-7 rounded-full border border-gms-border object-cover">
                            @else
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gms-purple/30 font-mono text-xs text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            @endif
                            <span class="hidden max-w-[10rem] truncate sm:inline">{{ $user->name }}</span>
                            <x-dashboard.icon name="chevron-down" class="h-4 w-4 text-gms-muted" />
                        </button>
                        <div x-show="open" x-cloak x-transition
                             class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-gms-border bg-gms-surface shadow-xl" role="menu">
                            <div class="border-b border-gms-border px-4 py-3">
                                <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gms-muted">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan focus:bg-gms-card focus:outline-none">Account settings</a>
                            @can('access-admin-dashboard')
                                <a href="{{ route('admin.dashboard') }}" role="menuitem" class="block px-4 py-2.5 text-sm font-semibold text-gms-purple hover:bg-gms-card hover:text-gms-cyan focus:bg-gms-card focus:outline-none">Platform Admin</a>
                            @endcan
                            <a href="{{ route('welcome') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan focus:bg-gms-card focus:outline-none">Public website</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="block w-full px-4 py-2.5 text-left text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan focus:bg-gms-card focus:outline-none">Log out</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main id="main-content" class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div role="status" class="rounded-xl border border-gms-cyan/40 bg-gms-cyan/10 px-4 py-3 text-sm text-gms-text">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div role="alert" class="rounded-xl border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-200">{{ session('error') }}</div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
