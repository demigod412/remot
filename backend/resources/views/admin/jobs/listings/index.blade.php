@extends('admin.layouts.app')
@section('title', 'Job Listings')
@section('page-title', 'Job Listings')

@section('content')

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total Listings</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;" data-countup="{{ $stats['total'] }}">0</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending Review</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;" data-countup="{{ $stats['pending'] }}">0</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">awaiting approval</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Active</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;" data-countup="{{ $stats['active'] }}">0</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">live & hiring</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;" data-countup="{{ $stats['rejected'] }}">0</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">declined</div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.jobs.listings.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Search</label>
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Job title…" style="padding-left:32px;">
            </div>
        </div>
        <div style="width:140px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Status</label>
            <select name="status">
                <option value="">All</option>
                <option value="0" @selected(request('status')==='0')>Pending</option>
                <option value="1" @selected(request('status')==='1')>Active</option>
                <option value="2" @selected(request('status')==='2')>Closed</option>
                <option value="3" @selected(request('status')==='3')>Rejected</option>
            </select>
        </div>
        <div style="width:160px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Category</label>
            <select name="category">
                <option value="">All</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category')==$cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Filter</button>
            @if(request()->hasAny(['search','status','category']))
            <a href="{{ route('admin.jobs.listings.index') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Job / Employer</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Type</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Applications</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Posted</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($listings as $listing)
            @php
                $statusColors = [0=>'badge-warning',1=>'badge-success',2=>'badge-default',3=>'badge-danger'];
                $statusLabels = [0=>'Pending',1=>'Active',2=>'Closed',3=>'Rejected'];
            @endphp
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:12px 20px;">
                    <div style="font-weight:500;color:var(--fg);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $listing->title }}</div>
                    <div style="font-size:12px;color:var(--fg-3);margin-top:1px;">{{ $listing->employer->username ?? $listing->employer->email ?? '-' }}</div>
                </td>
                <td style="padding:12px 20px;">
                    <div style="font-size:12.5px;color:var(--fg-2);">{{ $listing->locationTypeLabel }}</div>
                    <div style="font-size:12px;color:var(--fg-3);margin-top:1px;">{{ $listing->employmentTypeLabel }}</div>
                </td>
                <td style="padding:12px 20px;">
                    <span class="{{ $statusColors[$listing->status] ?? 'badge-default' }}" style="font-size:11px;">
                        {{ $statusLabels[$listing->status] ?? '-' }}
                    </span>
                </td>
                <td style="padding:12px 20px;color:var(--fg-2);">{{ $listing->application_count }}</td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-3);">{{ $listing->created_at->format('M d, Y') }}</td>
                <td style="padding:12px 20px;">
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('admin.jobs.listings.show', $listing->id) }}"
                           style="font-size:12.5px;padding:5px 12px;border-radius:7px;background:rgba(47,84,235,0.08);color:var(--accent);text-decoration:none;"
                           onmouseover="this.style.background='rgba(47,84,235,0.16)'"
                           onmouseout="this.style.background='rgba(47,84,235,0.08)'">
                            Review
                        </a>
                        @if($listing->status === 0)
                        <form method="POST" action="{{ route('admin.jobs.listings.approve', $listing->id) }}">
                            @csrf
                            <button type="submit"
                                    style="font-size:12.5px;padding:5px 12px;border-radius:7px;background:rgba(34,197,94,0.08);color:#22C55E;border:none;cursor:pointer;font-family:inherit;transition:background .14s;"
                                    onmouseover="this.style.background='rgba(34,197,94,0.16)'"
                                    onmouseout="this.style.background='rgba(34,197,94,0.08)'">
                                Approve
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:56px;text-align:center;color:var(--fg-3);">No job listings found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:12px 18px;border-top:1px solid var(--border);">{{ $listings->links() }}</div>
</div>

@endsection
