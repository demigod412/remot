@extends('user.layouts.app')
@section('title', $contract->title)
@section('page-title', 'Contract Detail')

@section('content')
{{-- Breadcrumb --}}
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-4); margin-bottom:20px; flex-wrap:wrap;">
    <a href="{{ $isEmployer ? route('user.contracts.sent') : route('user.contracts.received') }}"
       style="color:var(--fg-4); text-decoration:none;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">
        {{ $isEmployer ? 'Sent Contracts' : 'Received Contracts' }}
    </a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-3); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $contract->title }}</span>
</div>

<div style="display:grid; grid-template-columns:1fr 300px; gap:20px;" class="contract-detail-grid">

    {{-- Left: Contract Content --}}
    <div style="display:flex; flex-direction:column; gap:14px;">

        {{-- Header --}}
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
                <div>
                    <h2 style="font-size:19px; font-weight:700; color:var(--fg); margin:0 0 4px; letter-spacing:-0.3px;">{{ $contract->title }}</h2>
                    <span class="mono" style="font-size:11.5px; color:var(--fg-4);">Ref: {{ $contract->reference }}</span>
                </div>
                <span class="{{ $contract->statusColor }}" style="font-size:12.5px; flex-shrink:0; padding:5px 12px; border-radius:999px; font-weight:500;">
                    {{ $contract->statusLabel }}
                </span>
            </div>
            <div style="font-size:13.5px; color:var(--fg-2); line-height:1.7;">
                {!! richBody($contract->description) !!}
            </div>
        </div>

        {{-- Worker Submission --}}
        @if($contract->status >= 2 && $contract->worker_note)
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; color:var(--fg); margin-bottom:14px;">
                <i data-lucide="upload" style="width:15px; height:15px; color:var(--accent);"></i> Work Submission
            </div>
            <p style="font-size:13.5px; color:var(--fg-2); white-space:pre-line; line-height:1.65; margin:0 0 14px;">{{ $contract->worker_note }}</p>
            @if($contract->proof_file)
            <a href="{{ route('secure.contractProof', $contract->id) }}"
               target="_blank"
               style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; padding:7px 14px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); color:var(--fg-2); text-decoration:none; transition:all .14s;"
               onmouseover="this.style.color='var(--accent)'; this.style.borderColor='var(--accent)'" onmouseout="this.style.color='var(--fg-2)'; this.style.borderColor='var(--border)'">
                <i data-lucide="paperclip" style="width:14px; height:14px;"></i> Download Proof File
            </a>
            @endif
            @if($contract->submitted_at)
            <p style="font-size:11.5px; color:var(--fg-4); margin:10px 0 0;">Submitted {{ $contract->submitted_at->format('M d, Y H:i') }}</p>
            @endif
        </div>
        @endif

        {{-- Employer Note (cancel/dispute) --}}
        @if($contract->employer_note && in_array($contract->status, [5, 6]))
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:8px;">
                {{ $contract->status === 6 ? 'Dispute Reason' : 'Cancellation Note' }}
            </div>
            <p style="font-size:13.5px; color:var(--fg-2); margin:0;">{{ $contract->employer_note }}</p>
        </div>
        @endif

        @if($contract->status === 4 && $contract->declined_reason)
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:8px;">Decline Reason</div>
            <p style="font-size:13.5px; color:var(--fg-2); margin:0;">{{ $contract->declined_reason }}</p>
        </div>
        @endif

        {{-- ── Milestones ─────────────────────────────────── --}}
        @if($contract->milestones->isNotEmpty())
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; color:var(--fg); margin-bottom:18px;">
                <i data-lucide="layers" style="width:15px; height:15px; color:var(--accent);"></i> Milestones
                <span class="mono" style="font-size:12px; font-weight:400; color:var(--fg-4);">({{ $contract->milestones->where('status', 2)->count() }}/{{ $contract->milestones->count() }} approved)</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($contract->milestones as $ms)
            @php
                $msBg    = [0=>'var(--surface-2)', 1=>'rgba(96,165,250,0.06)', 2=>'rgba(34,197,94,0.06)', 3=>'rgba(239,68,68,0.06)'][$ms->status] ?? 'var(--surface-2)';
                $msColor = $ms->statusColor;
            @endphp
            <div style="border:1px solid var(--border); border-radius:12px; padding:16px; background:{{ $msBg }};" x-data="{ showSubmit: false, showDispute: false }">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:3px;">
                            <span class="mono" style="font-size:11px; color:var(--fg-4); background:var(--surface-3); padding:1px 6px; border-radius:4px;">#{{ $ms->sort_order + 1 }}</span>
                            <span style="font-size:14px; font-weight:600; color:var(--fg);">{{ $ms->title }}</span>
                        </div>
                        @if($ms->description)<p style="font-size:13px; color:var(--fg-3); margin:0;">{{ $ms->description }}</p>@endif
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0;">
                        <span class="mono" style="font-size:13.5px; font-weight:700; color:var(--accent);">{{ formatCoins($ms->amount) }}</span>
                        <span style="font-size:11.5px; font-weight:500; color:{{ $msColor }};">{{ $ms->statusLabel }}</span>
                    </div>
                </div>

                @if($ms->deadline_at)
                <div style="font-size:12px; color:{{ $ms->isOverdue ? '#EF4444' : 'var(--fg-4)' }}; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <i data-lucide="calendar" style="width:11px; height:11px;"></i>
                    Due {{ $ms->deadline_at->format('M d, Y') }}{{ $ms->isOverdue ? ' · Overdue' : '' }}
                </div>
                @endif

                @if($ms->status === 1)
                {{-- Submitted content --}}
                <div style="padding:10px 12px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); margin-bottom:10px;">
                    <div style="font-size:11.5px; color:var(--fg-4); margin-bottom:5px; font-weight:500;">Worker's Submission</div>
                    <p style="font-size:13px; color:var(--fg-2); margin:0 0 6px; white-space:pre-line;">{{ $ms->worker_note }}</p>
                    @if($ms->proof_file)
                    <a href="{{ route('secure.contractProof', ['contract' => $contract->id, 'milestone' => $ms->id]) }}"
                       target="_blank" style="font-size:12px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:5px;"
                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        <i data-lucide="paperclip" style="width:12px; height:12px;"></i> Download Proof
                    </a>
                    @endif
                </div>
                @if($isEmployer)
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <form method="POST" action="{{ route('user.contracts.milestones.approve', [$contract->id, $ms->id]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="font-size:12px; padding:6px 14px;"
                                onclick="return confirm('Approve this milestone and release payment?')">
                            <i data-lucide="check-circle" style="width:13px; height:13px;"></i> Approve & Pay
                        </button>
                    </form>
                    <button @click="showDispute = !showDispute"
                            style="font-size:12px; padding:6px 14px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:transparent; cursor:pointer; font-family:inherit; transition:background .14s;"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                        Dispute
                    </button>
                </div>
                <div x-show="showDispute" x-transition style="margin-top:10px;">
                    <form method="POST" action="{{ route('user.contracts.milestones.dispute', [$contract->id, $ms->id]) }}"
                          style="display:flex; flex-direction:column; gap:8px;">
                        @csrf
                        <textarea name="employer_note" rows="2" required placeholder="Describe the issue…" style="resize:none;"></textarea>
                        <div><button type="submit" class="btn btn-danger" style="font-size:12px; padding:6px 14px;">Confirm Dispute</button></div>
                    </form>
                </div>
                @endif
                @endif

                @if($ms->status === 2 && $ms->worker_payout > 0)
                <div style="font-size:12px; color:#22C55E; display:flex; align-items:center; gap:5px;">
                    <i data-lucide="check-circle" style="width:12px; height:12px;"></i>
                    Paid {{ formatCoins($ms->worker_payout) }} on {{ $ms->completed_at?->format('M d, Y') }}
                </div>
                @endif

                {{-- Worker submit form (per milestone) --}}
                @if(!$isEmployer && $contract->status === 1 && $ms->status === 0)
                <button @click="showSubmit = !showSubmit"
                        class="btn btn-primary" style="font-size:12px; padding:6px 14px;">
                    Submit this Milestone
                </button>
                <div x-show="showSubmit" x-transition style="margin-top:12px;">
                    <form method="POST" action="{{ route('user.contracts.milestones.submit', [$contract->id, $ms->id]) }}"
                          enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
                        @csrf
                        <textarea name="worker_note" rows="4" required min-length="10" placeholder="Describe what you delivered for this milestone…" style="resize:none;"></textarea>
                        <input type="file" name="proof_file">
                        <div>
                            <button type="submit" class="btn btn-primary" style="font-size:12px; padding:7px 18px;"
                                    onclick="return confirm('Submit this milestone?')">Submit Milestone</button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
            </div>
        </div>
        @endif

        {{-- Worker: Submit Work Form (non-milestone contracts only) --}}
        @if(!$isEmployer && $contract->status === 1 && $contract->milestones->isEmpty())
        <div class="card" style="padding:24px;">
            <div style="font-size:14px; font-weight:600; color:var(--fg); margin-bottom:16px;">Submit Your Work</div>
            <form method="POST" action="{{ route('user.contracts.submit', $contract->id) }}"
                  enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:14px;">
                @csrf
                <div>
                    <label style="display:block; font-size:11.5px; color:var(--fg-3); margin-bottom:7px; font-weight:500;">
                        Completion Notes <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="worker_note" rows="5"
                              placeholder="Describe what you've done, links, instructions for the employer…"
                              style="{{ $errors->has('worker_note') ? 'border-color:#EF4444;' : '' }}">{{ old('worker_note') }}</textarea>
                    @error('worker_note')<p style="font-size:11.5px; color:#EF4444; margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; color:var(--fg-3); margin-bottom:7px; font-weight:500;">
                        Proof File <span style="font-size:11px; color:var(--fg-4); font-weight:400;">(optional – max 10MB)</span>
                    </label>
                    <input type="file" name="proof_file"
                           style="{{ $errors->has('proof_file') ? 'border-color:#EF4444;' : '' }}">
                    @error('proof_file')<p style="font-size:11.5px; color:#EF4444; margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 24px;"
                            onclick="return confirm('Submit your work? The employer will review it.')">
                        Submit Work
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Worker: Decline Form --}}
        @if(!$isEmployer && $contract->status === 0)
        <div class="card" style="padding:24px;" x-data="{ showDecline: false }">
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <form method="POST" action="{{ route('user.contracts.accept', $contract->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 20px;"
                            onclick="return confirm('Accept this contract? {{ formatCoins($contract->amount) }} will be held in escrow.')">
                        Accept Contract
                    </button>
                </form>
                <button @click="showDecline = !showDecline"
                        style="padding:10px 20px; font-size:13px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:transparent; cursor:pointer; font-family:inherit; transition:background .14s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                    Decline
                </button>
            </div>
            <div x-show="showDecline" x-transition style="margin-top:14px; display:flex; flex-direction:column; gap:10px;">
                <form method="POST" action="{{ route('user.contracts.decline', $contract->id) }}"
                      style="display:flex; flex-direction:column; gap:10px;">
                    @csrf
                    <textarea name="declined_reason" rows="3" placeholder="Reason for declining (optional)…"></textarea>
                    <div>
                        <button type="submit" class="btn btn-danger" style="font-size:13px; padding:9px 20px;">Confirm Decline</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Rating Section (contract completed) --}}
        @if($contract->status === 3)
        @php
            $myRating = \App\Models\Rating::where('rater_id', auth()->id())
                ->where('ratable_id', $contract->id)
                ->where('ratable_type', \App\Models\Contract::class)
                ->first();
            $rateeForMe = $isEmployer ? $contract->worker : $contract->employer;
            $counterpartRated = \App\Models\Rating::where('rater_id', $rateeForMe->id)
                ->where('ratable_id', $contract->id)
                ->where('ratable_type', \App\Models\Contract::class)
                ->exists();
        @endphp
        <div class="card" style="padding:24px;" x-data="{ starHover: {{ $myRating ? $myRating->rating : 0 }}, starPick: {{ $myRating ? $myRating->rating : 0 }} }">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <i data-lucide="star" style="width:15px; height:15px; color:#F59E0B;"></i>
                <span style="font-size:14px; font-weight:600; color:var(--fg);">Rate {{ $isEmployer ? 'the worker' : 'the employer' }}</span>
            </div>
            <p style="font-size:12px; color:var(--fg-4); margin:0 0 16px; line-height:1.5;">Ratings are revealed only after both parties submit.</p>

            @if(session('success') && str_contains(session('success'), 'Rating'))
            <div style="padding:10px 14px; border-radius:8px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); color:#22C55E; font-size:13px; margin-bottom:14px;">{{ session('success') }}</div>
            @endif
            @if(session('info') && str_contains(session('info') ?? '', 'already'))
            <div style="padding:10px 14px; border-radius:8px; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.2); color:#60A5FA; font-size:13px; margin-bottom:14px;">{{ session('info') }}</div>
            @endif

            @if($myRating)
            <div style="padding:14px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border);">
                <p style="font-size:13px; color:var(--fg-3); margin:0 0 8px;">Your rating was submitted.</p>
                <div style="display:flex; gap:2px; margin-bottom:8px;">
                    @for($i = 1; $i <= 5; $i++)
                    <span style="font-size:20px; line-height:1; color:{{ $i <= $myRating->rating ? '#F59E0B' : 'var(--border)' }};">★</span>
                    @endfor
                </div>
                @if($myRating->review)<p style="font-size:13px; color:var(--fg-3); font-style:italic; margin:0 0 6px;">"{{ $myRating->review }}"</p>@endif
                @if(!$counterpartRated)
                <p style="font-size:11.5px; color:var(--fg-4); margin:0;">Waiting for {{ $rateeForMe->username }} to also rate…</p>
                @else
                <p style="font-size:11.5px; color:#22C55E; margin:0; display:flex; align-items:center; gap:5px;">
                    <i data-lucide="check-circle" style="width:12px; height:12px;"></i> Both ratings are now visible on your profiles.
                </p>
                @endif
            </div>
            @else
            <form method="POST" action="{{ route('user.ratings.store') }}">
                @csrf
                <input type="hidden" name="ratable_type" value="contract">
                <input type="hidden" name="ratable_id" value="{{ $contract->id }}">
                <input type="hidden" name="ratee_id" value="{{ $rateeForMe->id }}">
                <input type="hidden" name="rating" :value="starPick">

                <div style="display:flex; gap:3px; margin-bottom:14px; align-items:center;">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button"
                            @mouseover="starHover = {{ $i }}"
                            @mouseleave="starHover = starPick"
                            @click="starPick = {{ $i }}"
                            style="font-size:28px; line-height:1; background:none; border:none; cursor:pointer; padding:0 2px; transition:transform .1s; outline:none;"
                            :style="(starHover >= {{ $i }} || starPick >= {{ $i }}) ? 'color:#F59E0B; transform:scale(1.15)' : 'color:var(--border)'">★</button>
                    @endfor
                    <span style="font-size:13px; color:var(--fg-3); margin-left:8px;" x-text="['','Poor','Fair','Good','Great','Excellent'][starPick] || 'Select a star'"></span>
                </div>

                <textarea name="review" rows="3" placeholder="Write a short review (optional)…" style="resize:none; margin-bottom:10px;"></textarea>

                <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 20px;"
                        :disabled="starPick === 0"
                        :class="starPick === 0 ? 'opacity-40 cursor-not-allowed' : ''">
                    Submit Rating
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Employer: Approve or Dispute (submitted) --}}
        @if($isEmployer && $contract->status === 2)
        <div class="card" style="padding:24px;" x-data="{ showDispute: false }">
            <div style="font-size:14px; font-weight:600; color:var(--fg); margin-bottom:16px;">Review Submission</div>
            <div style="display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                <form method="POST" action="{{ route('user.contracts.approve', $contract->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 20px;"
                            onclick="return confirm('Approve and release {{ formatCoins($contract->amount) }} to the worker?')">
                        <i data-lucide="check-circle" style="width:14px; height:14px;"></i> Approve & Pay
                    </button>
                </form>
                <button @click="showDispute = !showDispute"
                        style="padding:10px 20px; font-size:13px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:transparent; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:6px; transition:background .14s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="alert-triangle" style="width:14px; height:14px;"></i> Raise Dispute
                </button>
            </div>
            <div x-show="showDispute" x-transition>
                <form method="POST" action="{{ route('user.contracts.dispute', $contract->id) }}"
                      style="display:flex; flex-direction:column; gap:10px;">
                    @csrf
                    <textarea name="employer_note" rows="3" required
                              placeholder="Describe the issue with the submission…"
                              style="{{ $errors->has('employer_note') ? 'border-color:#EF4444;' : '' }}"></textarea>
                    @error('employer_note')<p style="font-size:11.5px; color:#EF4444;">{{ $message }}</p>@enderror
                    <div>
                        <button type="submit" class="btn btn-danger" style="font-size:13px; padding:9px 20px;">Confirm Dispute</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Sidebar --}}
    <div style="display:flex; flex-direction:column; gap:14px;">

        {{-- Parties --}}
        <div class="card" style="padding:20px;">
            <div class="label" style="margin-bottom:12px;">Parties</div>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <div style="font-size:11.5px; color:var(--fg-4); margin-bottom:4px;">Employer</div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <span style="font-size:13.5px; color:var(--fg); font-weight:500;">{{ $contract->employer->fullname ?: $contract->employer->username }}</span>
                        @if($isEmployer)
                            <span style="font-size:11.5px; color:var(--accent);">(you)</span>
                        @else
                            <a href="{{ route('user.public-profile', $contract->employer->username) }}" style="font-size:12px; color:var(--fg-3); text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg-3)'">View →</a>
                        @endif
                    </div>
                </div>
                <div style="border-top:1px solid var(--border); padding-top:12px;">
                    <div style="font-size:11.5px; color:var(--fg-4); margin-bottom:4px;">Worker</div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <span style="font-size:13.5px; color:var(--fg); font-weight:500;">{{ $contract->worker->fullname ?: $contract->worker->username }}</span>
                        @if(!$isEmployer)
                            <span style="font-size:11.5px; color:var(--accent);">(you)</span>
                        @else
                            <a href="{{ route('user.public-profile', $contract->worker->username) }}" style="font-size:12px; color:var(--fg-3); text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg-3)'">View →</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial & Timeline --}}
        <div class="card" style="padding:20px;">
            <div class="label" style="margin-bottom:12px;">Details</div>
            <div style="display:flex; flex-direction:column; gap:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Contract Value</span>
                    <span class="mono" style="font-size:13px; font-weight:600; color:var(--accent);">{{ formatCoins($contract->amount) }}</span>
                </div>
                @if($contract->status === 3 && $contract->commission_amount > 0)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Platform Fee</span>
                    <span class="mono" style="font-size:13px; color:#EF4444;">− {{ formatCoins($contract->commission_amount) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; font-weight:500; color:var(--fg-2);">Worker Received</span>
                    <span class="mono" style="font-size:13px; font-weight:600; color:#22C55E;">{{ formatCoins($contract->worker_payout) }}</span>
                </div>
                @elseif($contract->status < 3)
                @php
                    $rate         = gs()->contract_commission ?? 5;
                    $previewFee   = round((float)$contract->amount * $rate / 100, 8);
                    $previewPayout= round((float)$contract->amount - $previewFee, 8);
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:11.5px; color:var(--fg-4);">Est. fee ({{ $rate }}%)</span>
                    <span class="mono" style="font-size:12px; color:var(--fg-4);">− {{ formatCoins($previewFee) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:11.5px; color:var(--fg-4);">Est. worker payout</span>
                    <span class="mono" style="font-size:12px; color:var(--fg-4);">{{ formatCoins($previewPayout) }}</span>
                </div>
                @endif
                @if($contract->deadline_at)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Deadline</span>
                    <span style="font-size:12.5px; color:{{ $contract->isOverdue ? '#EF4444' : 'var(--fg-2)' }};">
                        {{ $contract->deadline_at->format('M d, Y') }}
                        @if($contract->isOverdue) <span style="font-size:11px;">(overdue)</span> @endif
                    </span>
                </div>
                @endif
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Sent</span>
                    <span style="font-size:12.5px; color:var(--fg-2);">{{ $contract->created_at->format('M d, Y') }}</span>
                </div>
                @if($contract->accepted_at)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Accepted</span>
                    <span style="font-size:12.5px; color:var(--fg-2);">{{ $contract->accepted_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($contract->submitted_at)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Submitted</span>
                    <span style="font-size:12.5px; color:var(--fg-2);">{{ $contract->submitted_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($contract->completed_at)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0;">
                    <span style="font-size:12.5px; color:var(--fg-3);">Completed</span>
                    <span style="font-size:12.5px; color:#22C55E;">{{ $contract->completed_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Cancel (employer, before submission) --}}
        @if($isEmployer && in_array($contract->status, [0, 1]))
        <form method="POST" action="{{ route('user.contracts.cancel', $contract->id) }}">
            @csrf
            <button type="submit"
                    style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 16px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:transparent; cursor:pointer; font-size:13px; font-family:inherit; transition:background .14s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'"
                    onclick="return confirm('Cancel this contract?{{ $contract->status === 1 ? ' Coins will be refunded to you.' : '' }}')">
                <i data-lucide="x-circle" style="width:14px; height:14px;"></i> Cancel Contract
            </button>
        </form>
        @endif
    </div>

</div>

<style>
@media (max-width: 900px) { .contract-detail-grid { grid-template-columns: 1fr !important; } }
</style>
@endsection
