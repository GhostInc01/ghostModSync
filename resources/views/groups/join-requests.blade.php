<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $group->tag }} {{ $group->name }} — {{ __('Pending Join Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('groups.partials.subnav')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">{{ __('Membership Join Requests') }}</h3>

                @if ($joinRequests->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No join requests found for this group.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach ($joinRequests as $joinRequest)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div class="font-semibold">{{ $joinRequest->user->name }} ({{ $joinRequest->user->email }})</div>
                                    @if ($joinRequest->message)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $joinRequest->message }}</p>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ __('Status:') }} <span class="font-medium">{{ $joinRequest->status->label() }}</span>
                                    </div>
                                </div>

                                @if ($joinRequest->isPending())
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('groups.join-requests.review', [$group, $joinRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved" />
                                            <x-primary-button>{{ __('Approve') }}</x-primary-button>
                                        </form>

                                        <form method="POST" action="{{ route('groups.join-requests.review', [$group, $joinRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected" />
                                            <x-danger-button>{{ __('Reject') }}</x-danger-button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
