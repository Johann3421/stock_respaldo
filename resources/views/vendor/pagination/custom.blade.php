@if ($paginator->hasPages())
<style>
    .custom-pagination .page-link {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        padding: .4rem .65rem;
        border-radius: .375rem;
        min-width: 36px;
    }
    .custom-pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; color: #fff; }
    .custom-pagination .page-link { color: #495057; }
    .custom-pagination .page-item.disabled .page-link { color: #adb5bd; }
    .custom-pagination-summary { margin-top: .5rem; }

    /* Responsive: en móviles simplificamos la paginación
       - Mostrar sólo Previous, Current y Next
       - Permitir scroll horizontal si hay muchos elementos
    */
    @media (max-width: 576px) {
        .custom-pagination { display: flex; align-items: center; justify-content: center; gap: .25rem; overflow-x: auto; -webkit-overflow-scrolling: touch; padding: .25rem 0; }
        .custom-pagination .page-item { display: none; }
        .custom-pagination .page-item:first-child,
        .custom-pagination .page-item:last-child,
        .custom-pagination .page-item.active { display: inline-flex; }
        .custom-pagination .page-link { padding: .25rem .45rem; min-width: 34px; }
        .custom-pagination-summary { font-size: .8rem; margin-top: .4rem; }
    }
</style>

<nav aria-label="Paginación">
    <ul class="pagination justify-content-center my-2 custom-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                <span class="page-link" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente">
                <span class="page-link" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </li>
        @endif
    </ul>

    <div class="text-center small text-muted custom-pagination-summary">
        Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados
    </div>
</nav>

@endif
