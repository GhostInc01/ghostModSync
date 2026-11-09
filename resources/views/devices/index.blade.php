<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Connected Desktop Devices') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'device-revoked')
                <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300">
                    {{ __('Desktop device access token has been revoked.') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('Windows PCs running the Ghost ModSync desktop application appear here once authenticated. Revoking a device immediately invalidates its Sanctum API token.') }}
                    </p>

                    @if ($devices->isEmpty())
                        <div class="text-center py-8 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No Windows desktop devices have been connected to your account yet.') }}
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400">
                                        <th class="py-3 px-4 font-medium">{{ __('Device Name') }}</th>
                                        <th class="py-3 px-4 font-medium">{{ __('OS / Client') }}</th>
                                        <th class="py-3 px-4 font-medium">{{ __('Last Seen') }}</th>
                                        <th class="py-3 px-4 font-medium">{{ __('Status') }}</th>
                                        <th class="py-3 px-4 font-medium text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($devices as $device)
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $device->device_name }}</div>
                                                <div class="text-xs text-gray-500 font-mono">{{ $device->device_uuid }}</div>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                                <div>{{ $device->os_version ?? 'Windows' }}</div>
                                                @if ($device->client_version)
                                                    <div class="text-xs text-gray-500">v{{ $device->client_version }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                                {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : __('Never') }}
                                            </td>
                                            <td class="py-3 px-4">
                                                @if ($device->isRevoked())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                                        {{ __('Revoked') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                        {{ __('Active') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                @if (! $device->isRevoked())
                                                    <form method="POST" action="{{ route('devices.destroy', $device) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button onclick="return confirm('Revoke this desktop device?')">
                                                            {{ __('Revoke') }}
                                                        </x-danger-button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
