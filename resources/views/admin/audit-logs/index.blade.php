<x-admin.layout title="Audit Logs">
    <section class="rounded-xl border border-gms-border bg-gms-card/60 p-5">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" type="search" value="{{ $search }}" placeholder="Search by event name, actor name, or actor email"
                   class="min-h-[44px] flex-1 rounded-lg border border-gms-border bg-gms-surface px-4 text-sm text-gms-text placeholder:text-gms-muted focus:border-gms-cyan focus:ring-gms-cyan">
            <select name="category" class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
                <option value="">All categories</option>
                <option value="admin" @selected($category === 'admin')>admin.*</option>
                <option value="group" @selected($category === 'group')>group.*</option>
                <option value="membership" @selected($category === 'membership')>membership.*</option>
                <option value="mod" @selected($category === 'mod')>mod.*</option>
                <option value="profile" @selected($category === 'profile')>profile.*</option>
                <option value="sync" @selected($category === 'sync')>sync.*</option>
                <option value="device" @selected($category === 'device')>device.*</option>
            </select>
            <input name="from" type="date" value="{{ $fromDate ?? '' }}" aria-label="From date"
                   class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
            <input name="to" type="date" value="{{ $toDate ?? '' }}" aria-label="To date"
                   class="min-h-[44px] rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text focus:border-gms-cyan focus:ring-gms-cyan">
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-gms-cyan px-5 text-sm font-bold text-[#080B12] hover:bg-white">Filter</button>
            @if ($search !== '' || $category !== '' || ($fromDate ?? '') !== '' || ($toDate ?? '') !== '')
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gms-border px-4 text-sm font-semibold text-gms-text hover:text-gms-cyan">Reset</a>
            @endif
        </form>
    </section>

    <x-dashboard.panel title="Immutable audit trail ({{ $logs->total() }})">
        @if ($logs->isEmpty())
            <x-dashboard.empty-state icon="clock" message="No audit events match the current filter." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gms-border text-xs uppercase text-gms-muted">
                        <tr>
                            <th class="py-3 pr-4">Timestamp</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Actor</th>
                            <th class="px-4 py-3">Target</th>
                            <th class="py-3 pl-4">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gms-border">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-xs text-gms-muted">{{ $log->created_at?->toDateTimeString() }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gms-cyan">{{ $log->event }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="text-white">{{ $log->user?->name ?? 'System' }}</span>
                                    @if ($log->device)
                                        <span class="block text-gms-muted">{{ $log->device->device_name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gms-muted">
                                    {{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id ?? '—' }}
                                </td>
                                <td class="py-3 pl-4 font-mono text-xs text-gms-muted">
                                    @if (! empty($log->new_values))
                                        {{ \Illuminate\Support\Str::limit(json_encode($log->new_values), 90) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="mt-4 flex items-center justify-between text-xs text-gms-muted">
                    <span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
                    <div class="flex gap-3">
                        @if (! $logs->onFirstPage()) <a href="{{ $logs->previousPageUrl() }}" class="text-gms-cyan">Previous</a> @endif
                        @if ($logs->hasMorePages()) <a href="{{ $logs->nextPageUrl() }}" class="text-gms-cyan">Next</a> @endif
                    </div>
                </div>
            @endif
        @endif
    </x-dashboard.panel>
</x-admin.layout>
