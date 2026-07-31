@extends('admin.layouts.app')
@section('title', $contract->reference)
@section('page-title', 'Contract Detail')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.contracts.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Contracts</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);font-family:ui-monospace,monospace;font-size:12px;">{{ $contract->reference }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;" class="contract-show-grid">

    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="jobstation-card" style="padding:24px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
                <div style="flex:1;min-width:0;">
                    <h2 style="font-size:20px;font-weight:700;color:var(--fg);margin:0 0 4px;line-height:1.3;">{{ $contract->title }}</h2>
                    <div style="font-size:11.5px;color:var(--fg-3);font-family:ui-monospace,monospace;">{{ $contract->reference }}</div>
                </div>
                <span class="{{ $contract->statusColor }}" style="flex-shrink:0;padding:4px 12px;border-radius:8px;font-size:13px;font-weight:500;">
                    {{ $contract->statusLabel }}
                </span>
            </div>
            <div style="font-size:13.5px;color:var(--fg-2);line-height:1.75;">
                {!! richBody($contract->description) !!}
            </div>
        </div>

        {{-- Worker Submission --}}
        @if($contract->worker_note)
        <div class="jobstation-card" style="padding:24px;">
            <h4 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <i data-lucide="upload" style="width:15px;height:15px;color:#a78bfa;"></i> Worker Submission
            </h4>
            <p style="font-size:13.5px;color:var(--fg-2);white-space:pre-line;line-height:1.7;margin:0 0 14px;">{{ $contract->worker_note }}</p>
            @if($contract->proof_file)
            <a href="{{ route('secure.contractProof', $contract->id) }}"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);font-size:12.5px;color:var(--fg-2);text-decoration:none;transition:.12s;"
               onmouseover="this.style.background='var(--surface-3)'" onmouseout="this.style.background='var(--surface-2)'">
                <i data-lucide="paperclip" style="width:13px;height:13px;"></i> Download Proof File
            </a>
            @endif
            @if($contract->submitted_at)
            <div style="font-size:11.5px;color:var(--fg-4);margin-top:10px;">Submitted {{ $contract->submitted_at->format('M d, Y H:i') }}</div>
            @endif
        </div>
        @endif

        @if($contract->employer_note)
        <div class="jobstation-card" style="padding:24px;">
            <h4 style="font-size:14px;font-weight:500;color:var(--fg);margin:0 0 8px;">
                {{ $contract->status === 6 ? 'Dispute Reason' : 'Employer Note' }}
            </h4>
            <p style="font-size:13px;color:var(--fg-2);margin:0;">{{ $contract->employer_note }}</p>
        </div>
        @endif

        @if($contract->declined_reason)
        <div class="jobstation-card" style="padding:24px;">
            <h4 style="font-size:14px;font-weight:500;color:var(--fg);margin:0 0 8px;">Decline Reason</h4>
            <p style="font-size:13px;color:var(--fg-2);margin:0;">{{ $contract->declined_reason }}</p>
        </div>
        @endif

        {{-- Dispute Resolution --}}
        @if($contract->status === 6)
        <div class="jobstation-card" style="padding:24px;border:1px solid rgba(239,68,68,0.2);">
            <h4 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i data-lucide="alert-triangle" style="width:15px;height:15px;color:#EF4444;"></i> Resolve Dispute
            </h4>
            <form method="POST" action="{{ route('admin.contracts.resolve', $contract->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;" class="dispute-grid">
                    <label style="cursor:pointer;">
                        <input type="radio" name="resolution" value="pay_worker" style="display:none;" id="res_worker"
                               onchange="document.querySelectorAll('.res-card').forEach(c=>c.style.borderColor='var(--border)');document.getElementById('card_worker').style.borderColor='#22C55E'">
                        <div id="card_worker" class="res-card"
                             style="padding:14px;border-radius:10px;border:2px solid var(--border);cursor:pointer;transition:.12s;"
                             onclick="document.getElementById('res_worker').click()">
                            <div style="font-size:13px;font-weight:500;color:var(--fg);margin-bottom:4px;">Pay Worker</div>
                            <div style="font-size:12px;color:var(--fg-3);">Release {{ formatCoins($contract->amount) }} to worker</div>
                        </div>
                    </label>
                    <label style="cursor:pointer;">
                        <input type="radio" name="resolution" value="refund_employer" style="display:none;" id="res_employer"
                               onchange="document.querySelectorAll('.res-card').forEach(c=>c.style.borderColor='var(--border)');document.getElementById('card_employer').style.borderColor='#F59E0B'">
                        <div id="card_employer" class="res-card"
                             style="padding:14px;border-radius:10px;border:2px solid var(--border);cursor:pointer;transition:.12s;"
                             onclick="document.getElementById('res_employer').click()">
                            <div style="font-size:13px;font-weight:500;color:var(--fg);margin-bottom:4px;">Refund Employer</div>
                            <div style="font-size:12px;color:var(--fg-3);">Return {{ formatCoins($contract->amount) }} to employer</div>
                        </div>
                    </label>
                </div>
                @error('resolution') <div style="font-size:12px;color:#EF4444;margin-bottom:10px;">{{ $message }}</div> @enderror
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Admin Note</label>
                    <textarea name="admin_note" rows="3" placeholder="Resolution notes…"
                              style="width:100%;font-size:13px;resize:none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:9px 22px;"
                        onclick="return confirm('Resolve this dispute? This action is irreversible.')">
                    Confirm Resolution
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Right Sidebar --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Parties --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">Parties</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <div style="font-size:11px;color:var(--fg-4);margin-bottom:4px;">Employer</div>
                    <div style="font-size:13px;font-weight:500;color:var(--fg);">{{ $contract->employer->fullname ?: $contract->employer->username }}</div>
                    <div style="font-size:11.5px;color:var(--fg-3);">{{ $contract->employer->email }}</div>
                    <a href="{{ route('admin.users.show', $contract->employer_id) }}"
                       style="font-size:12px;color:var(--accent);text-decoration:none;"
                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View profile →</a>
                </div>
                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="font-size:11px;color:var(--fg-4);margin-bottom:4px;">Worker</div>
                    <div style="font-size:13px;font-weight:500;color:var(--fg);">{{ $contract->worker->fullname ?: $contract->worker->username }}</div>
                    <div style="font-size:11.5px;color:var(--fg-3);">{{ $contract->worker->email }}</div>
                    <a href="{{ route('admin.users.show', $contract->worker_id) }}"
                       style="font-size:12px;color:var(--accent);text-decoration:none;"
                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View profile →</a>
                </div>
            </div>
        </div>

        {{-- Financial & Timeline --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">Details</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Contract Value</span>
                    <span style="color:var(--accent);font-weight:600;">{{ formatCoins($contract->amount) }}</span>
                </div>
                @if($contract->status === 3 && $contract->commission_amount > 0)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Platform Commission</span>
                    <span style="color:#F59E0B;font-weight:500;">{{ formatCoins($contract->commission_amount) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:8px;">
                    <span style="color:var(--fg-3);">Worker Received</span>
                    <span style="color:#22C55E;font-weight:500;">{{ formatCoins($contract->worker_payout) }}</span>
                </div>
                @endif
                @if($contract->deadline_at)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Deadline</span>
                    <span style="color:{{ $contract->isOverdue ? '#EF4444' : 'var(--fg-2)' }};">{{ $contract->deadline_at->format('M d, Y') }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Created</span>
                    <span style="color:var(--fg-2);">{{ $contract->created_at->format('M d, Y') }}</span>
                </div>
                @if($contract->accepted_at)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Accepted</span>
                    <span style="color:var(--fg-2);">{{ $contract->accepted_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($contract->submitted_at)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Submitted</span>
                    <span style="color:var(--fg-2);">{{ $contract->submitted_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($contract->completed_at)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--fg-3);">Completed</span>
                    <span style="color:#22C55E;">{{ $contract->completed_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Force Cancel --}}
        @if(in_array($contract->status, [0, 1, 2, 6]))
        <form method="POST" action="{{ route('admin.contracts.force-cancel', $contract->id) }}">
            @csrf
            <button type="submit"
                    style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#EF4444;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.15)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.08)'"
                    onclick="return confirm('Force-cancel this contract? Employer will be refunded if coins were held.')">
                <i data-lucide="x-circle" style="width:14px;height:14px;"></i> Force Cancel
            </button>
        </form>
        @endif

    </div>

</div>

<style>
@media (max-width: 900px) {
    .contract-show-grid { grid-template-columns: 1fr !important; }
    .dispute-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection
