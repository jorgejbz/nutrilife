{{-- =========================================================
     PAGINACIÓN SENCILLA (anterior / siguiente)
     Uso: @include('partials.pager', ['paginator' => $items])
     Conserva los filtros (->withQueryString() en el controlador).
     ========================================================= --}}
@if ($paginator->total() > 0)
    <nav class="nl-pager" aria-label="Paginación">
        <span>
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
            @if ($paginator->lastPage() > 1)
                · Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            @endif
        </span>

        @if ($paginator->lastPage() > 1)
            <div class="nl-pager-links">
                <a class="nl-btn nl-btn--ghost nl-btn--sm {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}"
                   href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev"
                   @if ($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                </a>
                <a class="nl-btn nl-btn--ghost nl-btn--sm {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}"
                   href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next"
                   @unless ($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>
                    Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </nav>
@endif
