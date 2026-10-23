@props(['paginator' => null, 'label' => 'Pagination'])

@if ($paginator && $paginator->total() > 0)
    <nav {{ $attributes->class(['flex flex-wrap items-center justify-between gap-3 text-sm']) }} aria-label="{{ $label }}">
        <p class="text-ink-2">
            Affichage {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        @if ($paginator->hasPages())
            <ul class="flex items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex h-9 items-center gap-1 rounded-pill px-3 text-ink-3" aria-disabled="true">
                            <x-ui.icon name="chevron-left" class="w-4 h-4" /> Précédent
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 items-center gap-1 rounded-pill px-3 text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                            <x-ui.icon name="chevron-left" class="w-4 h-4" /> Précédent
                        </a>
                    @endif
                </li>
                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    <li>
                        @if ($page === $paginator->currentPage())
                            <span aria-current="page" class="inline-flex size-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-on-accent">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex size-9 items-center justify-center rounded-full text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 items-center gap-1 rounded-pill px-3 text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                            Suivant <x-ui.icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    @else
                        <span class="inline-flex h-9 items-center gap-1 rounded-pill px-3 text-ink-3" aria-disabled="true">
                            Suivant <x-ui.icon name="chevron-right" class="w-4 h-4" />
                        </span>
                    @endif
                </li>
            </ul>
        @endif
    </nav>
@endif
