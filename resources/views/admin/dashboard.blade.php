<x-admin.layout title="Platform Overview">
    @php
        $formatBytes = function (int $bytes): string {
            if ($bytes <= 0) {
                return '0 B';
            }
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);

            return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
        };
    @endphp

    <section class="rounded-xl border border-gms-border bg-gradient-to-r from-gms-card to-gms-surface p-5 sm:p-6">
        <h2 class="font-display text-2xl font-bold tracking-wide text-white sm:text-3xl">Ghost ModSync Control Plane</h2>
        <p class="mt-1 text-sm text-gms-muted">Platform-wide administration, multi-VTC oversight, mod integrity monitoring, and audit governance.</p>
    </section>

    {{-- Top KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <x-dashboard.stat-card label="Registered users" :value="$users['total']" icon="users" />
        <x-dashboard.stat-card label="Active users (7d)" :value="$users['active_7d']" icon="user" tone="purple" />
        <x-dashboard.stat-card label="Total groups" :value="$groups['total']" icon="shield" />
        <x-dashboard.stat-card label="Approved group mods" :value="$mods['approved_mods']" icon="cube" tone="purple" />
        <x-dashboard.stat-card label="Published convoy profiles" :value="$convoys['published']" icon="truck" />
        <x-dashboard.stat-card label="S3 storage used" :value="$formatBytes($storage['used_bytes'])" icon="database" tone="amber" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Platform Health Breakdown --}}
            <x-dashboard.panel title="Platform health & queues" description="Live operational counters across all tenants.">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Verified users</p>
                        <p class="mt-1 font-display text-2xl font-bold text-white">{{ $users['verified'] }} / {{ $users['total'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Suspended accounts</p>
                        <p class="mt-1 font-display text-2xl font-bold {{ $users['suspended'] ? 'text-red-300' : 'text-white' }}">{{ $users['suspended'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Public / Private VTCs</p>
                        <p class="mt-1 font-display text-2xl font-bold text-white">{{ $groups['public'] }} / {{ $groups['private'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Archived VTCs</p>
                        <p class="mt-1 font-display text-2xl font-bold text-gms-amber">{{ $groups['archived'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Ready mod versions</p>
                        <p class="mt-1 font-display text-2xl font-bold text-gms-cyan">{{ $mods['ready_versions'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Pending mod approvals</p>
                        <p class="mt-1 font-display text-2xl font-bold {{ $mods['pending_approvals'] ? 'text-gms-amber' : 'text-white' }}">{{ $mods['pending_approvals'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Sync sessions (In Sync / Failed)</p>
                        <p class="mt-1 font-display text-2xl font-bold text-white">{{ $sync['in_sync'] }} / <span class="{{ $sync['failed'] ? 'text-red-300' : 'text-white' }}">{{ $sync['failed'] }}</span></p>
                    </div>
                    <div class="rounded-lg border border-gms-border bg-gms-surface p-3">
                        <p class="text-xs text-gms-muted">Open moderation reports</p>
                        <p class="mt-1 font-display text-2xl font-bold {{ $moderation['pending_reports'] ? 'text-gms-amber' : 'text-white' }}">{{ $moderation['pending_reports'] }}</p>
                    </div>
                </div>
            </x-dashboard.panel>

            {{-- Recent Failed Syncs --}}
            <x-dashboard.panel title="Recent failed synchronisation sessions" action-href="{{ route('admin.sync.index', ['status' => 'failed']) }}" action-label="Inspect all">
                @if ($recentFailedSyncs->isEmpty())
                    <x-dashboard.empty-state icon="sync" message="No failed synchronisation sessions recorded." />
                @else
                    <ul class="divide-y divide-gms-border text-sm">
                        @foreach ($recentFailedSyncs as $session)
                            <li class="py-3 first:pt-0 last:pb-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-white">{{ $session->convoyProfile?->name ?? 'Profile' }} ({{ $session->convoyProfile?->group?->name ?? 'VTC' }})</span>
                                    <x-dashboard.badge tone="red">{{ $session->status->label() }}</x-dashboard.badge>
                                </div>
                                <p class="text-xs text-gms-muted">{{ $session->user?->email }} · {{ $session->device?->device_name ?? 'Unknown device' }} · {{ $session->updated_at->diffForHumans() }}</p>
                                @if ($session->error_message)
                                    <p class="mt-1 text-xs text-red-300">{{ $session->error_message }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>

        <div>
            {{-- Recent Platform Audit Logs --}}
            <x-dashboard.panel title="Recent audit events" action-href="{{ route('admin.audit-logs.index') }}" action-label="Full log">
                @if ($recentAuditLogs->isEmpty())
                    <x-dashboard.empty-state icon="clipboard" message="No audit log entries recorded yet." />
                @else
                    <ul class="divide-y divide-gms-border text-sm">
                        @foreach ($recentAuditLogs as $log)
                            <li class="py-2.5 first:pt-0 last:pb-0">
                                <p class="font-mono text-xs text-gms-cyan">{{ $log->event }}</p>
                                <p class="text-xs text-gms-muted">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
