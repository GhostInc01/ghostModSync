<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create VTC / Mod Group') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('groups.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Group / VTC Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="slug" :value="__('URL Slug (optional)')" />
                            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug')" placeholder="e.g. sfg-heavy-haul" />
                            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                        </div>
                        <div>
                            <x-input-label for="tag" :value="__('In-Game VTC Tag (optional)')" />
                            <x-text-input id="tag" name="tag" type="text" class="mt-1 block w-full" :value="old('tag')" placeholder="e.g. [SFG]" />
                            <x-input-error class="mt-2" :messages="$errors->get('tag')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="visibility" :value="__('Group Visibility')" />
                            <select id="visibility" name="visibility" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                <option value="private" @selected(old('visibility') === 'private')>{{ __('Private (Invite Only)') }}</option>
                                <option value="public" @selected(old('visibility') === 'public')>{{ __('Public (Listed in Directory)') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('visibility')" />
                        </div>

                        <div>
                            <x-input-label for="sync_policy" :value="__('Default Mod Synchronisation Policy')" />
                            <select id="sync_policy" name="sync_policy" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                <option value="automatic" @selected(old('sync_policy') === 'automatic')>{{ __('Automatic Synchronisation') }}</option>
                                <option value="approval_required" @selected(old('sync_policy') === 'approval_required')>{{ __('Approval-Based Synchronisation') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('sync_policy')" />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="require_join_approval" name="require_join_approval" type="checkbox" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <label for="require_join_approval" class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Require administrator approval when users request to join') }}
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Create Group') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
