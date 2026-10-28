@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="monevia-pagination">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ellipsis">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&raquo;</a>
        @else
            <span class="is-disabled" aria-disabled="true">&raquo;</span>
        @endif
    </nav>
@endif
