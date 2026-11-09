<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('VTCs & Mod Groups') }}
            </h2>
            <a href="{{ route('groups.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                {{ __('Create New Group') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('My Group Memberships') }}</h3>

                    @if ($myMemberships->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('You are not a member of any groups yet. Browse public VTCs below or create your own group.') }}
                        </p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($myMemberships as $membership)
                                <a href="{{ route('groups.show', $membership->group) }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $membership->group->tag }} {{ $membership->group->name }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                            {{ $membership->role->label() }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                        {{ $membership->group->description ?? __('No description provided.') }}
                                    </p>
                                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                                        <span>{{ $membership->group->visibility->label() }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $membership->group->sync_policy->label() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Public VTC Directory') }}</h3>

                    @if ($publicGroups->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No public groups found.') }}
                        </p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($publicGroups as $group)
                                <a href="{{ route('groups.show', $group) }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $group->tag }} {{ $group->name }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $group->memberships_count }} {{ __('members') }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                        {{ $group->description ?? __('No description provided.') }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
