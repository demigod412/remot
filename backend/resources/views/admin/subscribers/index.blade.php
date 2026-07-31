@extends('admin.layouts.app')
@section('title', 'Subscribers')
@section('page-title', 'Subscribers')

@section('content')

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Left: Subscribers table --}}
    <div>
        {{-- Search --}}
        <div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
            <form method="GET" action="{{ route('admin.subscribers.index') }}" style="display:flex;gap:10px;">
                <div style="flex:1;position:relative;">
                    <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by email…" style="padding-left:32px;">
                </div>
                <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.subscribers.index') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
                @endif
            </form>
        </div>

        <div class="jobstation-card" style="overflow:hidden;">
            <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:13.5px;font-weight:600;color:var(--fg);">{{ $subscribers->total() }} Subscribers</span>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:9px 18px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">#</th>
                        <th style="padding:9px 18px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Email</th>
                        <th style="padding:9px 18px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Subscribed</th>
                        <th style="padding:9px 18px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $i => $sub)
                    <tr style="border-bottom:1px solid var(--border);"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background=''">
                        <td style="padding:12px 18px;color:var(--fg-4);font-size:12px;">{{ $subscribers->firstItem() + $i }}</td>
                        <td style="padding:12px 18px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:50%;background:rgba(47,84,235,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="mail" style="width:12px;height:12px;color:var(--accent);"></i>
                                </div>
                                <span style="color:var(--fg-2);">{{ $sub->email }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 18px;font-size:12px;color:var(--fg-3);">{{ $sub->created_at->format('M d, Y') }}</td>
                        <td style="padding:12px 18px;text-align:right;">
                            <form method="POST" action="{{ route('admin.subscribers.delete', $sub->id) }}"
                                  onsubmit="return confirm('Remove this subscriber?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;margin-left:auto;"
                                        title="Remove"
                                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding:56px;text-align:center;color:var(--fg-3);">
                            <i data-lucide="users" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                            No subscribers yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($subscribers->hasPages())
            <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
                <div>Showing {{ $subscribers->firstItem() }}–{{ $subscribers->lastItem() }} of {{ $subscribers->total() }}</div>
                <div style="display:flex;gap:4px;">
                    @if(!$subscribers->onFirstPage())
                        <a href="{{ $subscribers->previousPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Prev</a>
                    @endif
                    @if($subscribers->hasMorePages())
                        <a href="{{ $subscribers->nextPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Next</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Right: Send Email panel --}}
    <div class="jobstation-card" style="padding:20px;">
        <div style="font-weight:600;font-size:14px;color:var(--fg);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i data-lucide="send" style="width:15px;height:15px;color:var(--accent);"></i>
            Email All Subscribers
        </div>
        <form method="POST" action="{{ route('admin.subscribers.send-email') }}" style="display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Email subject…" required>
                @error('subject') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Message</label>
                <textarea name="message" rows="8" placeholder="Write your message here…" style="resize:vertical;" required>{{ old('message') }}</textarea>
                @error('message') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div style="padding:10px 12px;border-radius:8px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);display:flex;align-items:flex-start;gap:8px;">
                <i data-lucide="alert-triangle" style="width:13px;height:13px;color:#F59E0B;flex-shrink:0;margin-top:1px;"></i>
                <span style="font-size:12px;color:#F59E0B;">This will send to all {{ $subscribers->total() }} subscribers.</span>
            </div>
            <button type="submit" class="btn-primary" style="padding:10px;font-size:13px;"
                    onclick="return confirm('Send email to all {{ $subscribers->total() }} subscribers?')">
                <i data-lucide="send" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:5px;"></i> Send Now
            </button>
        </form>
    </div>

</div>

@endsection
