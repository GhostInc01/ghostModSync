<x-admin.layout title="System Settings">
    <div class="grid gap-6 lg:grid-cols-2">
        <x-dashboard.panel title="Supported Simulator Games (ETS2 & ATS)">
            <div class="space-y-4">
                @foreach ($games as $game)
                    <div class="rounded-lg border border-gms-border bg-gms-surface/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-display text-sm font-bold text-white">{{ $game->name }} ({{ strtoupper($game->code->value) }})</p>
                                <p class="font-mono text-xs text-gms-muted">Steam App ID: {{ $game->steam_app_id }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-dashboard.badge :tone="$game->is_active ? 'cyan' : 'red'">
                                    {{ $game->is_active ? 'Active' : 'Disabled' }}
                                </x-dashboard.badge>
                                @can('manage-platform-settings')
                                    <form method="POST" action="{{ route('admin.settings.games.update', $game) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $game->is_active ? '0' : '1' }}">
                                        <button type="submit" class="rounded border border-gms-border px-2.5 py-1 text-xs font-semibold text-gms-text hover:border-gms-cyan hover:text-gms-cyan">
                                            {{ $game->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($game->versions as $ver)
                                <x-dashboard.badge :tone="$ver->is_current ? 'cyan' : 'neutral'">
                                    v{{ $ver->version_branch }}{{ $ver->is_current ? ' (current)' : '' }}
                                </x-dashboard.badge>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-dashboard.panel>

        <div class="space-y-6">
            @can('manage-platform-settings')
                <x-dashboard.panel title="Add Supported Game Version Branch (Super Admin)">
                    <form method="POST" action="{{ route('admin.settings.game-versions.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gms-muted">Simulator Game</label>
                            <select name="game_id" class="mt-1 min-h-[44px] w-full rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text">
                                @foreach ($games as $game)
                                    <option value="{{ $game->id }}">{{ $game->name }} ({{ strtoupper($game->code->value) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gms-muted">Version Branch Code (e.g. 1.54.*)</label>
                            <input type="text" name="version_branch" required placeholder="1.54.*"
                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-gms-border bg-gms-surface px-3 text-sm text-gms-text">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gms-text">
                            <input type="checkbox" name="is_current" value="1" class="rounded border-gms-border bg-gms-surface text-gms-cyan">
                            Mark as current stable branch for this game
                        </label>
                        <button type="submit" class="inline-flex min-h-[40px] items-center justify-center rounded-lg bg-gms-cyan px-4 text-xs font-bold text-[#080B12] hover:bg-white">
                            Save Game Version Branch
                        </button>
                    </form>
                </x-dashboard.panel>
            @else
                <x-dashboard.panel title="Super Admin Settings">
                    <p class="text-xs text-gms-muted">Modifying platform-wide game versions and system flags requires the <strong class="text-white">Platform Super Admin</strong> role.</p>
                </x-dashboard.panel>
            @endcan

            <x-dashboard.panel title="Sanitised Runtime & Upload Policy Summary">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between border-b border-gms-border pb-2">
                        <dt class="text-gms-muted">PHP / Laravel Runtime</dt>
                        <dd class="font-mono text-xs text-white">PHP {{ $systemMetadata['php_version'] }} · Laravel {{ $systemMetadata['laravel_version'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-gms-border pb-2">
                        <dt class="text-gms-muted">Environment</dt>
                        <dd class="font-mono text-xs text-gms-cyan">{{ $systemMetadata['environment'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-gms-border pb-2">
                        <dt class="text-gms-muted">Allowed Binary Archives</dt>
                        <dd class="font-mono text-xs text-white">{{ implode(', ', $systemMetadata['allowed_mod_extensions']) }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-gms-border pb-2">
                        <dt class="text-gms-muted">Integrity Verification</dt>
                        <dd class="font-mono text-xs text-gms-cyan">{{ $systemMetadata['checksum_algorithm'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gms-muted">S3 Bucket / Region</dt>
                        <dd class="font-mono text-xs text-gms-muted">{{ $systemMetadata['s3_bucket'] ?: 'not-configured' }} ({{ $systemMetadata['s3_region'] ?: 'n/a' }})</dd>
                    </div>
                </dl>
            </x-dashboard.panel>
        </div>
    </div>
</x-admin.layout>
