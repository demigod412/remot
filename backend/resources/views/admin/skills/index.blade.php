@extends('admin.layouts.app')
@section('title', 'Skills')
@section('page-title', 'Skills Management')

@section('content')

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- Left: Skills Table --}}
    <div>
        {{-- Filter --}}
        <div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
            <form method="GET" action="{{ route('admin.skills.index') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <div style="flex:1;min-width:160px;">
                    <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Search</label>
                    <div style="position:relative;">
                        <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Skill name…" style="padding-left:32px;">
                    </div>
                </div>
                <div style="width:176px;">
                    <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Filter</button>
            </form>
        </div>

        <div class="jobstation-card" style="overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Skill</th>
                        <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Category</th>
                        <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Users</th>
                        <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                        <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skills as $skill)
                    <tr style="border-bottom:1px solid var(--border);" x-data="{ editing: false }">
                        <td style="padding:11px 20px;">
                            <div x-show="!editing">
                                <div style="font-weight:500;color:var(--fg);">{{ $skill->name }}</div>
                                <div style="font-size:11.5px;color:var(--fg-4);margin-top:1px;">{{ $skill->slug }}</div>
                            </div>
                            <div x-show="editing" x-cloak>
                                <form method="POST" action="{{ route('admin.skills.update', $skill->id) }}"
                                      style="display:flex;gap:8px;align-items:center;">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $skill->name }}"
                                           style="font-size:13px;flex:1;">
                                    <select name="category_id" style="font-size:13px;width:140px;">
                                        <option value="">No category</option>
                                        @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected($skill->category_id == $cat->id)>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                            style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:#22C55E;display:flex;align-items:center;"
                                            onmouseover="this.style.background='rgba(34,197,94,0.1)'"
                                            onmouseout="this.style.background='transparent'">
                                        <i data-lucide="check" style="width:15px;height:15px;"></i>
                                    </button>
                                    <button type="button" @click="editing = false"
                                            style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                            onmouseover="this.style.background='var(--surface-2)'"
                                            onmouseout="this.style.background='transparent'">
                                        <i data-lucide="x" style="width:15px;height:15px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td style="padding:11px 20px;font-size:12.5px;color:var(--fg-3);">{{ $skill->category->name ?? '—' }}</td>
                        <td style="padding:11px 20px;font-size:12.5px;color:var(--fg-3);">{{ $skill->users()->count() }}</td>
                        <td style="padding:11px 20px;">
                            <form method="POST" action="{{ route('admin.skills.toggle', $skill->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit"
                                        style="font-size:12px;background:none;border:none;cursor:pointer;font-family:inherit;padding:2px 8px;border-radius:99px;{{ $skill->status ? 'background:rgba(34,197,94,0.1);color:#22C55E;' : 'background:var(--surface-2);color:var(--fg-3);' }}">
                                    {{ $skill->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td style="padding:11px 20px;">
                            <div style="display:flex;gap:4px;">
                                <button @click="editing = true"
                                        style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                        onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                    <i data-lucide="edit" style="width:15px;height:15px;"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.skills.delete', $skill->id) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;"
                                            onclick="return confirm('Delete this skill?')"
                                            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                            onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:48px;text-align:center;color:var(--fg-3);">No skills yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div style="padding:12px 18px;border-top:1px solid var(--border);">{{ $skills->links() }}</div>
        </div>
    </div>

    {{-- Right: Add Skill + Boost Pricing --}}
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="jobstation-card" style="padding:20px;">
            <div style="font-weight:600;font-size:14px;color:var(--fg);margin-bottom:16px;">Add Skill</div>
            <form method="POST" action="{{ route('admin.skills.store') }}" style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Skill Name <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Logo Design">
                    @error('name')<div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Category</label>
                    <select name="category_id">
                        <option value="">No category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding:9px;font-size:13px;font-weight:500;">Add Skill</button>
            </form>
        </div>

        <div class="jobstation-card" style="padding:20px;">
            <div style="font-weight:600;font-size:14px;color:var(--fg);margin-bottom:14px;">Boost Pricing</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                    <span style="color:var(--fg-3);">Work boost cost</span>
                    <span style="color:var(--fg);font-weight:500;">{{ formatCoins(gs()->boost_cost_work) }} / {{ gs()->boost_days_work }}d</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                    <span style="color:var(--fg-3);">Job boost cost</span>
                    <span style="color:var(--fg);font-weight:500;">{{ formatCoins(gs()->boost_cost_job) }} / {{ gs()->boost_days_job }}d</span>
                </div>
            </div>
            <a href="{{ route('admin.settings.general') }}"
               style="display:block;margin-top:12px;font-size:12.5px;color:var(--accent);text-decoration:none;">
                Change in Settings →
            </a>
        </div>
    </div>

</div>
@endsection
