<x-admin.layout :title="'Mod · '.$mod->name">
    <x-dashboard.panel title="Mod details & compatibility">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs text-gms-muted">Group / Game</dt>
                <dd class="font-semibold text-white">{{ $mod->group?->name }} · {{ $mod->game?->name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gms-muted">Source Type / Category</dt>
                <dd class="text-white">{{ $mod->source_type->value }} · {{ $mod->category }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gms-muted">Author / Creator</dt>
                <dd class="text-white">{{ $mod->author_name ?? '—' }} ({{ $mod->creator?->name ?? 'System' }})</dd>
            </div>
        </dl>
    </x-dashboard.panel>

    <x-dashboard.panel title="Mod versions ({{ $mod->versions->count() }})">
        @if ($mod->versions->isEmpty())
            <x-dashboard.empty-state icon="cube" message="No versions uploaded for this mod." />
        @else
            <ul class="divide-y divide-gms-border text-sm">
                @foreach ($mod->versions as $v)
                    <li class="space-y-2 py-4 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-mono font-bold text-gms-cyan">v{{ $v->version_number }}</span>
                                <span class="ml-2 text-xs text-gms-muted">{{ $v->archive_filename }}</span>
                                <span class="ml-2 text-xs text-gms-muted">Game: {{ $v->min_game_version ?? '*' }} – {{ $v->max_game_version ?? '*' }}</span>
                            </div>
                            <x-dashboard.badge :tone="$v->status->value === 'ready' ? 'cyan' : ($v->status->value === 'revoked' ? 'red' : 'amber')">{{ $v->status->value }}</x-dashboard.badge>
                        </div>
                        @if ($v->sha256_hash)
                            <p class="font-mono text-xs text-gms-muted">SHA-256: {{ $v->sha256_hash }} ({{ number_format((int) $v->file_size_bytes) }} bytes)</p>
                        @endif
                        @if ($v->requiredDlcs->isNotEmpty())
                            <p class="text-xs text-gms-muted">Required DLCs: {{ $v->requiredDlcs->pluck('name')->join(', ') }}</p>
                        @endif
                        <form method="POST" action="{{ route('admin.mod-versions.status.update', $v) }}" class="flex flex-wrap items-center gap-2 pt-1">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="rounded border border-gms-border bg-gms-surface px-2.5 py-1 text-xs text-white">
                                <option value="ready" @selected($v->status->value === 'ready')>Ready</option>
                                <option value="deprecated" @selected($v->status->value === 'deprecated')>Deprecated</option>
                                <option value="revoked" @selected($v->status->value === 'revoked')>Revoked</option>
                            </select>
                            <input name="reason" type="text" placeholder="Reason for status change" class="rounded border border-gms-border bg-gms-surface px-2.5 py-1 text-xs text-white">
                            <button type="submit" class="rounded bg-gms-cyan px-3 py-1 text-xs font-bold text-[#080B12]">Update Status</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-dashboard.panel>
</x-admin.layout>
