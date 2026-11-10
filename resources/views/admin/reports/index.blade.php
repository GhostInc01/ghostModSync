<x-admin.layout title="Platform Reports">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-display text-base font-bold text-white">Export Platform CSV Reports</h2>
                <p class="mt-1 text-xs text-gms-muted">Download sanitised CSV snapshots for compliance, auditing, and capacity planning.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.export', 'users') }}" class="inline-flex min-h-[40px] items-center rounded-lg border border-gms-border bg-gms-surface px-3.5 text-xs font-semibold text-gms-text hover:border-gms-cyan hover:text-gms-cyan">Export Users CSV</a>
                <a href="{{ route('admin.reports.export', 'groups') }}" class="inline-flex min-h-[40px] items-center rounded-lg border border-gms-border bg-gms-surface px-3.5 text-xs font-semibold text-gms-text hover:border-gms-cyan hover:text-gms-cyan">Export Groups CSV</a>
                <a href="{{ route('admin.reports.export', 'mods') }}" class="inline-flex min-h-[40px] items-center rounded-lg border border-gms-border bg-gms-surface px-3.5 text-xs font-semibold text-gms-text hover:border-gms-cyan hover:text-gms-cyan">Export Mods CSV</a>
                <a href="{{ route('admin.reports.export', 'sync') }}" class="inline-flex min-h-[40px] items-center rounded-lg border border-gms-border bg-gms-surface px-3.5 text-xs font-semibold text-gms-text hover:border-gms-cyan hover:text-gms-cyan">Export Sync CSV</a>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-dashboard.panel title="Account & registration summary">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Total Registered Accounts</dt>
                    <dd class="font-mono font-bold text-white">{{ number_format($userSummary['total']) }}</dd>
                </div>
                <div class="flex justify-between border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Verified Email Accounts</dt>
                    <dd class="font-mono text-gms-cyan">{{ number_format($userSummary['verified']) }}</dd>
                </div>
                <div class="flex justify-between border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Unverified Email Accounts</dt>
                    <dd class="font-mono text-amber-300">{{ number_format($userSummary['unverified']) }}</dd>
                </div>
                <div class="flex justify-between border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Suspended Accounts</dt>
                    <dd class="font-mono text-red-300">{{ number_format($userSummary['suspended']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gms-muted">Registered in Last 30 Days</dt>
                    <dd class="font-mono text-white">{{ number_format($userSummary['registered_last_30d']) }}</dd>
                </div>
            </dl>
        </x-dashboard.panel>

        <x-dashboard.panel title="Mod distribution by source">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gms-border pb-2">
                    <dt class="text-gms-muted">Direct S3 Uploads (`.scs` / `.zip`)</dt>
                    <dd class="font-mono font-bold text-gms-cyan">{{ number_format($modsBySource['s3_upload']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gms-muted">Steam Workshop References</dt>
                    <dd class="font-mono font-bold text-purple-300">{{ number_format($modsBySource['steam_workshop']) }}</dd>
                </div>
            </dl>
        </x-dashboard.panel>
    </div>

    <x-dashboard.panel title="Top VTC groups by active membership">
        @if ($topGroups->isEmpty())
            <x-dashboard.empty-state icon="groups" message="No groups available." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">Group</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Active Members</th>
                            <th class="px-4 py-3">Mods</th>
                            <th class="py-3 pl-4">Convoy Profiles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($topGroups as $g)
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-white">{{ $g->name }}</td>
                                <td class="px-4 py-3 text-xs text-gms-muted">{{ $g->owner?->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gms-cyan">{{ $g->active_members_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $g->mods_count }}</td>
                                <td class="py-3 pl-4 font-mono text-xs">{{ $g->convoy_profiles_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-dashboard.panel>
</x-admin.layout>
