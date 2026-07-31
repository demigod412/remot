@extends('admin.layouts.app')
@section('title', 'Pages')
@section('page-title', 'Site Pages')

@section('content')

<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Page</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Slug</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Sections</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pages as $page)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:13px 20px;">
                    <div style="font-weight:500;color:var(--fg);">{{ $page->name }}</div>
                    @if($page->is_default)
                        <span class="badge-info" style="font-size:11px;margin-top:3px;display:inline-flex;">Default</span>
                    @endif
                </td>
                <td style="padding:13px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">/{{ $page->slug }}</td>
                <td style="padding:13px 20px;text-align:center;color:var(--fg-2);font-size:13px;">
                    {{ is_array($page->secs) ? count($page->secs) : 0 }}
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <a href="{{ route('admin.pages.edit', $page->id) }}"
                       style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;font-size:12.5px;color:var(--accent);background:rgba(47,84,235,0.08);text-decoration:none;"
                       title="Edit"
                       onmouseover="this.style.background='rgba(47,84,235,0.16)'"
                       onmouseout="this.style.background='rgba(47,84,235,0.08)'">
                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="file-text" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No pages found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
