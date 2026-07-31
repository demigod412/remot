@extends('admin.layouts.app')
@section('title', 'Edit Payout Method')
@section('page-title', 'Edit Payout Method')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.payout-methods.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Payout Methods</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">{{ $method->name }}</span>
</div>

<div style="max-width:700px;">
    <div class="jobstation-card" style="padding:24px;">
        <form method="POST" action="{{ route('admin.payout-methods.update', $method->id) }}">
            @csrf @method('PUT')

            @if($errors->any())
            <div style="padding:10px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:8px;font-size:13px;color:#EF4444;margin-bottom:16px;">{{ $errors->first() }}</div>
            @endif

            @include('admin.payout-methods._form', ['method' => $method])

            <div style="display:flex;gap:10px;padding-top:16px;margin-top:8px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
                    <i data-lucide="save" style="width:14px;height:14px;"></i> Save Changes
                </button>
                <a href="{{ route('admin.payout-methods.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
