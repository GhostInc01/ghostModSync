<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $group->tag }} {{ $group->name }} — {{ __('Group Invitations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('groups.partials.subnav')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('Create New Invitation Link') }}</h3>
                <form method="POST" action="{{ route('groups.invitations.store', $group) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="email" :value="__('Target Email (optional)')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" placeholder="driver@example.com" />
                    </div>
                    <div>
                        <x-input-label for="role" :value="__('Role Granted')" />
                        <select id="role" name="role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                            <option value="member">{{ __('Member') }}</option>
                            <option value="moderator">{{ __('Moderator') }}</option>
                            <option value="convoy_dispatcher">{{ __('Convoy Dispatcher') }}</option>
                            <option value="mod_manager">{{ __('Mod Manager') }}</option>
                            <option value="admin">{{ __('Administrator') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="max_uses" :value="__('Max Uses')" />
                        <x-text-input id="max_uses" name="max_uses" type="number" min="1" max="500" value="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-primary-button>{{ __('Generate Invite') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('Issued Invitations') }}</h3>
                @if ($invitations->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No invitations issued yet.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-mono">{{ route('groups.invitations.accept', $invitation->token) }}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ __('Role:') }} {{ $invitation->role->label() }} &bull;
                                        {{ __('Uses:') }} {{ $invitation->uses_count }}/{{ $invitation->max_uses ?? '∞' }} &bull;
                                        {{ __('Expires:') }} {{ $invitation->expires_at->toDateString() }}
                                    </div>
                                </div>
                                @if ($invitation->isValid())
                                    <form method="POST" action="{{ route('groups.invitations.destroy', [$group, $invitation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>{{ __('Revoke') }}</x-danger-button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('Inactive / Expired') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
