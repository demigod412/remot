{{--
    Marketplace fields for the admin category form.

    Include inside the create and edit forms in admin/categories/index.blade.php:
        @include('admin.partials.category-marketplace-fields', ['cat' => $cat])
    or for the create form, with no $cat:
        @include('admin.partials.category-marketplace-fields')

    Commission, application cost and eligibility are set here and inherited by
    every task in the category. They are intentionally not editable per task.
--}}
@php $cat = $cat ?? null; @endphp

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Commission %</label>
        <input type="number" name="commission_percent" step="0.01" min="0" max="100" required
               value="{{ old('commission_percent', $cat->commission_percent ?? 0) }}"
               style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
        <small style="color:var(--fg-3);font-size:11px;">Taken off the worker payout</small>
    </div>

    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">
            Application cost ({{ coinName() }})
        </label>
        <input type="number" name="application_cost" step="0.01" min="0" required
               value="{{ old('application_cost', $cat->application_cost ?? 0) }}"
               style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
        <small style="color:var(--fg-3);font-size:11px;">Charged when a worker applies</small>
    </div>

    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">
            Daily application limit
        </label>
        <input type="number" name="daily_application_limit" step="1" min="0" max="1000"
               value="{{ old('daily_application_limit', $cat->daily_application_limit ?? 0) }}"
               style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
        <small style="color:var(--fg-3);font-size:11px;">
            Most tasks in this category one worker may apply to per day. 0 = unlimited.
        </small>
    </div>

    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Open to</label>
        <select name="eligible_user_type" required style="width:100%;font-size:13px;">
            <option value="0" @selected(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 0)>Individuals and businesses</option>
            <option value="1" @selected(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 1)>Individuals only</option>
            <option value="2" @selected(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 2)>Businesses only</option>
        </select>
    </div>
</div>

<div style="margin-bottom:12px;">
    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Description</label>
    <textarea name="description" rows="2" maxlength="2000"
              style="width:100%;font-size:13px;font-family:inherit;resize:vertical;">{{ old('description', $cat->description ?? '') }}</textarea>
</div>

{{-- Result schema. Optional: leave blank until the JSON format is settled. --}}
<details style="margin-bottom:12px;" @if(old('result_schema') || ($cat && $cat->hasResultSchema())) open @endif>
    <summary style="cursor:pointer;font-size:12px;color:var(--accent);font-weight:600;">
        Result schema (optional)
        @if($cat && $cat->hasResultSchema())
            <span style="color:#22C55E;font-weight:400;">— configured</span>
        @else
            <span style="color:var(--fg-3);font-weight:400;">— not set, any valid JSON accepted</span>
        @endif
    </summary>

    <div style="margin-top:10px;">
        <div style="font-size:11.5px;color:var(--fg-3);line-height:1.6;margin-bottom:8px;">
            Paste a schema to have malformed results rejected at upload, before they reach
            your review queue. Leave blank and any valid JSON is accepted.
            Supported keys: <code>type</code>, <code>required</code>, <code>properties</code>,
            <code>items</code>, <code>enum</code>, <code>min</code>, <code>max</code>,
            <code>min_length</code>, <code>max_length</code>, <code>min_items</code>,
            <code>max_items</code>, <code>pattern</code>, <code>nullable</code>.
        </div>

        {{-- Example JSON is assigned in PHP below rather than written inline.
             A raw JSON snippet ending in three closing braces terminates a Blade
             echo early and produces a parse error. Note: never name Blade
             directives inside a comment either, since raw PHP blocks are
             extracted before comments are stripped. --}}
        @php
            $schemaExample = '{"type":"object","required":["task_id"],"properties":{"task_id":{"type":"string"}}}';
        @endphp

        <textarea name="result_schema" rows="10" spellcheck="false"
                  placeholder="{{ $schemaExample }}"
                  style="width:100%;font-size:12px;font-family:ui-monospace,monospace;resize:vertical;">{{ old('result_schema', $cat?->resultSchemaJson() ?? '') }}</textarea>

        <label style="display:flex;align-items:center;gap:7px;margin-top:8px;font-size:12px;color:var(--fg-2);">
            <input type="checkbox" name="schema_strict" value="1"
                   @checked(old('schema_strict', $cat->schema_strict ?? false))>
            Reject keys not listed in the schema
            <span style="color:var(--fg-3);">(leave off while iterating)</span>
        </label>
    </div>
</details>
