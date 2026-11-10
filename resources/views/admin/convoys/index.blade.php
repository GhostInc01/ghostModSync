<x-admin.layout title="Convoy Profile Management">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.convoys.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" type="search" value="{{ $search }}" placeholder="Search convoy profiles by name or slug"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted focus:border-gms-cyan focus:ring-gms-cyan">
            <select name="game" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
                <option value="">All games</option>
                <option value="ets2" @selected($gameCode === 'ets2')>ETS2</option>
                <option value="ats" @selected($gameCode === 'ats')>ATS</option>
            </select>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] hover:bg-white">Filter</button>
            @if ($search !== '' || $gameCode !== '')
                <a href="{{ route('admin.convoys.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gms-border px-4 text-sm font-semibold text-gms-text hover:text-gms-cyan">Reset</a>
            @endif
        </form>
    </section>

    <x-dashboard.panel title="Convoy profiles ({{ $profiles->total() }})">
        @if ($profiles->isEmpty())
            <x-dashboard.empty-state icon="convoy" message="No convoy profiles match the current filter." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">Profile</th>
                            <th class="px-4 py-3">Group</th>
                            <th class="px-4 py-3">Game</th>
                            <th class="px-4 py-3">Revision</th>
                            <th class="px-4 py-3">Mods</th>
                            <th class="px-4 py-3">Sync Sessions</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($profiles as $profile)
                            <tr>
                                <td class="py-3 pr-4">
                                    <p class="font-semibold text-white">{{ $profile->name }}</p>
                                    <p class="text-xs text-gms-muted font-mono">{{ $profile->slug }} @if ($profile->target_game_version) · v{{ $profile->target_game_version }} @endif</p>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $profile->group?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-dashboard.badge tone="cyan">{{ strtoupper($profile->game?->code?->value ?? 'N/A') }}</x-dashboard.badge>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-white">r{{ $profile->revision }} <span class="text-gms-muted">({{ $profile->revisions_count }} total)</span></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $profile->profile_mods_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $profile->sync_sessions_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($profile->trashed())
                                        <x-dashboard.badge tone="red">Archived</x-dashboard.badge>
                                    @elseif ($profile->is_active)
                                        <x-dashboard.badge tone="cyan">Published</x-dashboard.badge>
                                    @else
                                        <x-dashboard.badge tone="amber">Draft</x-dashboard.badge>
                                    @endif
                                </td>
                                <td class="py-3 pl-4 text-right">
                                    <a href="{{ route('admin.convoys.show', $profile->id) }}" class="text-xs font-semibold text-gms-cyan hover:text-white">Inspect</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($profiles->hasPages())
                <div class="mt-4 flex items-center justify-between text-xs text-gms-muted">
                    <span>Page {{ $profiles->currentPage() }} of {{ $profiles->lastPage() }}</span>
                    <div class="flex gap-3">
                        @if (! $profiles->onFirstPage()) <a href="{{ $profiles->previousPageUrl() }}" class="text-gms-cyan">Previous</a> @endif
                        @if ($profiles->hasMorePages()) <a href="{{ $profiles->nextPageUrl() }}" class="text-gms-cyan">Next</a> @endif
                    </div>
                </div>
            @endif
        @endif
    </x-dashboard.panel>
</x-admin.layout>
