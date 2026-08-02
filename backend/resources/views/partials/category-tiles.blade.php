{{--
    Category tiles: a discovery affordance for the task lists.

    Both browse pages already had working category FILTERS (a radio sidebar on the
    public list, a select on the dashboard list). What was missing was a way to see
    that categories exist at all before you go looking for a control. These tiles
    sit above the listing and link into the same ?category= query the filters use,
    so there is one filtering mechanism, not two.

    Usage:
        @include('partials.category-tiles', [
            'categories' => $categories,
            'routeName'  => 'works.index',
        ])

    Shared between the public (web.*) and dashboard (user.*) layouts, which use
    different CSS variable names for the same roles. The var(--fg, var(--text))
    fallback chain resolves against whichever set is present, so this renders
    correctly in both without a theme parameter.
--}}
@php
    $categories = $categories ?? collect();
    $activeId   = request('category');

    // Hide entirely when there is nothing to discover. A single category is not a
    // choice, and empty tiles are worse than no tiles.
    $tileCats = $categories->filter(fn ($c) => ($c->works_count ?? 0) > 0);
@endphp

@if($tileCats->count() > 1)
<div style="margin-bottom:22px;">
    <div style="display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:11px;">
        <h2 style="font-size:13px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3, var(--muted)); margin:0;">
            {{ __('Browse by category') }}
        </h2>
        @if($activeId)
            <a href="{{ route($routeName) }}"
               style="font-size:12px; color:var(--accent); text-decoration:none; flex-shrink:0;">
                {{ __('Show all categories') }}
            </a>
        @endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(158px, 1fr)); gap:10px;">
        @foreach($tileCats as $tile)
            @php $isActive = (string) $activeId === (string) $tile->id; @endphp
            <a href="{{ $isActive ? route($routeName) : route($routeName, ['category' => $tile->id]) }}"
               aria-current="{{ $isActive ? 'true' : 'false' }}"
               style="display:block; padding:13px 14px; border-radius:10px; text-decoration:none;
                      border:1.5px solid {{ $isActive ? 'var(--accent)' : 'var(--border)' }};
                      background:{{ $isActive ? 'rgba(99,102,241,0.07)' : 'transparent' }};
                      transition:border-color .15s, background .15s;">
                <div style="font-size:13.5px; font-weight:600; color:var(--fg, var(--text)); line-height:1.35; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $tile->name }}
                </div>
                <div style="font-size:11.5px; color:var(--fg-3, var(--muted)); margin-top:4px;">
                    {{ trans_choice('{1} :count task|[2,*] :count tasks', $tile->works_count, ['count' => $tile->works_count]) }}
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif
