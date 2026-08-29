@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-2 mt-4 text-sm" role="navigation">
        <div>
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 rounded-ui-sm px-3 py-1.5 text-content-muted">
                    <x-icon name="chevron-left" class="w-4 h-4" /> {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 rounded-ui-sm bg-surface px-3 py-1.5 text-content-secondary shadow-soft-sm hover:text-content transition">
                    <x-icon name="chevron-left" class="w-4 h-4" /> {{ __('pagination.previous') }}
                </a>
            @endif
        </div>
        <span class="text-content-muted">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>
        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 rounded-ui-sm bg-surface px-3 py-1.5 text-content-secondary shadow-soft-sm hover:text-content transition">
                    {{ __('pagination.next') }} <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-ui-sm px-3 py-1.5 text-content-muted">
                    {{ __('pagination.next') }} <x-icon name="chevron-right" class="w-4 h-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
