@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-ui-lg shadow-soft'.($padded ? ' p-6' : '')]) }}>
    {{ $slot }}
</div>
