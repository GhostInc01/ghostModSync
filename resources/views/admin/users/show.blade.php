<x-admin.layout :title="'User · '.$targetUser->name">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-dashboard.panel title="Account profile">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gms-muted">Name</dt>
                        <dd class="font-semibold text-white">{{ $targetUser->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Email</dt>
                        <dd class="text-white">{{ $targetUser->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Username / Steam ID</dt>
                        <dd class="font-mono text-xs text-white">{{ $targetUser->username ?? '—' }} / {{ $targetUser->steam_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Platform role</dt>
                        <dd><x-dashboard.badge :tone="$targetUser->isPlatformAdmin() ? 'purple' : 'neutral'">{{ $targetUser->platformRoleLabel() }}</x-dashboard.badge></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Account status</dt>
                        <dd>
                            @if ($targetUser->isSuspended())
                                <x-dashboard.badge tone="red">Suspended</x-dashboard.badge>
                                <p class="mt-1 text-xs text-red-300">{{ $targetUser->suspended_reason }}</p>
                            @else
                                <x-dashboard.badge tone="cyan">Active</x-dashboard.badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gms-muted">Registered / Last login</dt>
                        <dd class="text-xs text-white">{{ $targetUser->created_at?->toDateString() }} · {{ $targetUser->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                </dl>
            </x-dashboard.panel>

            <x-dashboard.panel title="Group memberships ({{ $targetUser->groupMemberships->count() }})">
                @if ($targetUser->groupMemberships->isEmpty())
                    <x-dashboard.empty-state icon="users" message="User does not belong to any VTC groups." />
                @else
                    <ul class="divide-y divide-gms-border text-sm">
                        @foreach ($targetUser->groupMemberships as $m)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0">
                                <span class="font-semibold text-white">{{ $m->group?->name ?? 'Archived Group' }}</span>
                                <div class="flex gap-2">
                                    <x-dashboard.badge tone="purple">{{ $m->role->label() }}</x-dashboard.badge>
                                    <x-dashboard.badge>{{ $m->status->label() }}</x-dashboard.badge>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>

            <x-dashboard.panel title="Registered desktop devices ({{ $targetUser->devices->count() }})">
                @if ($targetUser->devices->isEmpty())
                    <x-dashboard.empty-state icon="device" message="No desktop devices registered for this user." />
                @else
                    <ul class="divide-y divide-gms-border text-sm">
                        @foreach ($targetUser->devices as $device)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0">
                                <div>
                                    <p class="font-semibold text-white">{{ $device->device_name }} <span class="font-mono text-xs text-gms-muted">({{ $device->client_version ?? 'unknown' }})</span></p>
                                    <p class="text-xs text-gms-muted">{{ $device->os_version ?? 'Windows' }} · Last seen {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</p>
                                </div>
                                @if ($device->revoked_at)
                                    <x-dashboard.badge tone="red">Revoked</x-dashboard.badge>
                                @else
                                    <form method="POST" action="{{ route('admin.users.devices.revoke', [$targetUser, $device]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-400/40 bg-red-400/10 px-2.5 py-1 text-xs font-semibold text-red-300 hover:bg-red-400/20">Revoke</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>

        <div class="space-y-6">
            @if (Auth::id() !== $targetUser->id)
                <x-dashboard.panel title="Account moderation">
                    @if ($targetUser->isSuspended())
                        <form method="POST" action="{{ route('admin.users.status.update', $targetUser) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="reactivate">
                            <button type="submit" class="w-full rounded-lg bg-gms-cyan px-4 py-2.5 text-sm font-bold text-[#080B12] hover:bg-white">Reactivate Account</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.status.update', $targetUser) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="suspend">
                            <label class="block text-xs text-gms-muted">
                                Suspension reason
                                <input name="reason" type="text" required maxlength="500" placeholder="Reason for account suspension"
                                       class="mt-1 w-full rounded-lg border border-gms-border bg-gms-surface px-3 py-2 text-sm text-white">
                            </label>
                            <button type="submit" class="w-full rounded-lg border border-red-400/40 bg-red-400/10 px-4 py-2.5 text-sm font-bold text-red-300 hover:bg-red-400/20">Suspend Account</button>
                        </form>
                    @endif

                    @can('manage-platform-settings')
                        <form method="POST" action="{{ route('admin.users.status.update', $targetUser) }}" class="mt-5 space-y-3 border-t border-gms-border pt-4">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="update_role">
                            <label class="block text-xs text-gms-muted">
                                Platform administrator tier
                                <select name="platform_role" class="mt-1 w-full rounded-lg border border-gms-border bg-gms-surface px-3 py-2 text-sm text-white">
                                    <option value="none" @selected(! $targetUser->isPlatformAdmin())>Standard User (No Admin Access)</option>
                                    <option value="platform_admin" @selected($targetUser->platform_role === \App\Enums\PlatformRole::PlatformAdmin)>Platform Administrator</option>
                                    <option value="super_admin" @selected($targetUser->isSuperAdmin())>Platform Super Admin</option>
                                </select>
                            </label>
                            <button type="submit" class="w-full rounded-lg border border-gms-border bg-gms-surface px-4 py-2 text-xs font-semibold text-white hover:border-gms-cyan">Save Platform Role</button>
                        </form>
                    @endcan
                </x-dashboard.panel>
            @endif

            <x-dashboard.panel title="Recent user audit activity">
                @if ($auditLogs->isEmpty())
                    <x-dashboard.empty-state icon="clipboard" message="No audit events recorded for this user." />
                @else
                    <ul class="space-y-2.5 text-xs">
                        @foreach ($auditLogs as $log)
                            <li>
                                <p class="font-mono text-gms-cyan">{{ $log->event }}</p>
                                <p class="text-gms-muted">{{ $log->created_at?->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
