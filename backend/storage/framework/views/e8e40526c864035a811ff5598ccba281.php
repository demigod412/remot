<?php $__env->startSection('title', 'Submit Proof'); ?>
<?php $__env->startSection('page-title', 'Submit Proof'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-3); margin-bottom:20px;">
    <a href="<?php echo e(route('user.submissions.index')); ?>" style="color:var(--fg-3); text-decoration:none;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">My Submissions</a>
    <i data-lucide="chevron-right" style="width:12px; height:12px;"></i>
    <span style="color:var(--fg-2);">Submit Proof</span>
</div>

<div style="display:flex; flex-direction:column; gap:14px;">

    
    <div class="card" style="padding:18px; display:flex; gap:14px; align-items:flex-start;">
        <?php if($submission->work->cover_image): ?>
        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $submission->work->cover_image)); ?>"
             style="width:60px; height:60px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        <?php endif; ?>
        <div style="flex:1; min-width:0;">
            <div style="font-size:15px; font-weight:600; color:var(--fg);"><?php echo e($submission->work->title); ?></div>
            <div style="font-size:12px; color:var(--fg-3); margin-top:3px;"><?php echo e($submission->work->category->name ?? ''); ?></div>
            <div style="font-size:12px; color:#E6C400; margin-top:4px; display:flex; align-items:center; gap:4px;">
                <i data-lucide="coins" style="width:12px; height:12px;"></i>
                You'll earn <?php echo e(formatCoins($submission->work->coins_per_worker)); ?> upon approval
            </div>
            <?php if($submission->deadline_at): ?>
            <div style="font-size:11px; margin-top:4px; display:flex; align-items:center; gap:4px;
                color:<?php echo e($submission->deadline_at->isPast() ? 'var(--danger)' : ($submission->deadline_at->diffInHours(now()) <= 6 ? 'var(--warn)' : 'var(--fg-3)')); ?>;">
                <i data-lucide="clock" style="width:11px; height:11px;"></i>
                <?php if($submission->deadline_at->isPast()): ?>
                    Auto-approved (deadline passed)
                <?php else: ?>
                    Auto-approves in
                    <span id="proof-deadline"
                          data-expires="<?php echo e($submission->deadline_at->timestamp); ?>"
                          style="font-weight:600;font-family:var(--font-mono);"><?php echo e($submission->deadline_at->diffForHumans()); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="card" style="padding:18px;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:10px;">Work Instructions</div>
        <div style="font-size:13px; color:var(--fg-2); line-height:1.65;">
            <?php echo richBody($submission->work->description); ?>

        </div>
    </div>

    
    <div class="card" style="padding:18px;"
         x-data="{
             files: [],
             dragging: false,
             addFiles(newFiles) {
                 const allowed = Array.from(newFiles).filter(f => {
                     const ok = ['image/jpeg','image/png','image/gif','image/webp',
                                 'application/pdf','application/zip','text/plain'].includes(f.type)
                                 || f.name.endsWith('.zip') || f.name.endsWith('.txt');
                     return ok && f.size <= 5 * 1024 * 1024;
                 });
                 const combined = [...this.files, ...allowed].slice(0, 5);
                 this.files = combined;
                 this.syncInput();
             },
             removeFile(index) {
                 this.files.splice(index, 1);
                 this.syncInput();
             },
             syncInput() {
                 const dt = new DataTransfer();
                 this.files.forEach(f => dt.items.add(f));
                 document.getElementById('proof-files-input').files = dt.files;
             },
             isImage(file) { return file.type.startsWith('image/'); },
             preview(file) { return URL.createObjectURL(file); },
             formatSize(bytes) {
                 if (bytes < 1024) return bytes + ' B';
                 if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
                 return (bytes/(1024*1024)).toFixed(1) + ' MB';
             }
         }">

        <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:16px;">Submit Your Proof</div>

        <form method="POST" action="<?php echo e(route('user.submissions.proof.submit', $submission->id)); ?>"
              enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
            <?php echo csrf_field(); ?>

            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                    Proof Note <span style="color:var(--danger);">*</span>
                </div>
                <textarea name="proof_note" rows="5"
                          style="<?php echo e($errors->has('proof_note') ? 'border-color:var(--danger);' : ''); ?> resize:vertical;"
                          placeholder="Describe what you did and how you completed the task. Include any relevant links, usernames, etc."
                          required><?php echo e(old('proof_note')); ?></textarea>
                <div style="font-size:11px; color:var(--fg-4); margin-top:4px;">Minimum 20 characters</div>
                <?php $__errorArgs = ['proof_note'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px; color:var(--danger); margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                    Proof Files <span style="color:var(--fg-4); font-weight:400;">(optional, up to 5)</span>
                </div>

                <div style="border-radius:10px; border:2px dashed; padding:20px; text-align:center; cursor:pointer; transition:all .2s;"
                     :style="dragging ? 'border-color:var(--accent); background:var(--accent-soft);' : 'border-color:var(--border-strong); background:var(--surface-2);'"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
                     @click="$refs.fileInput.click()">

                    <input type="file" x-ref="fileInput" id="proof-files-input"
                           name="proof_files[]" multiple
                           accept="image/*,application/pdf,.zip,.txt"
                           style="display:none;"
                           @change="addFiles($event.target.files); $event.target.value=''">

                    <template x-if="files.length === 0">
                        <div style="pointer-events:none; user-select:none;">
                            <i data-lucide="upload-cloud" style="width:32px; height:32px; color:var(--fg-4); margin:0 auto 8px; display:block;"
                               :style="dragging ? 'color:var(--accent)' : ''"></i>
                            <div style="font-size:13px; color:var(--fg-3);" x-text="dragging ? 'Drop files here' : 'Drag & drop files, or click to browse'"></div>
                            <div style="font-size:11px; color:var(--fg-4); margin-top:4px;">Screenshots, PDFs, ZIPs — max 5MB each, up to 5 files</div>
                        </div>
                    </template>

                    <template x-if="files.length > 0">
                        <div style="pointer-events:none; user-select:none; font-size:13px; color:var(--fg-3);">
                            <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline; margin-right:4px;"></i>
                            Click or drop to add more
                            (<span x-text="5 - files.length"></span> slots left)
                        </div>
                    </template>
                </div>

                
                <div x-show="files.length > 0" style="margin-top:10px; display:flex; flex-direction:column; gap:6px;">
                    <template x-for="(file, index) in files" :key="index">
                        <div style="display:flex; align-items:center; gap:10px; padding:10px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border);">
                            <div style="width:40px; height:40px; border-radius:8px; overflow:hidden; flex-shrink:0; background:var(--surface-3); display:flex; align-items:center; justify-content:center;">
                                <template x-if="isImage(file)">
                                    <img :src="preview(file)" style="width:100%; height:100%; object-fit:cover;">
                                </template>
                                <template x-if="!isImage(file)">
                                    <i data-lucide="file" style="width:18px; height:18px; color:var(--fg-4);"></i>
                                </template>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13px; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="file.name"></div>
                                <div style="font-size:11px; color:var(--fg-3);" x-text="formatSize(file.size)"></div>
                            </div>
                            <button type="button" @click.stop="removeFile(index)"
                                    style="padding:4px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:var(--fg-4); display:flex; align-items:center;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.color='var(--danger)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'"
                                    title="Remove">
                                <i data-lucide="x" style="width:14px; height:14px;"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <?php $__errorArgs = ['proof_files'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px; color:var(--danger); margin-top:6px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['proof_files.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px; color:var(--danger); margin-top:6px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div style="display:flex; gap:10px; padding-top:6px;">
                <button type="submit" class="btn btn-primary" style="padding:9px 20px;">
                    <i data-lucide="send" style="width:14px; height:14px;"></i> Submit Proof
                </button>
                <a href="<?php echo e(route('user.submissions.index')); ?>" class="btn" style="padding:9px 20px; text-decoration:none;">Cancel</a>
            </div>
        </form>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    var el = document.getElementById('proof-deadline');
    if (!el) return;
    var expires = parseInt(el.dataset.expires) * 1000;
    function pad(n) { return n < 10 ? '0' + n : n; }
    function update() {
        var diff = Math.max(0, Math.floor((expires - Date.now()) / 1000));
        if (diff === 0) { el.textContent = 'now'; return; }
        var d = Math.floor(diff / 86400), h = Math.floor((diff % 86400) / 3600);
        var m = Math.floor((diff % 3600) / 60), s = diff % 60;
        el.textContent = d > 0
            ? d + 'd ' + pad(h) + 'h ' + pad(m) + 'm'
            : pad(h) + ':' + pad(m) + ':' + pad(s);
        setTimeout(update, 1000);
    }
    update();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/submissions/proof.blade.php ENDPATH**/ ?>