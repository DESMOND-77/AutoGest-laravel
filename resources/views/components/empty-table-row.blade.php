@props(['colspan', 'title', 'message' => null, 'action' => null, 'actionLabel' => null])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
        <p class="text-sm font-medium text-content">{{ $title }}</p>
        @if ($message)
            <p class="text-sm text-content-muted mt-1">{{ $message }}</p>
        @endif
        @if ($action && $actionLabel)
            <a href="{{ $action }}" class="inline-flex items-center gap-1.5 mt-4 rounded-ui-md bg-primary px-4 py-2 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
                <x-icon name="plus" class="w-4 h-4" /> {{ $actionLabel }}
            </a>
        @endif
    </td>
</tr>
