@props(['disabled' => false])

<textarea
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-ui-sm border-0 bg-surface px-3.5 py-2.5 text-sm text-content shadow-inset placeholder:text-content-muted focus:outline-none focus:shadow-inset-focus disabled:opacity-50 transition']) }}
>{{ $slot }}</textarea>
