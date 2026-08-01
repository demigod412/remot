
<?php $cat = $cat ?? null; ?>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Commission %</label>
        <input type="number" name="commission_percent" step="0.01" min="0" max="100" required
               value="<?php echo e(old('commission_percent', $cat->commission_percent ?? 0)); ?>"
               style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
        <small style="color:var(--fg-3);font-size:11px;">Taken off the worker payout</small>
    </div>

    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">
            Application cost (<?php echo e(coinName()); ?>)
        </label>
        <input type="number" name="application_cost" step="0.01" min="0" required
               value="<?php echo e(old('application_cost', $cat->application_cost ?? 0)); ?>"
               style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
        <small style="color:var(--fg-3);font-size:11px;">Charged when a worker applies</small>
    </div>

    <div>
        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Open to</label>
        <select name="eligible_user_type" required style="width:100%;font-size:13px;">
            <option value="0" <?php if(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 0): echo 'selected'; endif; ?>>Individuals and businesses</option>
            <option value="1" <?php if(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 1): echo 'selected'; endif; ?>>Individuals only</option>
            <option value="2" <?php if(old('eligible_user_type', $cat->eligible_user_type ?? 0) == 2): echo 'selected'; endif; ?>>Businesses only</option>
        </select>
    </div>
</div>

<div style="margin-bottom:12px;">
    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Description</label>
    <textarea name="description" rows="2" maxlength="2000"
              style="width:100%;font-size:13px;font-family:inherit;resize:vertical;"><?php echo e(old('description', $cat->description ?? '')); ?></textarea>
</div>


<details style="margin-bottom:12px;" <?php if(old('result_schema') || ($cat && $cat->hasResultSchema())): ?> open <?php endif; ?>>
    <summary style="cursor:pointer;font-size:12px;color:var(--accent);font-weight:600;">
        Result schema (optional)
        <?php if($cat && $cat->hasResultSchema()): ?>
            <span style="color:#22C55E;font-weight:400;">— configured</span>
        <?php else: ?>
            <span style="color:var(--fg-3);font-weight:400;">— not set, any valid JSON accepted</span>
        <?php endif; ?>
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

        
        <?php
            $schemaExample = '{"type":"object","required":["task_id"],"properties":{"task_id":{"type":"string"}}}';
        ?>

        <textarea name="result_schema" rows="10" spellcheck="false"
                  placeholder="<?php echo e($schemaExample); ?>"
                  style="width:100%;font-size:12px;font-family:ui-monospace,monospace;resize:vertical;"><?php echo e(old('result_schema', $cat?->resultSchemaJson() ?? '')); ?></textarea>

        <label style="display:flex;align-items:center;gap:7px;margin-top:8px;font-size:12px;color:var(--fg-2);">
            <input type="checkbox" name="schema_strict" value="1"
                   <?php if(old('schema_strict', $cat->schema_strict ?? false)): echo 'checked'; endif; ?>>
            Reject keys not listed in the schema
            <span style="color:var(--fg-3);">(leave off while iterating)</span>
        </label>
    </div>
</details>
<?php /**PATH /var/www/resources/views/admin/partials/category-marketplace-fields.blade.php ENDPATH**/ ?>