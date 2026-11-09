<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $group->tag }} {{ $group->name }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $group->visibility->label() }} &bull; {{ $group->sync_policy->label() }}
                    @if ($activeRole)
                        &bull; {{ __('Your Role:') }} <span class="font-semibold">{{ $activeRole->label() }}</span>
                    @endif
                </p>
            </div>

            @if (! $activeRole && $group->isPublic())
                <form method="POST" action="{{ route('groups.join', $group) }}">
                    @csrf
                    <x-primary-button>{{ __('Join Group') }}</x-primary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('groups.partials.subnav')

            @if (session('invitation_token'))
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 rounded-lg text-sm text-indigo-900 dark:text-indigo-200">
                    <strong>{{ __('Invite Link Created:') }}</strong>
                    <code class="ms-2 select-all">{{ route('groups.invitations.accept', session('invitation_token')) }}</code>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-2">{{ __('About This VTC / Group') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $group->description ?? __('No description provided.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Convoy Profiles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Convoy Mod Profiles') }}</h3>

                    @if ($convoyProfiles->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No convoy profiles published yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($convoyProfiles as $profile)
                                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-semibold">{{ $profile->name }}</span>
                                            <span class="ms-2 text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                                {{ strtoupper($profile->game->code->value) }} {{ $profile->target_game_version }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500">Rev #{{ $profile->revision }}</span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500 font-mono truncate">
                                        SHA-256: {{ $profile->manifest_hash }}
                                    </div>
                                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                        {{ $profile->profileMods->count() }} {{ __('pinned mods') }} &bull; {{ $profile->effectiveSyncPolicy()->label() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Mod Library -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Shared Mod Library') }}</h3>

                    @if ($mods->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No mods registered in this group yet.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($mods as $mod)
                                <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">{{ $mod->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ strtoupper($mod->game->code->value) }} &bull; {{ $mod->source_type->label() }} &bull; {{ $mod->versions->count() }} {{ __('versions') }}
                                        </div>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $mod->category }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('Group Members & Roles') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 px-3">{{ __('Member') }}</th>
                                <th class="py-2 px-3">{{ __('Role') }}</th>
                                <th class="py-2 px-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($memberships as $membership)
                                <tr>
                                    <td class="py-2 px-3">{{ $membership->user->name }}</td>
                                    <td class="py-2 px-3">{{ $membership->role->label() }}</td>
                                    <td class="py-2 px-3">{{ $membership->status->label() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
