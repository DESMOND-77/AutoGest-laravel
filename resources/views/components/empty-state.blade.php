@props(['icon' => 'archive-box', 'title', 'message' => null, 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center px-6 py-14']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-ui-lg bg-primary/10 text-primary">
        <x-icon :name="$icon" class="w-7 h-7" />
    </span>
    <p class="mt-4 text-sm font-semibold text-content">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-content-muted max-w-sm">{{ $message }}</p>
    @endif
    @if ($action && $actionLabel)
        <x-button variant="primary" :href="$action" class="mt-5">
            <x-icon name="plus" class="w-4 h-4" /> {{ $actionLabel }}
        </x-button>
    @endif
</div>
