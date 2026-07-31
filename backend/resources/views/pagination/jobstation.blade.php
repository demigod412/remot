@php
    $cell     = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 11px;border-radius:8px;font-size:13px;font-weight:500;line-height:1;text-decoration:none;border:1px solid var(--border,#e5e7eb);transition:background .12s,border-color .12s;';
    $link     = $cell.'color:var(--fg,#1f2937);background:var(--surface,#fff);';
    $current  = $cell.'color:#fff;background:var(--primary,#6C47FF);border-color:var(--primary,#6C47FF);';
    $disabled = $cell.'color:var(--fg-4,#9ca3af);background:transparent;opacity:.55;cursor:default;';
    $dots     = 'display:inline-flex;align-items:center;padding:0 4px;color:var(--fg-4,#9ca3af);font-size:13px;';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="font-size:12.5px;color:var(--fg-3,#6b7280);">
            {{ __('Showing') }}
            <span style="font-weight:600;color:var(--fg,#1f2937);">{{ $paginator->firstItem() ?? 0 }}</span>–<span style="font-weight:600;color:var(--fg,#1f2937);">{{ $paginator->lastItem() ?? 0 }}</span>
            {{ __('of') }} <span style="font-weight:600;color:var(--fg,#1f2937);">{{ $paginator->total() }}</span>
        </div>

        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" style="{{ $disabled }}">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}" style="{{ $link }}">&lsaquo;</a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="{{ $dots }}">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" style="{{ $current }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="{{ $link }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}" style="{{ $link }}">&rsaquo;</a>
            @else
                <span aria-disabled="true" style="{{ $disabled }}">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
