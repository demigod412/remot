@extends('admin.layouts.app')
@section('title', 'Content Sections')
@section('page-title', 'Content Sections')

@section('content')

<div style="font-size:13px;color:var(--fg-3);margin-bottom:18px;">Edit the content blocks that appear on the public website.</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
    @forelse($sections as $section)
    @php
        $iconMap = [
            'hero'          => ['layout-template',  '#A78BFA', 'rgba(167,139,250,0.1)'],
            'how_it_works'  => ['list-ordered',      '#60A5FA', 'rgba(96,165,250,0.1)'],
            'features'      => ['star',              '#F59E0B', 'rgba(245,158,11,0.1)'],
            'faq'           => ['help-circle',       '#2f54eb', 'rgba(47,84,235,0.1)'],
            'cta'           => ['megaphone',         '#F472B6', 'rgba(244,114,182,0.1)'],
            'blog'          => ['newspaper',         '#38BDF8', 'rgba(56,189,248,0.1)'],
            'footer'        => ['layout-panel-top', 'var(--fg-3)', 'var(--surface-2)'],
            'seo'           => ['search',            '#A3E635', 'rgba(163,230,53,0.1)'],
        ];
        [$icon, $color, $bg] = $iconMap[$section->section_key] ?? ['file-text', 'var(--fg-3)', 'var(--surface-2)'];
        $dataPreview = is_array($section->section_data) ? count($section->section_data) . ' fields' : '—';
    @endphp
    <div class="jobstation-card" style="padding:18px;display:flex;align-items:flex-start;gap:14px;">
        <div style="width:42px;height:42px;border-radius:12px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="{{ $icon }}" style="width:20px;height:20px;color:{{ $color }};"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:13.5px;color:var(--fg);text-transform:capitalize;">
                {{ str_replace('_', ' ', $section->section_key) }}
            </div>
            <div style="font-size:12px;color:var(--fg-4);margin-top:2px;">{{ $dataPreview }}</div>
        </div>
        <a href="{{ route('admin.content.edit', $section->id) }}"
           style="flex-shrink:0;padding:6px;border-radius:7px;color:var(--fg-3);display:flex;align-items:center;"
           title="Edit"
           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
           onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
            <i data-lucide="pencil" style="width:15px;height:15px;"></i>
        </a>
    </div>
    @empty
    <div class="jobstation-card" style="padding:56px;text-align:center;color:var(--fg-3);grid-column:1/-1;">
        <i data-lucide="layout-template" style="width:36px;height:36px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
        <div style="font-size:14px;">No content sections found. Run seeders to populate them.</div>
    </div>
    @endforelse
</div>

@endsection
