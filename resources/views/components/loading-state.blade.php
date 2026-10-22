@props(['label' => 'Chargement…'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-content-muted']) }}>
    <svg class="w-8 h-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <p class="mt-3 text-sm">{{ $label }}</p>
</div>
