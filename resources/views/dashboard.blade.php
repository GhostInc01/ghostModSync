<x-dashboard.layout title="Dashboard" :attention-count="$attentionCount ?? null">
    @php
        $syncTone = fn (?\App\Enums\SyncStatus $s) => match ($s) {
            \App\Enums\SyncStatus::InSync => 'cyan',
            \App\Enums\SyncStatus::Syncing => 'purple',
            \App\Enums\SyncStatus::OutOfSync, \App\Enums\SyncStatus::PendingApproval => 'amber',
            \App\Enums\SyncStatus::Failed => 'red',
            default => 'neutral',
        };
    @endphp

    @if ($loadFailed)
        <div role="alert" class="rounded-xl border border-red-400/40 bg-red-400/10 p-5">
            <h2 class="font-display text-lg font-bold text-white">We couldn't load your dashboard</h2>
            <p class="mt-1 text-sm text-gms-muted">Something went wrong on our side. The error has been logged.</p>
            <a href="{{ route('dashboard') }}" class="mt-4 inline-flex min-h-[44px] items-center rounded-lg border border-gms-border bg-gms-card px-4 text-sm font-semibold text-gms-text hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Try again</a>
        </div>
    @else
        {{-- Welcome --}}
        <section class="rounded-xl border border-gms-border bg-gradient-to-r from-gms-card to-gms-surface p-5 sm:p-6">
            <h2 class="font-display text-2xl font-bold tracking-wide text-white sm:text-3xl">Welcome back, {{ $user->name }}!</h2>
            <p class="mt-1 text-sm text-gms-muted">Your gaming groups, convoy profiles and mod synchronisation activities, all in one place.</p>
            @if ($user->last_login_at)
                <p class="mt-2 text-xs text-gms-muted">Last sign-in {{ $user->last_login_at->diffForHumans() }}.</p>
            @endif
        </section>

        {{-- Summary cards (real, user-scoped counts only) --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-dashboard.stat-card label="Groups joined" :value="$kpis['groups']" icon="users" />
            <x-dashboard.stat-card label="Convoy profiles available" :value="$kpis['profiles']" icon="truck" tone="purple" />
            <x-dashboard.stat-card label="Mods available" :value="$kpis['mods']" icon="cube" />
            <x-dashboard.stat-card label="Registered devices" :value="$kpis['devices']" icon="device" tone="purple" />
            <x-dashboard.stat-card label="Last synchronisation" :value="$kpis['last_sync'] ? $kpis['last_sync']->diffForHumans(null, true, true) : '—'" icon="sync" tone="amber" />
        </div>

        {{-- Onboarding --}}
        @if (! ($checklist['joined_group'] && $checklist['registered_device'] && $checklist['synced']))
            <x-dashboard.panel title="Getting started" description="Complete these steps to run your first convoy sync.">
                <ol class="space-y-3 text-sm">
                    @foreach ([
                        ['done' => $checklist['joined_group'], 'text' => 'Join or create a VTC group', 'href' => route('groups.discover')],
                        ['done' => $checklist['registered_device'], 'text' => 'Sign in from the Windows desktop client to register a device', 'href' => route('devices.index')],
                        ['done' => $checklist['synced'], 'text' => 'Complete your first successful sync', 'href' => null],
                    ] as $step)
                        <li class="flex items-center gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border {{ $step['done'] ? 'border-gms-cyan bg-gms-cyan/20 text-gms-cyan' : 'border-gms-border text-gms-muted' }}">
                                @if ($step['done'])<x-dashboard.icon name="check" class="h-4 w-4" />@endif
                            </span>
                            <span class="{{ $step['done'] ? 'text-gms-muted line-through' : 'text-gms-text' }}">
                                @if ($step['href'] && ! $step['done'])
                                    <a href="{{ $step['href'] }}" class="hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">{{ $step['text'] }}</a>
                                @else
                                    {{ $step['text'] }}
                                @endif
                                @unless ($step['done'])<span class="sr-only"> (not completed)</span>@endunless
                            </span>
                        </li>
                    @endforeach
                </ol>
            </x-dashboard.panel>
        @endif

        {{-- Needs attention --}}
        <div id="attention" class="scroll-mt-24">
            @if ($invitations->isNotEmpty() || $joinRequests->isNotEmpty() || $approvals->isNotEmpty())
                <x-dashboard.panel title="Needs your attention">
                    <ul class="divide-y divide-gms-border">
                        @foreach ($invitations as $invitation)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="min-w-0 break-words text-sm">Invitation to join <strong class="text-white">{{ $invitation->group->name }}</strong> as {{ $invitation->role->label() }}</span>
                                <a href="{{ route('groups.invitations.accept', $invitation->token) }}" class="inline-flex min-h-[44px] shrink-0 items-center rounded-lg bg-gms-cyan px-4 text-sm font-bold text-[#080B12] hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Review invitation</a>
                            </li>
                        @endforeach
                        @foreach ($joinRequests as $request)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="min-w-0 break-words text-sm">Your request to join <strong class="text-white">{{ $request->group->name }}</strong> is awaiting review</span>
                                <x-dashboard.badge tone="amber">Pending</x-dashboard.badge>
                            </li>
                        @endforeach
                        @foreach ($approvals as $approval)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="min-w-0 break-words text-sm"><strong class="text-white">{{ $approval->user->name }}</strong> requests sync approval for {{ $approval->convoyProfile->name }} ({{ $approval->convoyProfile->group->name }})</span>
                                <a href="{{ route('groups.show', $approval->convoyProfile->group) }}" class="shrink-0 text-sm font-semibold text-gms-cyan hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Review</a>
                            </li>
                        @endforeach
                    </ul>
                </x-dashboard.panel>
            @else
                <p class="rounded-xl border border-gms-border bg-gms-card/60 px-5 py-3 text-sm text-gms-muted">Nothing needs your attention right now.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                {{-- Groups --}}
                <x-dashboard.panel title="My groups" action-href="{{ route('groups.discover') }}" action-label="Discover groups">
                    @if ($memberships->isEmpty())
                        <x-dashboard.empty-state icon="users" message="You haven't joined a group yet. Discover a public VTC or create your own to start syncing mods." :href="route('groups.discover')" cta="Discover Groups" />
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach ($memberships as $membership)
                                @php
                                    $group = $membership->group;
                                    $games = $groupGames->get($group->id, collect());
                                @endphp
                                <a href="{{ route('groups.show', $group) }}" class="block rounded-lg border border-gms-border bg-gms-surface p-4 transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                                    <div class="flex items-start gap-3">
                                        @if ($group->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($group->logo_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg border border-gms-border object-cover" loading="lazy">
                                        @else
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gms-cyan/10 font-display text-lg font-bold text-gms-cyan" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($group->tag, '[] ') ?: $group->name, 0, 2)) }}</span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-semibold text-white">{{ $group->name }}</p>
                                            <p class="font-mono text-xs text-gms-cyan">{{ $group->tag }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 line-clamp-2 text-xs text-gms-muted">{{ $group->description ?: 'No description provided.' }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                        <x-dashboard.badge tone="purple">{{ $membership->role->label() }}</x-dashboard.badge>
                                        <x-dashboard.badge tone="cyan">{{ $membership->status->label() }}</x-dashboard.badge>
                                        <x-dashboard.badge>{{ $group->visibility->label() }}</x-dashboard.badge>
                                        @foreach ($games as $gameName)
                                            <x-dashboard.badge>{{ $gameName }}</x-dashboard.badge>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-gms-muted">{{ $group->active_members_count }} {{ \Illuminate\Support\Str::plural('member', $group->active_members_count) }} · {{ $group->sync_policy->label() }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.panel>

                {{-- Convoy profiles --}}
                <x-dashboard.panel title="Convoy profiles" description="Your latest reported sync status per profile.">
                    @if ($profiles->isEmpty())
                        <x-dashboard.empty-state icon="truck" message="No active convoy profiles in your groups yet. Profiles appear here once a group publishes one." />
                    @else
                        <ul class="divide-y divide-gms-border">
                            @foreach ($profiles as $profile)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ $profile->name }}</p>
                                        <p class="text-xs text-gms-muted">{{ $profile->group->name }} · {{ $profile->game->name }} · <span class="font-mono">r{{ $profile->revision }}</span></p>
                                    </div>
                                    @if ($profile->mySession)
                                        <x-dashboard.badge :tone="$syncTone($profile->mySession->status)">{{ $profile->mySession->status->label() }}</x-dashboard.badge>
                                    @else
                                        <x-dashboard.badge>Not synced yet</x-dashboard.badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>

                {{-- Recent mods --}}
                <x-dashboard.panel title="Recent mod updates" description="Latest ready versions across your groups.">
                    @if ($recentMods->isEmpty())
                        <x-dashboard.empty-state icon="cube" message="No mod versions have been published in your groups yet." />
                    @else
                        <ul class="divide-y divide-gms-border">
                            @foreach ($recentMods as $version)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ $version->mod->name }}</p>
                                        <p class="text-xs text-gms-muted">{{ $version->mod->group->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono text-xs text-gms-cyan">{{ $version->version_number }}</span>
                                        @if ($version->published_at)
                                            <p class="text-xs text-gms-muted">{{ $version->published_at->diffForHumans() }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>
            </div>

            <div class="space-y-6">
                {{-- Synchronisation overview --}}
                <x-dashboard.panel title="Synchronisation overview">
                    @if (! $sync['has_devices'] && ! $sync['has_sessions'])
                        <x-dashboard.empty-state icon="sync" message="Synchronisation is performed by the Windows desktop client. No device has registered and no sync has been reported yet — nothing is simulated here." :href="route('devices.index')" cta="View devices" />
                    @else
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gms-muted">Latest status</dt>
                                <dd>
                                    @if ($sync['latest'])
                                        <x-dashboard.badge :tone="$syncTone($sync['latest']->status)">{{ $sync['latest']->status->label() }}</x-dashboard.badge>
                                    @else
                                        <span class="text-gms-muted">No sync reported</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gms-muted">Last synchronised</dt>
                                <dd class="text-white">{{ $kpis['last_sync'] ? $kpis['last_sync']->diffForHumans() : 'Never' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gms-muted">Failed attempts</dt>
                                <dd class="{{ $sync['failed'] ? 'text-red-300' : 'text-white' }}">{{ $sync['failed'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gms-muted">My approvals pending</dt>
                                <dd class="text-white">{{ $sync['my_pending_approvals'] }}</dd>
                            </div>
                            @if ($sync['awaiting_my_review'])
                                <div class="flex items-center justify-between gap-2">
                                    <dt class="text-gms-muted">Awaiting my review</dt>
                                    <dd class="text-gms-amber">{{ $sync['awaiting_my_review'] }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if ($sync['latest']?->error_message)
                            <p class="mt-3 text-xs text-red-300">{{ \Illuminate\Support\Str::limit($sync['latest']->error_message, 140) }}</p>
                        @endif
                    @endif
                </x-dashboard.panel>

                {{-- Sync activity --}}
                <x-dashboard.panel title="Recent sync sessions">
                    @if ($recentSessions->isEmpty())
                        <x-dashboard.empty-state icon="sync" message="No sync sessions yet. They appear once the desktop client reports in." />
                    @else
                        <ul class="space-y-3">
                            @foreach ($recentSessions as $session)
                                <li class="text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate font-semibold text-white">{{ $session->convoyProfile->name }}</span>
                                        <x-dashboard.badge :tone="$syncTone($session->status)">{{ $session->status->label() }}</x-dashboard.badge>
                                    </div>
                                    <p class="text-xs text-gms-muted">{{ $session->device?->device_name ?? 'Unknown device' }} · {{ $session->updated_at->diffForHumans() }}</p>
                                    @if ($session->error_message)
                                        <p class="mt-1 text-xs text-red-300">{{ \Illuminate\Support\Str::limit($session->error_message, 120) }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>

                {{-- Recent activity (audit log) --}}
                <x-dashboard.panel title="Recent activity">
                    @if ($activity->isEmpty())
                        <x-dashboard.empty-state icon="check" message="No recorded activity yet. Joining groups, publishing mods and syncing will show up here." />
                    @else
                        <ul class="space-y-3">
                            @foreach ($activity as $event)
                                <li class="text-sm">
                                    <p class="text-white">{{ $event['label'] }}@if ($event['group']) <span class="text-gms-muted">· {{ $event['group'] }}</span>@endif</p>
                                    <p class="text-xs text-gms-muted">{{ $event['at']->diffForHumans() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>

                {{-- Devices --}}
                <x-dashboard.panel title="My devices" action-href="{{ route('devices.index') }}" action-label="Manage">
                    @if ($devices->isEmpty())
                        <x-dashboard.empty-state icon="device" message="No devices registered. Sign in from the Windows desktop client to register one." />
                    @else
                        <ul class="space-y-3">
                            @foreach ($devices as $device)
                                <li class="flex items-center justify-between gap-2 text-sm">
                                    <span class="truncate font-semibold text-white">{{ $device->device_name }}</span>
                                    <span class="shrink-0 text-xs text-gms-muted">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never seen' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>
            </div>
        </div>
    @endif
</x-dashboard.layout>
