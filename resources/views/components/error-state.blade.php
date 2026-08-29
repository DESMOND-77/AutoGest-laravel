@props(['title' => 'Une erreur est survenue', 'message' => null, 'retry' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center px-6 py-14']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-ui-lg bg-danger/10 text-danger">
        <x-icon name="exclamation-triangle" class="w-7 h-7" />
    </span>
    <p class="mt-4 text-sm font-semibold text-content">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-content-muted max-w-sm">{{ $message }}</p>
    @endif
    @if ($retry)
        <x-button variant="secondary" :href="$retry" class="mt-5">Réessayer</x-button>
    @endif
</div>
