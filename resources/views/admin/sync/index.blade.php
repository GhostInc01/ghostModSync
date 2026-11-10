<x-admin.layout title="Synchronisation Monitoring">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.sync.index') }}" class="flex flex-wrap items-center gap-3">
            <select name="status" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
                <option value="">All session statuses</option>
                <option value="in_sync" @selected($status === 'in_sync')>In Sync</option>
                <option value="syncing" @selected($status === 'syncing')>Syncing</option>
                <option value="pending_approval" @selected($status === 'pending_approval')>Pending Approval</option>
                <option value="failed" @selected($status === 'failed')>Failed</option>
                <option value="rolled_back" @selected($status === 'rolled_back')>Rolled Back</option>
            </select>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] hover:bg-white">Filter</button>
            @if ($status !== '')
                <a href="{{ route('admin.sync.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gms-border px-4 text-sm font-semibold text-gms-text hover:text-gms-cyan">Reset</a>
            @endif
        </form>
    </section>

    <x-dashboard.panel title="Synchronisation sessions ({{ $sessions->total() }})">
        @if ($sessions->isEmpty())
            <x-dashboard.empty-state icon="sync" message="No synchronisation sessions match the current filter." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">User / Device</th>
                            <th class="px-4 py-3">Convoy Profile</th>
                            <th class="px-4 py-3">Revision</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Transfer</th>
                            <th class="py-3 pl-4">Diagnostics</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($sessions as $session)
                            <tr>
                                <td class="py-3 pr-4">
                                    <p class="font-semibold text-white">{{ $session->user?->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gms-muted">{{ $session->device?->device_name ?? 'Desktop Client' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-white">{{ $session->convoyProfile?->name ?? 'Profile' }}</p>
                                    <p class="text-xs text-gms-muted">{{ $session->convoyProfile?->group?->name ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gms-cyan">r{{ $session->profile_revision }}</td>
                                <td class="px-4 py-3">
                                    <x-dashboard.badge :tone="$session->status->value === 'in_sync' ? 'cyan' : ($session->status->value === 'failed' ? 'red' : 'amber')">
                                        {{ $session->status->value }}
                                    </x-dashboard.badge>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    {{ number_format(((int) $session->bytes_downloaded) / 1048576, 1) }} MB
                                    @if ($session->duration_ms)
                                        <span class="text-gms-muted">({{ number_format($session->duration_ms / 1000, 1) }}s)</span>
                                    @endif
                                </td>
                                <td class="py-3 pl-4 text-xs">
                                    @if ($session->error_message)
                                        <p class="text-red-300">[{{ $session->error_code ?? 'ERR' }}] {{ $session->error_message }}</p>
                                    @else
                                        <span class="text-gms-muted">{{ $session->updated_at?->diffForHumans() }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sessions->hasPages())
                <div class="mt-4 flex items-center justify-between text-xs text-gms-muted">
                    <span>Page {{ $sessions->currentPage() }} of {{ $sessions->lastPage() }}</span>
                    <div class="flex gap-3">
                        @if (! $sessions->onFirstPage()) <a href="{{ $sessions->previousPageUrl() }}" class="text-gms-cyan">Previous</a> @endif
                        @if ($sessions->hasMorePages()) <a href="{{ $sessions->nextPageUrl() }}" class="text-gms-cyan">Next</a> @endif
                    </div>
                </div>
            @endif
        @endif
    </x-dashboard.panel>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-dashboard.panel title="Pending approval-based sync requests ({{ $pendingApprovals->count() }})">
            @if ($pendingApprovals->isEmpty())
                <x-dashboard.empty-state icon="check" message="No pending sync approvals across the platform." />
            @else
                <div class="divide-y divide-gms-border text-sm">
                    @foreach ($pendingApprovals as $approval)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="font-semibold text-white">{{ $approval->user?->name }} · {{ $approval->convoyProfile?->name }}</p>
                                <p class="text-xs text-gms-muted">{{ $approval->device?->device_name }} · r{{ $approval->approved_revision }}</p>
                            </div>
                            <x-dashboard.badge tone="amber">Pending</x-dashboard.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.panel>

        <x-dashboard.panel title="Recent desktop client heartbeats">
            @if ($recentDevices->isEmpty())
                <x-dashboard.empty-state icon="device" message="No registered Windows desktop devices yet." />
            @else
                <div class="divide-y divide-gms-border text-sm">
                    @foreach ($recentDevices as $device)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="font-semibold text-white">{{ $device->device_name }}</p>
                                <p class="text-xs text-gms-muted">{{ $device->user?->name }} · Client v{{ $device->client_version ?? '—' }}</p>
                            </div>
                            <span class="font-mono text-xs text-gms-muted">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.panel>
    </div>
</x-admin.layout>
