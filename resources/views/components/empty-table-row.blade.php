@props(['colspan', 'title', 'message' => null, 'action' => null, 'actionLabel' => null])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $title }}</p>
        @if ($message)
            <p class="text-sm text-gray-500 mt-1">{{ $message }}</p>
        @endif
        @if ($action && $actionLabel)
            <a href="{{ $action }}" class="inline-flex items-center mt-4 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                {{ $actionLabel }}
            </a>
        @endif
    </td>
</tr>
