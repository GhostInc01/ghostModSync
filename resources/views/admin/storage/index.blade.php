<x-admin.layout title="Storage Management">
    <section class="grid gap-4 sm:grid-cols-3">
        <x-dashboard.stat-card
            label="Total S3 Storage Used"
            :value="number_format($totalUsedBytes / 1073741824, 2).' GB'"
            hint="Sum of active & deprecated S3 mod archives"
            icon="mod"
            accent="cyan"
        />
        <x-dashboard.stat-card
            label="Total Allocated Group Quota"
            :value="number_format($totalQuotaBytes / 1073741824, 1).' GB'"
            :hint="$groups->count().' VTC groups provisioned'"
            icon="groups"
            accent="purple"
        />
        <x-dashboard.stat-card
            label="Duplicate SHA-256 Hashes"
            :value="$duplicateHashes->count()"
            hint="Identical archives uploaded more than once"
            icon="shield"
            accent="amber"
        />
    </section>

    <x-dashboard.panel title="Per-group S3 storage consumption vs quota">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                    <tr>
                        <th class="py-3 pr-4">Group</th>
                        <th class="px-4 py-3">S3 Versions</th>
                        <th class="px-4 py-3">Used</th>
                        <th class="px-4 py-3">Quota</th>
                        <th class="py-3 pl-4">Utilisation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gms-border">
                    @foreach ($groups as $g)
                        <tr>
                            <td class="py-3 pr-4 font-semibold text-white">
                                <a href="{{ route('admin.groups.show', $g->id) }}" class="hover:text-gms-cyan">{{ $g->name }}</a>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $g->s3_version_count }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gms-cyan">{{ number_format($g->used_storage_bytes / 1048576, 1) }} MB</td>
                            <td class="px-4 py-3 font-mono text-xs text-gms-muted">{{ number_format($g->storage_quota_bytes / 1073741824, 1) }} GB</td>
                            <td class="py-3 pl-4 font-mono text-xs">{{ $g->quota_usage_percent }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-dashboard.panel>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-dashboard.panel title="Largest S3 mod archives">
            @if ($largestVersions->isEmpty())
                <x-dashboard.empty-state icon="mod" message="No S3 archives uploaded yet." />
            @else
                <div class="divide-y divide-gms-border text-sm">
                    @foreach ($largestVersions as $v)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="font-semibold text-white">{{ $v->mod?->name ?? 'Mod' }} <span class="font-mono text-xs text-gms-cyan">v{{ $v->version_number }}</span></p>
                                <p class="text-xs text-gms-muted">{{ $v->mod?->group?->name ?? '—' }} · {{ $v->archive_filename }}</p>
                            </div>
                            <span class="font-mono text-xs text-white">{{ number_format(((int) $v->file_size_bytes) / 1048576, 1) }} MB</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.panel>

        <x-dashboard.panel title="Pending or revoked uploads ({{ $problematicUploads->count() }})">
            @if ($problematicUploads->isEmpty())
                <x-dashboard.empty-state icon="check" message="No stale pending or revoked uploads found." />
            @else
                <div class="divide-y divide-gms-border text-sm">
                    @foreach ($problematicUploads as $v)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="font-semibold text-white">{{ $v->mod?->name ?? 'Mod' }} <span class="font-mono text-xs text-gms-cyan">v{{ $v->version_number }}</span></p>
                                <p class="text-xs text-gms-muted">{{ $v->mod?->group?->name ?? '—' }} · {{ $v->created_at?->diffForHumans() }}</p>
                            </div>
                            <x-dashboard.badge :tone="$v->status->value === 'revoked' ? 'red' : 'amber'">{{ $v->status->value }}</x-dashboard.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.panel>
    </div>
</x-admin.layout>
