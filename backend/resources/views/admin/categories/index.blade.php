@extends('admin.layouts.app')
@section('title', 'Categories')
@section('page-title', 'Work Categories')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div style="font-size:13px;color:var(--fg-3);">{{ $categories->count() }} categories</div>
    <button x-data @click="$dispatch('open-add-category')" class="btn-primary" style="padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Category
    </button>
</div>

<div class="jobstation-card" style="overflow:hidden;margin-bottom:24px;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Category</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Icon</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Subs</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Works</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
            <tr style="border-bottom:1px solid var(--border);" x-data="{ editOpen: false }">
                <td style="padding:13px 20px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        @if($cat->icon)
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="{{ $cat->icon }}" style="width:16px;height:16px;color:var(--accent);"></i>
                        </div>
                        @endif
                        <span style="font-weight:500;color:var(--fg);">{{ $cat->name }}</span>
                    </div>
                </td>
                <td style="padding:13px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-4);">{{ $cat->icon ?? '—' }}</td>
                <td style="padding:13px 20px;text-align:center;color:var(--fg-2);">{{ $cat->subcategories_count }}</td>
                <td style="padding:13px 20px;text-align:center;color:var(--fg-2);">{{ $cat->works_count }}</td>
                <td style="padding:13px 20px;text-align:center;">
                    @if($cat->status)
                        <span class="badge-success" style="font-size:11px;">Active</span>
                    @else
                        <span class="badge-default" style="font-size:11px;">Inactive</span>
                    @endif
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                        <button @click="editOpen = !editOpen"
                                style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                title="Edit"
                                onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                            <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.categories.toggle', $cat->id) }}">
                            @csrf
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                    title="{{ $cat->status ? 'Deactivate' : 'Activate' }}"
                                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                <i data-lucide="{{ $cat->status ? 'eye-off' : 'eye' }}" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        @if(!$cat->works_count)
                        <form method="POST" action="{{ route('admin.categories.delete', $cat->id) }}"
                              onsubmit="return confirm('Delete {{ addslashes($cat->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;"
                                    title="Delete"
                                    onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                                <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>

            {{-- Inline edit row --}}
            <tr x-show="editOpen" x-cloak x-transition style="background:var(--surface-2);">
                <td colspan="6" style="padding:16px 20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                        {{-- Edit category --}}
                        <div>
                            <div style="font-size:11px;color:var(--fg-3);font-weight:600;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Edit Category</div>
                            <form method="POST" action="{{ route('admin.categories.update', $cat->id) }}" style="display:flex;gap:8px;">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $cat->name }}" placeholder="Category name" style="flex:1;font-size:13px;" required>
                                <input type="text" name="icon" value="{{ $cat->icon }}" placeholder="lucide icon" style="width:120px;font-size:13px;font-family:ui-monospace,monospace;">
                                <button type="submit" class="btn-primary" style="padding:8px 14px;font-size:13px;flex-shrink:0;">Save</button>
                            </form>
                        </div>

                        {{-- Subcategories --}}
                        <div>
                            <div style="font-size:11px;color:var(--fg-3);font-weight:600;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Subcategories</div>
                            <div style="max-height:120px;overflow-y:auto;margin-bottom:10px;display:flex;flex-direction:column;gap:4px;">
                                @forelse($cat->subcategories as $sub)
                                <div style="display:flex;align-items:center;gap:8px;"
                                     onmouseover="this.querySelector('.sub-del').style.opacity='1'"
                                     onmouseout="this.querySelector('.sub-del').style.opacity='0'">
                                    <span style="font-size:13px;color:var(--fg-2);flex:1;">{{ $sub->name }}</span>
                                    <form method="POST" action="{{ route('admin.categories.subcategories.delete', $sub->id) }}"
                                          onsubmit="return confirm('Delete subcategory?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="sub-del"
                                                style="padding:3px;border-radius:5px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;opacity:0;transition:opacity .14s;"
                                                onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                                onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                            <i data-lucide="x" style="width:13px;height:13px;"></i>
                                        </button>
                                    </form>
                                </div>
                                @empty
                                <div style="font-size:12px;color:var(--fg-4);">No subcategories yet.</div>
                                @endforelse
                            </div>
                            <form method="POST" action="{{ route('admin.categories.subcategories.store', $cat->id) }}" style="display:flex;gap:8px;">
                                @csrf
                                <input type="text" name="name" placeholder="New subcategory…" style="flex:1;font-size:13px;" required>
                                <button type="submit" class="btn" style="padding:8px 12px;font-size:13px;flex-shrink:0;">Add</button>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="tag" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No categories yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Add Category Modal --}}
<div x-data="{ open: false }" @open-add-category.window="open = true">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);" x-transition>
        <div @click.outside="open = false"
             class="jobstation-card" style="width:100%;max-width:420px;padding:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-weight:600;font-size:15px;color:var(--fg);">Add Category</h3>
                <button @click="open = false"
                        style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="x" style="width:16px;height:16px;display:block;"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Social Media" required>
                        @error('name') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">
                            Icon <span style="font-size:12px;color:var(--fg-3);">(Lucide icon name)</span>
                        </label>
                        <input type="text" name="icon" value="{{ old('icon') }}" placeholder="share-2, globe, star…" style="font-family:ui-monospace,monospace;">
                    </div>
                    <div style="display:flex;gap:10px;padding-top:4px;">
                        <button type="submit" class="btn-primary" style="flex:1;padding:9px;font-size:13px;">Create Category</button>
                        <button type="button" @click="open = false" class="btn" style="padding:9px 16px;font-size:13px;">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-add-category')));</script>
@endif

@endsection
