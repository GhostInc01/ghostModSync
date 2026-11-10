@props(['title' => 'Platform Admin', 'subtitle' => null])

@php
    /** @var \App\Models\User $user */
    $user = Auth::user()->fresh();
    $adminAttentionCount = \App\Models\ModerationReport::query()
        ->where('status', \App\Enums\ModerationReportStatus::Pending->value)
        ->count()
        + \App\Models\GroupModApproval::query()
            ->where('status', \App\Enums\ModApprovalStatus::Pending->value)
            ->count();
    $nav = [
        ['label' => 'Overview', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'icon' => 'users'],
        ['label' => 'Groups (VTCs)', 'route' => 'admin.groups.index', 'active' => request()->routeIs('admin.groups.*'), 'icon' => 'shield'],
        ['label' => 'Mods & Catalogue', 'route' => 'admin.mods.index', 'active' => request()->routeIs('admin.mods.*'), 'icon' => 'cube'],
        ['label' => 'Convoy Profiles', 'route' => 'admin.convoys.index', 'active' => request()->routeIs('admin.convoys.*'), 'icon' => 'truck'],
        ['label' => 'Sync Monitoring', 'route' => 'admin.sync.index', 'active' => request()->routeIs('admin.sync.*'), 'icon' => 'sync'],
        ['label' => 'Storage & Quotas', 'route' => 'admin.storage.index', 'active' => request()->routeIs('admin.storage.*'), 'icon' => 'database'],
        ['label' => 'Reports & Exports', 'route' => 'admin.reports.index', 'active' => request()->routeIs('admin.reports.*'), 'icon' => 'chart'],
        ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'active' => request()->routeIs('admin.audit-logs.*'), 'icon' => 'clipboard'],
        ['label' => 'System & Games', 'route' => 'admin.settings.index', 'active' => request()->routeIs('admin.settings.*'), 'icon' => 'cog'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gms-bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} · Admin · {{ config('app.name', 'Ghost ModSync') }}</title>
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
            <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/60 lg:hidden" aria-hidden="true"></div>

            <aside id="admin-sidebar"
                   class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gms-border bg-gms-surface transition-transform duration-200 lg:translate-x-0"
                   :class="{ 'translate-x-0': sidebarOpen }"
                   aria-label="Admin Navigation">
                <div class="flex h-16 items-center justify-between gap-2 border-b border-gms-border px-4">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                        <img src="{{ asset('images/brand/logo-icon.svg') }}" alt="" class="h-8 w-8">
                        <div>
                            <span class="block font-display text-lg font-bold leading-none tracking-wide text-white">Ghost ModSync</span>
                            <span class="mt-0.5 inline-block rounded border border-gms-purple/40 bg-gms-purple/15 px-1.5 py-0.2 font-mono text-[10px] font-bold uppercase tracking-wider text-gms-purple">Platform Admin</span>
                        </div>
                    </a>
                    <button type="button" @click="sidebarOpen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gms-muted hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan lg:hidden"
                            aria-label="Close navigation">
                        <x-dashboard.icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @foreach ($nav as $item)
                        <x-dashboard.nav-item :href="route($item['route'])" :active="$item['active']" :icon="$item['icon']">
                            {{ $item['label'] }}
                        </x-dashboard.nav-item>
                    @endforeach
                </nav>

                <div class="space-y-1 border-t border-gms-border p-3">
                    <a href="{{ route('dashboard') }}" class="flex min-h-[40px] items-center gap-3 rounded-lg px-3 text-xs font-semibold text-gms-cyan transition hover:bg-gms-card hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                        <x-dashboard.icon name="home" class="h-4 w-4 shrink-0" />
                        Return to User Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-[40px] w-full items-center gap-3 rounded-lg px-3 text-xs font-semibold text-gms-muted transition hover:bg-gms-card hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                            <x-dashboard.icon name="logout" class="h-4 w-4 shrink-0" />
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gms-border bg-gms-bg/90 px-4 backdrop-blur sm:px-6">
                    <button type="button" @click="sidebarOpen = true"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gms-border bg-gms-card text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan lg:hidden"
                            aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" aria-label="Open navigation">
                        <x-dashboard.icon name="menu" class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <nav aria-label="Breadcrumb" class="hidden text-xs text-gms-muted sm:block">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-gms-cyan">Platform Admin</a>
                            <span aria-hidden="true">/</span> <span>{{ $title }}</span>
                        </nav>
                        <h1 class="truncate font-display text-xl font-bold tracking-wide text-white sm:text-2xl">{{ $title }}</h1>
                    </div>

                    <x-dashboard.badge tone="purple">{{ $user->platformRoleLabel() }}</x-dashboard.badge>

                    <a href="{{ route('admin.mods.index') }}"
                       class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gms-border bg-gms-card text-gms-muted transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                       aria-label="{{ $adminAttentionCount > 0 ? $adminAttentionCount.' moderation items pending review' : 'Moderation queue' }}">
                        <x-dashboard.icon name="bell" class="h-5 w-5" />
                        @if ($adminAttentionCount > 0)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gms-amber px-1 text-[11px] font-bold text-[#080B12]" aria-hidden="true">{{ $adminAttentionCount }}</span>
                        @endif
                    </a>

                    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                                class="flex min-h-[44px] items-center gap-2 rounded-lg border border-gms-border bg-gms-card px-3 text-sm font-semibold text-gms-text transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
                                aria-haspopup="menu" :aria-expanded="open.toString()">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gms-purple/30 font-mono text-xs text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="hidden max-w-[10rem] truncate sm:inline">{{ $user->name }}</span>
                            <x-dashboard.icon name="chevron-down" class="h-4 w-4 text-gms-muted" />
                        </button>
                        <div x-show="open" x-cloak x-transition
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-gms-border bg-gms-surface shadow-xl" role="menu">
                            <div class="border-b border-gms-border px-4 py-3">
                                <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gms-muted">{{ $user->email }}</p>
                                <p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-gms-purple">{{ $user->platformRoleLabel() }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan">User Dashboard</a>
                            <a href="{{ route('profile.edit') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan">Account Settings</a>
                            <a href="{{ route('welcome') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan">Public Website</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="block w-full px-4 py-2.5 text-left text-sm text-gms-text hover:bg-gms-card hover:text-gms-cyan">Log out</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main id="main-content" class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div role="status" class="rounded-xl border border-gms-cyan/40 bg-gms-cyan/10 px-4 py-3 text-sm text-gms-text">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="rounded-xl border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
