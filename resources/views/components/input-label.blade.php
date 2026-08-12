@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-content-secondary mb-1']) }}>
    {{ $value ?? $slot }}
</label>
