<x-admin.layout title="User Management">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" type="search" value="{{ $search }}" placeholder="Search by name, username, email or Steam ID"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted focus:border-gms-cyan focus:ring-gms-cyan">
            <select name="filter" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
                <option value="">All accounts</option>
                <option value="admins" @selected($filter === 'admins')>Platform admins</option>
                <option value="suspended" @selected($filter === 'suspended')>Suspended</option>
                <option value="unverified" @selected($filter === 'unverified')>Unverified email</option>
            </select>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] hover:bg-white">Filter</button>
            @if ($search !== '' || $filter !== '')
                <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gms-border px-4 text-sm font-semibold text-gms-text hover:text-gms-cyan">Reset</a>
            @endif
        </form>
    </section>

    <x-dashboard.panel title="Registered users ({{ $users->total() }})">
        @if ($users->isEmpty())
            <x-dashboard.empty-state icon="users" message="No user accounts match the current filter." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">User</th>
                            <th class="px-4 py-3">Platform Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Groups</th>
                            <th class="px-4 py-3">Devices</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($users as $u)
                            <tr>
                                <td class="py-3 pr-4">
                                    <p class="font-semibold text-white">{{ $u->name }}</p>
                                    <p class="text-xs text-gms-muted">{{ $u->email }} @if ($u->username) · <span class="font-mono">&#64;{{ $u->username }}</span> @endif</p>
                                </td>
                                <td class="px-4 py-3">
                                    <x-dashboard.badge :tone="$u->isPlatformAdmin() ? 'purple' : 'neutral'">{{ $u->platformRoleLabel() }}</x-dashboard.badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($u->isSuspended())
                                        <x-dashboard.badge tone="red">Suspended</x-dashboard.badge>
                                    @elseif ($u->email_verified_at)
                                        <x-dashboard.badge tone="cyan">Active</x-dashboard.badge>
                                    @else
                                        <x-dashboard.badge tone="amber">Unverified</x-dashboard.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $u->group_memberships_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $u->devices_count }}</td>
                                <td class="py-3 pl-4 text-right">
                                    <a href="{{ route('admin.users.show', $u) }}" class="text-xs font-semibold text-gms-cyan hover:text-white">Inspect</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="mt-4 flex items-center justify-between text-xs text-gms-muted">
                    <span>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
                    <div class="flex gap-3">
                        @if (! $users->onFirstPage()) <a href="{{ $users->previousPageUrl() }}" class="text-gms-cyan">Previous</a> @endif
                        @if ($users->hasMorePages()) <a href="{{ $users->nextPageUrl() }}" class="text-gms-cyan">Next</a> @endif
                    </div>
                </div>
            @endif
        @endif
    </x-dashboard.panel>
</x-admin.layout>
