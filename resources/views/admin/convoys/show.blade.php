<x-admin.layout :title="'Convoy Profile · '.$profile->name">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.convoys.index') }}" class="text-xs font-semibold text-gms-cyan hover:text-white">← Back to convoy profiles</a>
        @if ($profile->is_active)
            <x-dashboard.badge tone="cyan">Published (r{{ $profile->revision }})</x-dashboard.badge>
        @else
            <x-dashboard.badge tone="amber">Draft</x-dashboard.badge>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-dashboard.panel title="Profile metadata">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Group</dt>
                    <dd class="text-white">{{ $profile->group?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Game</dt>
                    <dd class="font-mono text-gms-cyan">{{ strtoupper($profile->game?->code?->value ?? 'N/A') }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Target Game Version</dt>
                    <dd class="font-mono text-xs text-white">{{ $profile->target_game_version ?? 'Any' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Created By</dt>
                    <dd class="text-white">{{ $profile->creator?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gms-muted">Published At</dt>
                    <dd class="font-mono text-xs text-gms-muted">{{ $profile->published_at?->toDateTimeString() ?? 'Unpublished' }}</dd>
                </div>
            </dl>
        </x-dashboard.panel>

        <div class="space-y-6 lg:col-span-2">
            <x-dashboard.panel title="Ordered mod load stack ({{ $profile->profileMods->count() }})">
                @if ($profile->profileMods->isEmpty())
                    <x-dashboard.empty-state icon="mod" message="No mods attached to this convoy profile." />
                @else
                    <div class="divide-y divide-gms-border text-sm">
                        @foreach ($profile->profileMods as $item)
                            <div class="flex flex-wrap items-center justify-between gap-2 py-2.5">
                                <div>
                                    <span class="font-mono text-xs text-gms-cyan">#{{ $item->load_order }}</span>
                                    <span class="ml-2 font-semibold text-white">{{ $item->modVersion?->mod?->name ?? 'Mod' }}</span>
                                    <span class="ml-1 font-mono text-xs text-gms-muted">v{{ $item->modVersion?->version_number ?? '?' }}</span>
                                </div>
                                <x-dashboard.badge :tone="$item->is_required ? 'cyan' : 'neutral'">
                                    {{ $item->is_required ? 'Required' : 'Optional' }}
                                </x-dashboard.badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.panel>

            <x-dashboard.panel title="Immutable revision history ({{ $profile->revisions->count() }})">
                @if ($profile->revisions->isEmpty())
                    <x-dashboard.empty-state icon="clock" message="No published revisions recorded yet." />
                @else
                    <div class="divide-y divide-gms-border text-sm">
                        @foreach ($profile->revisions as $rev)
                            <div class="py-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-gms-cyan">Revision #{{ $rev->revision_number }}</span>
                                    <span class="font-mono text-xs text-gms-muted">{{ $rev->published_at?->toDateTimeString() }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gms-muted">
                                    Published by {{ $rev->publisher?->name ?? 'System' }}
                                    @if ($rev->changelog) · {{ $rev->changelog }} @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
