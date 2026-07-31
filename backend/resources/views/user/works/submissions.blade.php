@extends('user.layouts.app')
@section('title', 'Submissions — ' . $work->title)
@section('page-title', 'Work Submissions')

@section('content')
{{-- Breadcrumb --}}
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-4); margin-bottom:20px; flex-wrap:wrap;">
    <a href="{{ route('user.works.index') }}" style="color:var(--fg-4); text-decoration:none;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">My Works</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <a href="{{ route('user.works.show', $work->id) }}" style="color:var(--fg-4); text-decoration:none; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">{{ Str::limit($work->title, 40) }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-3);">Submissions</span>
</div>

{{-- Bulk approve (Alpine.js) --}}
<div x-data="{ selected: [] }" style="display:flex; flex-direction:column; gap:14px;">

    {{-- Filter + Bulk Action Bar --}}
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            @foreach(['' => 'All', '1' => 'Pending', '2' => 'Approved', '3' => 'Rejected'] as $val => $label)
            <a href="{{ route('user.works.submissions', array_merge(['id' => $work->id], $val ? ['status' => $val] : [])) }}"
               style="font-size:12px; font-weight:500; padding:6px 12px; border-radius:7px; text-decoration:none; transition:all .14s;
                      {{ request('status') == $val ? 'background:var(--accent); color:white; border:1px solid var(--accent);' : 'background:var(--surface); color:var(--fg-2); border:1px solid var(--border);' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
            <span x-show="selected.length > 0" style="font-size:12px; color:var(--fg-3);" x-text="selected.length + ' selected'"></span>
            <form method="POST" action="{{ route('user.works.submissions.bulk-approve', $work->id) }}"
                  x-show="selected.length > 0" id="bulk-form">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit"
                        onclick="return confirm('Approve selected submissions?')"
                        class="btn btn-primary btn-sm">
                    Bulk Approve
                </button>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card" style="overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <th style="padding:11px 18px; text-align:left; width:36px;">
                        <input type="checkbox" style="accent-color:var(--accent); cursor:pointer;"
                               @change="selected = $event.target.checked ? @json($submissions->getCollection()->where('status', 1)->pluck('id')->values()) : []">
                    </th>
                    <th style="padding:11px 18px; text-align:left; font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Worker</th>
                    <th style="padding:11px 18px; text-align:left; font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Submitted</th>
                    <th style="padding:11px 18px; text-align:center; font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Status</th>
                    <th style="padding:11px 18px; text-align:right; font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $sub)
                @php
                    $sc = [0=>'badge badge-info',1=>'badge badge-warning',2=>'badge badge-success',3=>'badge badge-danger'];
                    $sl = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected'];
                @endphp
                <tr style="border-bottom:1px solid var(--border); transition:background .12s;"
                    onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                    <td style="padding:12px 18px;">
                        @if($sub->status == 1)
                        <input type="checkbox" style="accent-color:var(--accent); cursor:pointer;"
                               :value="{{ $sub->id }}"
                               x-model="selected">
                        @endif
                    </td>
                    <td style="padding:12px 18px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:30px; height:30px; border-radius:50%; background:var(--accent-soft); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--accent); flex-shrink:0;">
                                {{ strtoupper(substr($sub->worker->firstname ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:13px; color:var(--fg);">{{ $sub->worker->fullname ?? 'Unknown' }}</div>
                                <div style="font-size:11.5px; color:var(--fg-4);">{{ '@' . ($sub->worker->username ?? '') }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px 18px; font-size:12.5px; color:var(--fg-3);">
                        {{ $sub->submitted_at ? $sub->submitted_at->diffForHumans() : '—' }}
                    </td>
                    <td style="padding:12px 18px; text-align:center;">
                        <span class="{{ $sc[$sub->status] ?? 'badge' }}" style="font-size:11.5px;">
                            {{ $sl[$sub->status] ?? '' }}
                        </span>
                    </td>
                    <td style="padding:12px 18px; text-align:right;">
                        <a href="{{ route('user.works.submissions.review', [$work->id, $sub->id]) }}"
                           style="font-size:12.5px; color:var(--accent); text-decoration:none; font-weight:500;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Review →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:48px 18px; text-align:center; font-size:13px; color:var(--fg-4);">No submissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $submissions->withQueryString()->links() }}
</div>
@endsection
