@extends('admin.layouts.app')
@section('title', 'Coin Packages')
@section('page-title', 'Coin Packages')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div style="font-size:13px;color:var(--fg-3);">{{ $packages->count() }} packages configured</div>
    <button x-data @click="$dispatch('open-add-pkg')" class="btn-primary" style="padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Package
    </button>
</div>

{{-- Packages Grid --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px;">
    @forelse($packages as $pkg)
    <div class="jobstation-card" style="padding:20px;position:relative;" x-data="{ editOpen: false }">
        @if($pkg->is_popular)
        <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);">
            <span style="background:var(--accent);color:#fff;font-size:11px;font-weight:700;padding:2px 12px;border-radius:99px;letter-spacing:0.05em;">POPULAR</span>
        </div>
        @endif
        @if($pkg->badge_label)
        <div style="position:absolute;top:12px;right:12px;">
            <span class="badge-warning" style="font-size:11px;">{{ $pkg->badge_label }}</span>
        </div>
        @endif

        <div style="text-align:center;margin-bottom:16px;padding-top:{{ $pkg->is_popular ? '8px' : '0' }};">
            <div style="font-size:15px;font-weight:700;color:var(--fg);margin-bottom:4px;">{{ $pkg->name }}</div>
            <div style="font-size:30px;font-weight:800;color:#F5D547;letter-spacing:-1px;font-family:ui-monospace,monospace;margin-bottom:2px;">
                {{ number_format($pkg->total_coins) }}
                <span style="font-size:14px;font-weight:600;color:var(--fg-3);">{{ coinSymbol() }}</span>
            </div>
            @if($pkg->bonus_coins > 0)
            <div style="font-size:12px;color:#22C55E;margin-bottom:3px;">+{{ number_format($pkg->bonus_coins) }} bonus</div>
            @endif
            <div style="font-size:20px;font-weight:700;color:var(--fg);">{{ number_format($pkg->price, 2) }} {{ $pkg->currency }}</div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid var(--border);">
            <div>
                @if($pkg->status)
                    <span class="badge-success" style="font-size:11px;">Active</span>
                @else
                    <span class="badge-default" style="font-size:11px;">Inactive</span>
                @endif
            </div>
            <div style="display:flex;gap:2px;">
                <button @click="editOpen = !editOpen"
                        style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                        title="Edit"
                        onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                    <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                </button>
                <form method="POST" action="{{ route('admin.coin-packages.toggle', $pkg->id) }}">
                    @csrf
                    <button type="submit"
                            style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                            title="{{ $pkg->status ? 'Deactivate' : 'Activate' }}"
                            onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                            onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                        <i data-lucide="{{ $pkg->status ? 'eye-off' : 'eye' }}" style="width:15px;height:15px;"></i>
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.coin-packages.delete', $pkg->id) }}"
                      onsubmit="return confirm('Delete this package?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;"
                            title="Delete"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                            onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Inline Edit --}}
        <div x-show="editOpen" x-cloak x-transition style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
            <form method="POST" action="{{ route('admin.coin-packages.update', $pkg->id) }}">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Name</label>
                        <input type="text" name="name" value="{{ $pkg->name }}" style="font-size:13px;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Price</label>
                        <input type="number" name="price" value="{{ $pkg->price }}" step="0.01" min="0.01" style="font-size:13px;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Coins</label>
                        <input type="number" name="coins" value="{{ $pkg->coins }}" min="1" style="font-size:13px;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Bonus Coins</label>
                        <input type="number" name="bonus_coins" value="{{ $pkg->bonus_coins }}" min="0" style="font-size:13px;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Currency</label>
                        <input type="text" name="currency" value="{{ $pkg->currency }}" style="font-size:13px;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Badge Label</label>
                        <input type="text" name="badge_label" value="{{ $pkg->badge_label }}" placeholder="BEST VALUE" style="font-size:13px;">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--fg-2);cursor:pointer;margin-bottom:10px;">
                    <input type="checkbox" name="is_popular" value="1" {{ $pkg->is_popular ? 'checked' : '' }}>
                    Mark as Popular
                </label>
                <button type="submit" class="btn-primary" style="width:100%;padding:8px;font-size:13px;">Save Changes</button>
            </form>
        </div>
    </div>
    @empty
    <div class="jobstation-card" style="padding:56px;text-align:center;color:var(--fg-3);grid-column:1/-1;">
        <i data-lucide="package" style="width:36px;height:36px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
        <div style="font-size:14px;">No coin packages yet.</div>
    </div>
    @endforelse
</div>

{{-- Add Package Modal --}}
<div x-data="{ open: false }" @open-add-pkg.window="open = true">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);" x-transition>
        <div @click.outside="open = false"
             class="jobstation-card" style="width:100%;max-width:440px;padding:24px;max-height:90vh;overflow-y:auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-weight:600;font-size:15px;color:var(--fg);">Add Coin Package</h3>
                <button @click="open = false"
                        style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="x" style="width:16px;height:16px;display:block;"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.coin-packages.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Starter Pack" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Currency <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="currency" value="{{ old('currency','USD') }}" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Coins <span style="color:#EF4444;">*</span></label>
                        <input type="number" name="coins" value="{{ old('coins') }}" min="1" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Price <span style="color:#EF4444;">*</span></label>
                        <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0.01" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Bonus Coins</label>
                        <input type="number" name="bonus_coins" value="{{ old('bonus_coins',0) }}" min="0">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Badge Label</label>
                        <input type="text" name="badge_label" value="{{ old('badge_label') }}" placeholder="BEST VALUE">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-2);cursor:pointer;margin-bottom:16px;">
                    <input type="checkbox" name="is_popular" value="1" {{ old('is_popular') ? 'checked' : '' }}>
                    Mark as Popular
                </label>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn-primary" style="flex:1;padding:9px;font-size:13px;">Create Package</button>
                    <button type="button" @click="open = false" class="btn" style="padding:9px 16px;font-size:13px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-add-pkg')));</script>
@endif

@endsection
