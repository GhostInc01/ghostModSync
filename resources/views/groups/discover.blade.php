<x-dashboard.layout title="Discover Groups">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('groups.discover') }}" role="search" class="flex flex-col gap-3 sm:flex-row">
            <label for="q" class="sr-only">Search public groups</label>
            <input id="q" name="q" type="search" value="{{ $search }}" maxlength="100" placeholder="Search by name, tag or description"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted focus:border-gms-cyan focus:ring-gms-cyan">
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] shadow-[0_0_20px_rgba(0,240,255,0.25)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Search</button>
            @if ($search !== '')
                <a href="{{ route('groups.discover') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gms-border px-4 text-sm font-semibold text-gms-text hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Clear</a>
            @endif
        </form>
        <p class="mt-3 text-xs text-gms-muted">Only public groups are listed. Private groups are joined by invitation.</p>
    </section>

    @if ($groups->isEmpty())
        <x-dashboard.empty-state icon="users"
            :message="$search !== '' ? 'No public groups match your search.' : 'There are no public groups yet.'"
            :href="route('groups.create')" cta="Create a group" />
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($groups as $group)
                @php
                    $mine = $myMemberships->get($group->id);
                    $statusTone = match ($mine?->status) {
                        \App\Enums\MembershipStatus::Active => 'cyan',
                        \App\Enums\MembershipStatus::Suspended, \App\Enums\MembershipStatus::Banned => 'red',
                        default => 'amber',
                    };
                @endphp
                <a href="{{ route('groups.show', $group) }}" class="block rounded-xl border border-gms-border bg-gms-card/60 p-5 transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            @if ($group->logo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($group->logo_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg border border-gms-border object-cover" loading="lazy">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gms-cyan/10 font-display text-lg font-bold text-gms-cyan" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($group->tag, '[] ') ?: $group->name, 0, 2)) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white">{{ $group->name }}</p>
                                <p class="font-mono text-xs text-gms-cyan">{{ $group->tag }}</p>
                            </div>
                        </div>
                        @if ($mine)
                            <x-dashboard.badge :tone="$statusTone">{{ $mine->status->label() }}</x-dashboard.badge>
                        @endif
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm text-gms-muted">{{ $group->description ?: 'No description provided.' }}</p>
                    <p class="mt-3 text-xs text-gms-muted">
                        {{ $group->active_members_count }} {{ \Illuminate\Support\Str::plural('member', $group->active_members_count) }}
                        · {{ $group->require_join_approval ? 'Approval required to join' : 'Open to join' }}
                    </p>
                </a>
            @endforeach
        </div>

        @if ($groups->hasPages())
            <nav class="flex items-center justify-between gap-3" aria-label="Pagination">
                @if ($groups->onFirstPage())
                    <span class="text-sm text-gms-muted/50">Previous</span>
                @else
                    <a href="{{ $groups->previousPageUrl() }}" rel="prev" class="text-sm font-semibold text-gms-cyan hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Previous</a>
                @endif
                <span class="text-xs text-gms-muted">Page {{ $groups->currentPage() }} of {{ $groups->lastPage() }}</span>
                @if ($groups->hasMorePages())
                    <a href="{{ $groups->nextPageUrl() }}" rel="next" class="text-sm font-semibold text-gms-cyan hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan">Next</a>
                @else
                    <span class="text-sm text-gms-muted/50">Next</span>
                @endif
            </nav>
        @endif
    @endif
</x-dashboard.layout>
