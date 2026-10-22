@props(['show' => 'show', 'maxWidth' => 'md'])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth] ?? 'sm:max-w-md';
@endphp

<div
    x-show="{{ $show }}"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-modal="true"
    role="dialog"
>
    <div
        x-show="{{ $show }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-content/60"
        @click="{{ $show }} = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="{{ $show }}"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            {{ $attributes->class(['relative w-full rounded-ui-xl bg-surface shadow-soft-hover p-6', $maxWidthClass]) }}
            @click.stop
        >
            {{ $slot }}
        </div>
    </div>
</div>
