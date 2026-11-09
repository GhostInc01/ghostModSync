<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $group->tag }} {{ $group->name }} — {{ __('Group Members') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('groups.partials.subnav')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('Group Members & Roles') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 px-3">{{ __('Member') }}</th>
                                <th class="py-2 px-3">{{ __('Role') }}</th>
                                <th class="py-2 px-3">{{ __('Status') }}</th>
                                <th class="py-2 px-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($memberships as $membership)
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="font-medium">{{ $membership->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $membership->user->email }}</div>
                                    </td>
                                    <td class="py-3 px-3">{{ $membership->role->label() }}</td>
                                    <td class="py-3 px-3">{{ $membership->status->label() }}</td>
                                    <td class="py-3 px-3 text-right space-x-2">
                                        @if ($canManageMembers && $membership->user_id !== $group->owner_id && ($activeRole?->rank() ?? 0) > $membership->role->rank())
                                            <form method="POST" action="{{ route('groups.memberships.update', [$group, $membership]) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                @if ($canAssignRoles)
                                                    <select name="role" class="text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded">
                                                        @foreach (\App\Enums\GroupRole::cases() as $roleOption)
                                                            @if ($roleOption !== \App\Enums\GroupRole::Owner && ($activeRole === \App\Enums\GroupRole::Owner || $roleOption->rank() < $activeRole->rank()))
                                                                <option value="{{ $roleOption->value }}" @selected($membership->role === $roleOption)>
                                                                    {{ $roleOption->label() }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                @endif
                                                <select name="status" class="text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded">
                                                    <option value="active" @selected($membership->status->value === 'active')>{{ __('Active') }}</option>
                                                    <option value="suspended" @selected($membership->status->value === 'suspended')>{{ __('Suspended') }}</option>
                                                </select>
                                                <x-secondary-button type="submit">{{ __('Save') }}</x-secondary-button>
                                            </form>

                                            <form method="POST" action="{{ route('groups.memberships.destroy', [$group, $membership]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>{{ __('Remove') }}</x-danger-button>
                                            </form>
                                        @elseif ($membership->user_id === auth()->id() && $membership->user_id !== $group->owner_id)
                                            <form method="POST" action="{{ route('groups.memberships.destroy', [$group, $membership]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>{{ __('Leave Group') }}</x-danger-button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
