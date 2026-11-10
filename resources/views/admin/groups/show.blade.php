<x-admin.layout :title="'Group · '.$group->name">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-dashboard.panel title="Group summary">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gms-muted">Name &amp; Tag</dt>
                        <dd class="font-semibold text-white">{{ $group->tag }} {{ $group->name }} <span class="font-mono text-xs text-gms-muted">({{ $group->slug }})</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Owner</dt>
                        <dd class="text-white">{{ $group->owner?->name }} ({{ $group->owner?->email }})</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Visibility &amp; Sync Policy</dt>
                        <dd class="text-white">{{ $group->visibility->label() }} · {{ $group->sync_policy->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Storage Quota</dt>
                        <dd class="font-mono text-xs text-white">{{ round($group->storage_quota_bytes / 1073741824, 1) }} GB</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-gms-muted">{{ $group->description ?: 'No description provided.' }}</p>
            </x-dashboard.panel>

            <x-dashboard.panel title="Members ({{ $group->memberships->count() }})">
                <ul class="divide-y divide-gms-border text-sm">
                    @foreach ($group->memberships as $m)
                        <li class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0">
                            <span class="text-white">{{ $m->user?->name }} <span class="text-xs text-gms-muted">({{ $m->user?->email }})</span></span>
                            <div class="flex gap-2">
                                <x-dashboard.badge tone="purple">{{ $m->role->label() }}</x-dashboard.badge>
                                <x-dashboard.badge>{{ $m->status->label() }}</x-dashboard.badge>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-dashboard.panel>
        </div>

        <div class="space-y-6">
            <x-dashboard.panel title="Group governance">
                @if ($group->trashed())
                    <form method="POST" action="{{ route('admin.groups.status.update', $group->id) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="restore">
                        <button type="submit" class="w-full rounded-lg bg-gms-cyan px-4 py-2.5 text-sm font-bold text-[#080B12]">Restore Archived Group</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.groups.status.update', $group->id) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="archive">
                        <input name="reason" type="text" maxlength="500" placeholder="Reason for archiving group"
                               class="w-full rounded-lg border border-gms-border bg-gms-surface px-3 py-2 text-sm text-white">
                        <button type="submit" class="w-full rounded-lg border border-red-400/40 bg-red-400/10 px-4 py-2.5 text-sm font-bold text-red-300">Archive / Suspend Group</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.groups.status.update', $group->id) }}" class="mt-4 space-y-3 border-t border-gms-border pt-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="update_quota">
                    <label class="block text-xs text-gms-muted">
                        Storage quota (GB)
                        <input name="storage_quota_gb" type="number" min="1" max="10000" value="{{ (int) round($group->storage_quota_bytes / 1073741824) }}"
                               class="mt-1 w-full rounded-lg border border-gms-border bg-gms-surface px-3 py-2 text-sm text-white">
                    </label>
                    <button type="submit" class="w-full rounded-lg border border-gms-border bg-gms-surface px-4 py-2 text-xs font-semibold text-white hover:border-gms-cyan">Update Quota</button>
                </form>
            </x-dashboard.panel>

            <x-dashboard.panel title="Group moderation reports ({{ $reports->count() }})">
                @if ($reports->isEmpty())
                    <x-dashboard.empty-state icon="shield" message="No moderation reports filed against this group." />
                @else
                    <ul class="space-y-3 text-xs">
                        @foreach ($reports as $report)
                            <li class="rounded border border-gms-border bg-gms-surface p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-white">{{ $report->reason }}</span>
                                    <x-dashboard.badge :tone="$report->status->value === 'pending' ? 'amber' : 'cyan'">{{ $report->status->label() }}</x-dashboard.badge>
                                </div>
                                @if ($report->details)
                                    <p class="mt-1 text-gms-muted">{{ $report->details }}</p>
                                @endif
                                @if ($report->status->value === 'pending')
                                    <form method="POST" action="{{ route('admin.moderation-reports.review', $report) }}" class="mt-2 flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <button name="status" value="resolved" class="rounded bg-gms-cyan px-2.5 py-1 font-bold text-[#080B12]">Resolve</button>
                                        <button name="status" value="dismissed" class="rounded border border-gms-border px-2.5 py-1 text-gms-muted">Dismiss</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
