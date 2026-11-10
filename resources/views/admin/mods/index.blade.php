<x-admin.layout title="Mod Management">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.mods.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" type="search" value="{{ $search }}" placeholder="Search mods by name, slug or author"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted">
            <select name="filter" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text">
                <option value="">All sources</option>
                <option value="s3" @selected($filter === 's3')>S3 Direct Uploads</option>
                <option value="steam" @selected($filter === 'steam')>Steam Workshop</option>
            </select>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12]">Filter</button>
        </form>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-dashboard.panel title="Uploaded mods ({{ $mods->total() }})">
                @if ($mods->isEmpty())
                    <x-dashboard.empty-state icon="cube" message="No mods match the current filter." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                                <tr>
                                    <th class="py-3 pr-4">Mod</th>
                                    <th class="px-4 py-3">Group / Game</th>
                                    <th class="px-4 py-3">Source</th>
                                    <th class="px-4 py-3">Versions</th>
                                    <th class="py-3 pl-4 text-right">Inspect</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gms-border">
                                @foreach ($mods as $m)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <p class="font-semibold text-white">{{ $m->name }}</p>
                                            <p class="text-xs text-gms-muted">{{ $m->author_name ?? 'Unknown author' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gms-muted">{{ $m->group?->name }} · {{ $m->game?->code?->label() }}</td>
                                        <td class="px-4 py-3"><x-dashboard.badge tone="purple">{{ $m->source_type->value }}</x-dashboard.badge></td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $m->versions_count }}</td>
                                        <td class="py-3 pl-4 text-right">
                                            <a href="{{ route('admin.mods.show', $m->id) }}" class="text-xs font-semibold text-gms-cyan">Inspect</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-dashboard.panel>

            @if ($pendingApprovals->isNotEmpty())
                <x-dashboard.panel title="Pending group mod approvals">
                    <ul class="divide-y divide-gms-border text-sm">
                        @foreach ($pendingApprovals as $approval)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                                <div>
                                    <p class="font-semibold text-white">{{ $approval->modVersion?->mod?->name }} ({{ $approval->modVersion?->version_number }})</p>
                                    <p class="text-xs text-gms-muted">{{ $approval->group?->name }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.mod-approvals.review', $approval) }}" class="flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <button name="status" value="approved" class="rounded bg-gms-cyan px-3 py-1 text-xs font-bold text-[#080B12]">Approve</button>
                                    <button name="status" value="rejected" class="rounded border border-red-400/40 px-3 py-1 text-xs font-semibold text-red-300">Reject</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </x-dashboard.panel>
            @endif
        </div>

        <div class="space-y-6">
            <x-dashboard.panel title="Duplicate SHA-256 uploads">
                @if ($duplicateHashes->isEmpty())
                    <x-dashboard.empty-state icon="check" message="No duplicate SHA-256 archives detected." />
                @else
                    <ul class="space-y-2 text-xs">
                        @foreach ($duplicateHashes as $dup)
                            <li class="rounded border border-gms-border bg-gms-surface p-2.5">
                                <p class="truncate font-mono text-gms-amber">{{ $dup->sha256_hash }}</p>
                                <p class="text-gms-muted">{{ $dup->occurrences }} versions share this checksum</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>

            <x-dashboard.panel title="Open moderation reports">
                @if ($reports->isEmpty())
                    <x-dashboard.empty-state icon="shield" message="No open moderation reports." />
                @else
                    <ul class="space-y-3 text-xs">
                        @foreach ($reports as $report)
                            <li class="rounded border border-gms-border bg-gms-surface p-3">
                                <p class="font-semibold text-white">{{ $report->reason }}</p>
                                <p class="mt-0.5 text-gms-muted">{{ $report->details }}</p>
                                <form method="POST" action="{{ route('admin.moderation-reports.review', $report) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <button name="status" value="resolved" class="rounded bg-gms-cyan px-2.5 py-1 font-bold text-[#080B12]">Resolve</button>
                                    <button name="status" value="dismissed" class="rounded border border-gms-border px-2.5 py-1 text-gms-muted">Dismiss</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
