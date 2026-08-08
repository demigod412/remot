@extends('admin.layouts.app')

@section('title', 'Import Tasks')
@section('page-title', 'Import Tasks')

@section('content')
<div style="max-width:860px;">

    @if(session('error'))
    <div style="padding:13px 16px;border-radius:9px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.28);color:#EF4444;font-size:13px;margin-bottom:16px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Per-row problems. Every row is checked before anything is written, so this
         list is the complete set of things to fix, not just the first failure. --}}
    @if(session('import_errors'))
    <div class="jobstation-card" style="padding:18px;margin-bottom:16px;border-color:rgba(239,68,68,0.28);">
        <div style="font-size:13px;font-weight:600;color:#EF4444;margin-bottom:10px;">
            Nothing was imported. Fix these and upload again:
        </div>
        <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--fg-2);line-height:1.75;max-height:320px;overflow:auto;">
            @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="jobstation-card" style="padding:22px;margin-bottom:16px;">
        <div style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:6px;">Upload a CSV</div>
        <p style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin:0 0 18px;">
            Up to {{ $maxRows }} rows per file. The whole file is validated first and
            imported only if every row is valid, so you never end up with half a batch.
        </p>

        <form method="POST" action="{{ route('admin.works.import.store') }}" enctype="multipart/form-data"
              x-data="{ name: '', sending: false }" @submit="sending = true">
            @csrf

            <div role="button" tabindex="0"
                 @click="$refs.csv.click()"
                 @keydown.enter.prevent="$refs.csv.click()"
                 @dragover.prevent="$el.style.borderColor='var(--accent)'"
                 @dragleave.prevent="$el.style.borderColor='var(--border)'"
                 @drop.prevent="$refs.csv.files = $event.dataTransfer.files; name = $refs.csv.files[0]?.name || ''"
                 style="border:1.5px dashed var(--border);border-radius:10px;padding:26px;text-align:center;cursor:pointer;transition:border-color .15s;">
                <div style="font-size:22px;opacity:.5;margin-bottom:8px;">&#8679;</div>
                <div style="font-size:13.5px;color:var(--fg);font-weight:500;" x-text="name || 'Drop your CSV here, or click to browse'"></div>
                <div style="font-size:12px;color:var(--fg-3);margin-top:5px;">CSV &middot; max 4 MB</div>
            </div>

            <input type="file" name="file" x-ref="csv" accept=".csv,text/csv"
                   @change="name = $event.target.files[0]?.name || ''"
                   style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;">

            @error('file')
            <div style="font-size:12.5px;color:#EF4444;margin-top:8px;">{{ $message }}</div>
            @enderror

            <div style="display:flex;gap:9px;align-items:center;margin-top:18px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" x-bind:disabled="sending"
                        x-bind:style="sending ? 'opacity:.65;cursor:progress;' : ''">
                    <span x-show="!sending">Validate and import</span>
                    <span x-show="sending" x-cloak>Importing…</span>
                </button>
                <a href="{{ route('admin.works.import.template') }}" class="btn">Download template</a>
                <a href="{{ route('admin.works.index') }}" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Bulk JSON. The route and controller existed with no page to reach them from,
         which is the same gap that left the single-task upload field unbuilt. --}}
    <div class="jobstation-card" style="padding:22px;margin-bottom:16px;">
        <div style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:6px;">Or upload many task files at once</div>
        <p style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin:0 0 18px;">
            One task per JSON file, up to 100 at a time. Category, slots and payout below
            apply to the whole batch &mdash; putting them inside each file means editing
            every file to change a price, and a typo prices one task wrong without anyone
            noticing until a worker is paid it. Every file is validated before any task is
            created.
        </p>

        <form method="POST" action="{{ route('admin.works.import.json') }}" enctype="multipart/form-data"
              x-data="{ count: 0, sending: false }" @submit="sending = true">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Category</label>
                    <select name="category_id" required style="width:100%;font-size:13px;">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Worker slots (each)</label>
                    <input type="number" name="worker_slots" value="{{ old('worker_slots', 10) }}" min="1" max="10000" required
                           style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
                </div>
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Payout USD (each)</label>
                    <input type="number" name="payout_usd" value="{{ old('payout_usd') }}" step="0.01" min="0" required
                           placeholder="0.00" style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
                </div>
            </div>

            <input type="file" name="files[]" accept=".json,application/json" multiple required
                   @change="count = $event.target.files.length"
                   style="width:100%;padding:9px;border:1px dashed var(--border);border-radius:8px;background:var(--surface-2);font-size:12.5px;">

            <div x-show="count > 0" x-cloak style="font-size:12px;color:var(--fg-2);margin-top:8px;">
                <span x-text="count"></span> file(s) selected.
            </div>

            @error('files') <div style="font-size:12px;color:#EF4444;margin-top:6px;">{{ $message }}</div> @enderror
            @error('files.*') <div style="font-size:12px;color:#EF4444;margin-top:6px;">{{ $message }}</div> @enderror

            <button type="submit" class="btn btn-primary" style="margin-top:14px;" x-bind:disabled="sending"
                    x-bind:style="sending ? 'opacity:.65;cursor:progress;' : ''">
                <span x-show="!sending">Validate and import</span>
                <span x-show="sending" x-cloak>Importing&hellip;</span>
            </button>
        </form>
    </div>

    {{-- The generated task IDs, mapped back to filenames. That mapping exists nowhere
         else, and an admin who uploaded 40 files needs to know which became which. --}}
    @if(session('import_results'))
    <div class="jobstation-card" style="padding:20px;margin-bottom:16px;">
        <div style="font-size:13px;font-weight:600;color:#22C55E;margin-bottom:10px;">Imported</div>
        <div class="mono" style="font-size:12px;color:var(--fg-2);line-height:1.9;max-height:280px;overflow:auto;">
            @foreach(session('import_results') as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="jobstation-card" style="padding:22px;">
        <div style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:14px;">CSV columns</div>
        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
            <thead>
                <tr style="text-align:left;color:var(--fg-3);">
                    <th style="padding:7px 10px 7px 0;font-weight:500;">Column</th>
                    <th style="padding:7px 10px;font-weight:500;">Required</th>
                    <th style="padding:7px 0;font-weight:500;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $notes = [
                        'title'                     => 'Up to 200 characters.',
                        'description'               => 'At least 20 characters.',
                        'category'                  => 'Category NAME, not id. Must already exist.',
                        'worker_slots'              => 'How many workers can be accepted.',
                        'payout_usd'                => 'USD paid per worker, before commission.',
                        'subcategory'               => 'Name, must belong to the category above.',
                        'avg_minutes'               => 'Estimated minutes to complete.',
                        'requires_kyc'              => '1 or 0.',
                        'display_application_boost' => 'Cosmetic applicant head start. Never affects real slots.',
                        'work_status'               => '1 = active, 0 = holding. Defaults to 1.',
                        'expires_at'                => 'YYYY-MM-DD HH:MM. Leave blank for no expiry.',
                    ];
                @endphp
                @foreach($columns as $col => $req)
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:9px 10px 9px 0;"><code style="font-family:ui-monospace,monospace;color:var(--fg);">{{ $col }}</code></td>
                    <td style="padding:9px 10px;color:{{ $req === 'required' ? '#EF4444' : 'var(--fg-3)' }};">{{ $req === 'required' ? 'yes' : 'no' }}</td>
                    <td style="padding:9px 0;color:var(--fg-3);">{{ $notes[$col] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size:12px;color:var(--fg-3);line-height:1.6;margin:16px 0 0;">
            The application fee and commission come from the category, so they are not
            columns here. Existing categories:
            <strong style="color:var(--fg-2);">{{ $categories->pluck('name')->implode(', ') ?: 'none yet' }}</strong>
        </p>
    </div>
</div>
@endsection
