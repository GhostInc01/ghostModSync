<x-admin.layout title="Group Management">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.groups.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" type="search" value="{{ $search }}" placeholder="Search groups by name, slug or tag"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted focus:border-gms-cyan focus:ring-gms-cyan">
            <select name="filter" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text">
                <option value="">All groups</option>
                <option value="public" @selected($filter === 'public')>Public</option>
                <option value="private" @selected($filter === 'private')>Private</option>
                <option value="archived" @selected($filter === 'archived')>Archived (Suspended)</option>
            </select>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] hover:bg-white">Filter</button>
        </form>
    </section>

    <x-dashboard.panel title="VTC Groups ({{ $groups->total() }})">
        @if ($groups->isEmpty())
            <x-dashboard.empty-state icon="shield" message="No groups match the current filter." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">Group</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Visibility / Status</th>
                            <th class="px-4 py-3">Members</th>
                            <th class="px-4 py-3">Mods / Convoys</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($groups as $g)
                            <tr>
                                <td class="py-3 pr-4">
                                    <p class="font-semibold text-white"><span class="font-mono text-xs text-gms-cyan">{{ $g->tag }}</span> {{ $g->name }}</p>
                                    <p class="font-mono text-xs text-gms-muted">{{ $g->slug }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gms-muted">{{ $g->owner?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-dashboard.badge>{{ $g->visibility->label() }}</x-dashboard.badge>
                                    @if ($g->trashed())
                                        <x-dashboard.badge tone="red">Archived</x-dashboard.badge>
                                    @else
                                        <x-dashboard.badge tone="cyan">Active</x-dashboard.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $g->memberships_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $g->mods_count }} / {{ $g->convoy_profiles_count }}</td>
                                <td class="py-3 pl-4 text-right">
                                    <a href="{{ route('admin.groups.show', $g->id) }}" class="text-xs font-semibold text-gms-cyan hover:text-white">Inspect</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-dashboard.panel>

    @if ($reports->isNotEmpty())
        <x-dashboard.panel title="Reported groups">
            <ul class="divide-y divide-gms-border text-sm">
                @foreach ($reports as $report)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="font-semibold text-white">Group #{{ $report->reportable_id }} — {{ $report->reason }}</p>
                            <p class="text-xs text-gms-muted">Reported by {{ $report->reporter?->name ?? 'User' }} · {{ $report->status->label() }}</p>
                        </div>
                        <a href="{{ route('admin.groups.show', $report->reportable_id) }}" class="text-xs font-semibold text-gms-cyan">Investigate</a>
                    </li>
                @endforeach
            </ul>
        </x-dashboard.panel>
    @endif
</x-admin.layout>
