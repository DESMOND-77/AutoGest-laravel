@props(['sticky' => true])

<div {{ $attributes->class([
    'flex flex-wrap items-center justify-end gap-2 py-4',
    $sticky ? 'sticky bottom-0 z-20 -mx-4 mt-2 border-t border-line bg-[var(--surface-glass)] px-4 backdrop-blur-md sm:-mx-6 sm:px-6' : null,
]) }}>
    {{ $slot }}
</div>
