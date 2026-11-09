<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="-mb-px flex flex-wrap gap-6 text-sm font-medium">
        <a href="{{ route('groups.show', $group) }}"
           class="py-3 border-b-2 {{ request()->routeIs('groups.show') ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
            {{ __('Overview') }}
        </a>

        <a href="{{ route('groups.members', $group) }}"
           class="py-3 border-b-2 {{ request()->routeIs('groups.members') ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
            {{ __('Group Members') }}
        </a>

        @if ($canManageMembers ?? false)
            <a href="{{ route('groups.join-requests', $group) }}"
               class="py-3 border-b-2 {{ request()->routeIs('groups.join-requests') ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                {{ __('Join Requests') }}
            </a>

            <a href="{{ route('groups.invitations', $group) }}"
               class="py-3 border-b-2 {{ request()->routeIs('groups.invitations') ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                {{ __('Invitations') }}
            </a>
        @endif

        @if ($canManageSettings ?? false)
            <a href="{{ route('groups.settings', $group) }}"
               class="py-3 border-b-2 {{ request()->routeIs('groups.settings') ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                {{ __('Group Settings') }}
            </a>
        @endif
    </nav>
</div>
