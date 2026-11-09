<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $group->tag }} {{ $group->name }} — {{ __('Group Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('groups.partials.subnav')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('General & Branding Settings') }}</h3>
                <form method="POST" action="{{ route('groups.update', $group) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" :value="__('Group Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $group->name)" required />
                        </div>
                        <div>
                            <x-input-label for="tag" :value="__('VTC Tag')" />
                            <x-text-input id="tag" name="tag" type="text" class="mt-1 block w-full" :value="old('tag', $group->tag)" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">{{ old('description', $group->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="visibility" :value="__('Visibility')" />
                            <select id="visibility" name="visibility" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                <option value="public" @selected($group->visibility->value === 'public')>{{ __('Public') }}</option>
                                <option value="private" @selected($group->visibility->value === 'private')>{{ __('Private') }}</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="sync_policy" :value="__('Synchronisation Policy')" />
                            <select id="sync_policy" name="sync_policy" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                <option value="automatic" @selected($group->sync_policy->value === 'automatic')>{{ __('Automatic') }}</option>
                                <option value="approval_required" @selected($group->sync_policy->value === 'approval_required')>{{ __('Approval Required') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save Settings') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if ($canTransferOwnership)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-2">{{ __('Transfer Group Ownership') }}</h3>
                    <form method="POST" action="{{ route('groups.transfer-ownership', $group) }}" class="flex items-center gap-4">
                        @csrf
                        <select name="new_owner_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach ($activeMembers as $member)
                                <option value="{{ $member->user_id }}">{{ $member->user->name }} ({{ $member->role->label() }})</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit">{{ __('Transfer Ownership') }}</x-secondary-button>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-red-600 mb-2">{{ __('Archive / Delete Group') }}</h3>
                    <form method="POST" action="{{ route('groups.destroy', $group) }}">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Archive Group') }}</x-danger-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
