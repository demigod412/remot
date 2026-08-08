<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Annotation Console</title>
{{--
    Server bridge. MUST sit inside <head>, not before <!DOCTYPE html>.

    It was above the doctype originally, which is invalid HTML: the parser treats
    anything before the doctype as stray content, puts the document in quirks mode,
    and may relocate or drop the node entirely. window.REMOTOX therefore never got
    set, the console read REMOTOX as null, and fell straight back to offline mode —
    which is why the uploaded questions never appeared, the annotator ID was a
    locally generated one, and finishing downloaded a file instead of submitting.

    JSON_HEX_TAG and friends are deliberate. Without them a task containing the
    characters </script> in a prompt or code block would terminate this script early
    and break the page, and task content is admin-supplied free text.
--}}
<script>
  window.TASK_DATA = {!! json_encode($taskData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};

  window.REMOTOX = {
      saveUrl:   {!! json_encode(route('user.annotate.save', $submission->annotate_code), JSON_HEX_TAG) !!},
      submitUrl: {!! json_encode(route('user.annotate.submit', $submission->annotate_code), JSON_HEX_TAG) !!},
      csrf:      {!! json_encode(csrf_token(), JSON_HEX_TAG) !!},
      progress:  {!! json_encode($progress, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
      tasksUrl:  {!! json_encode(route('user.tasks.index'), JSON_HEX_TAG) !!},
      code:      {!! json_encode($submission->annotate_code, JSON_HEX_TAG) !!},
      deadline:  {!! json_encode(optional($deadline)->toIso8601String(), JSON_HEX_TAG) !!}
  };
</script>
<style>
  :root{
    --bg:#EDF1EF;
    --surface:#FFFFFF;
    --surface-alt:#F5F7F5;
    --ink:#182420;
    --ink-soft:#5A6D66;
    --ink-faint:#8B9C95;
    --line:#D7DEDA;
    --accent:#2A6F77;
    --accent-ink:#153C41;
    --accent-soft:#DCEBEA;
    --amber:#E2933C;
    --amber-soft:#FBEBD8;
    --flag:#C1443C;
    --flag-soft:#F8E1DE;
    --done:#3F8F5F;
    --done-soft:#E1F0E5;
    --radius:8px;
    --radius-sm:4px;
    --mono: ui-monospace, "SFMono-Regular", "IBM Plex Mono", Menlo, Consolas, "Courier New", monospace;
    --sans: "Segoe UI", ui-sans-serif, system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
    --shadow-card: 0 1px 2px rgba(24,36,32,0.04), 0 8px 24px -12px rgba(24,36,32,0.15);
  }
  *{box-sizing:border-box;}
  html,body{height:100%;}
  body{
    margin:0;
    background:var(--bg);
    color:var(--ink);
    font-family:var(--sans);
    -webkit-font-smoothing:antialiased;
    font-size:15px;
    line-height:1.5;
  }
  ::selection{ background:var(--accent-soft); color:var(--accent-ink); }
  button{ font-family:inherit; }
  textarea, input{ font-family:inherit; }

  .visually-hidden{
    position:absolute; width:1px; height:1px; overflow:hidden;
    clip:rect(0,0,0,0); white-space:nowrap;
  }

  /* ---------- shared type scale ---------- */
  .eyebrow{
    font-family:var(--mono);
    font-size:11px;
    letter-spacing:.14em;
    text-transform:uppercase;
    color:var(--ink-faint);
    font-weight:600;
  }
  h1,h2,h3{ margin:0; font-weight:700; letter-spacing:-0.01em; }

  /* ================= START VIEW ================= */
  #view-start{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:32px 20px;
  }
  .start-card{
    width:100%;
    max-width:640px;
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow-card);
    overflow:hidden;
  }
  .start-head{
    padding:28px 32px 24px;
    border-bottom:1px solid var(--line);
    position:relative;
  }
  .start-head::before{
    content:"";
    position:absolute; left:0; top:0; bottom:0; width:4px;
    background:repeating-linear-gradient(180deg, var(--accent) 0 8px, transparent 8px 14px);
  }
  .start-head h1{ font-size:26px; margin-top:8px; }
  .start-meta{
    display:flex; gap:18px; flex-wrap:wrap; margin-top:14px;
  }
  .meta-chip{
    font-family:var(--mono); font-size:12px; color:var(--ink-soft);
    background:var(--surface-alt); border:1px solid var(--line);
    padding:4px 9px; border-radius:999px;
  }
  .start-body{ padding:26px 32px 32px; }
  .start-body p{ color:var(--ink-soft); margin:0 0 20px; }
  .field-group{ margin-bottom:14px; }
  .field-group label{
    display:block; font-size:12px; font-weight:700; margin-bottom:6px;
    color:var(--ink); letter-spacing:.01em;
  }
  .field-group .req{ color:var(--flag); }
  .field-group input[type="text"]{
    width:100%; padding:10px 12px; border:1px solid var(--line);
    border-radius:var(--radius-sm); font-size:14px; background:var(--surface-alt);
    color:var(--ink); outline:none; transition:border-color .15s, background .15s;
  }
  .field-group input[type="text"]:focus{
    border-color:var(--accent); background:#fff;
  }
  .field-group input[type="text"][readonly]{
    cursor:default; color:var(--ink-soft); background:var(--surface);
    border-style:dashed;
  }
  .field-group input[type="text"][readonly]:focus{
    border-color:var(--line); background:var(--surface);
  }
  .field-group .auto-tag{
    font-family:var(--mono); font-size:10.5px; text-transform:uppercase;
    letter-spacing:.06em; color:var(--ink-faint); margin-left:6px; font-weight:600;
  }
  .btn{
    appearance:none; border:none; cursor:pointer;
    border-radius:var(--radius-sm); font-weight:700; font-size:14px;
    padding:11px 20px; transition:transform .08s ease, box-shadow .15s ease, background .15s;
    display:inline-flex; align-items:center; gap:8px;
  }
  .btn:active{ transform:translateY(1px); }
  .btn-primary{ background:var(--accent); color:#fff; }
  .btn-primary:hover{ background:var(--accent-ink); }
  .btn-primary:disabled{ background:#B9C4C1; cursor:not-allowed; }
  .btn-ghost{ background:transparent; color:var(--ink-soft); border:1px solid var(--line); }
  .btn-ghost:hover{ background:var(--surface-alt); color:var(--ink); }
  .btn-flag{ background:transparent; color:var(--ink-soft); border:1px solid var(--line); }
  .btn-flag.active{ background:var(--flag-soft); color:var(--flag); border-color:var(--flag); }
  .btn-row{ display:flex; gap:10px; align-items:center; margin-top:22px; flex-wrap:wrap; }
  .resume-banner{
    background:var(--amber-soft); border:1px solid #EFCFA0; color:#7A4E13;
    padding:12px 14px; border-radius:var(--radius-sm); font-size:13px; margin-bottom:18px;
    display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
  }
  .resume-banner button{ font-size:12px; padding:6px 12px; }
  .import-row{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    background:var(--surface-alt); border:1px solid var(--line); border-radius:var(--radius-sm);
    padding:10px 14px; margin-bottom:18px; flex-wrap:wrap;
  }
  .import-row .import-label{ font-size:12.5px; color:var(--ink-soft); }
  .import-row input[type="file"]{ font-size:12px; max-width:220px; }
  .err-list{ margin:0 0 18px; padding-left:18px; color:var(--flag); font-size:13.5px; line-height:1.7; }
  .warn-list{ margin:0; padding-left:18px; color:#8A5A16; font-size:13px; line-height:1.7; }
  .warn-note{ color:var(--ink-soft); font-size:13px; margin:14px 0 6px; }
  .warn-banner{ background:var(--amber-soft); border:1px solid #EFCFA0; color:#7A4E13; padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; margin-bottom:16px; }

  /* ================= TASK VIEW ================= */
  #view-task{
    display:none;
    min-height:100vh;
    grid-template-columns:280px 1fr;
  }
  #view-task.active{ display:grid; }

  .rail{
    background:var(--surface);
    border-right:1px solid var(--line);
    padding:24px 20px 20px;
    position:sticky; top:0; height:100vh; overflow-y:auto;
  }
  .rail-title{
    font-size:14px; font-weight:700; margin:2px 0 2px;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }
  .gauge-wrap{
    display:flex; align-items:center; gap:14px; margin:18px 0 20px;
    padding:14px; background:var(--surface-alt); border:1px solid var(--line); border-radius:var(--radius);
  }
  .gauge-num{ font-family:var(--mono); font-size:20px; font-weight:700; }
  .gauge-sub{ font-size:11px; color:var(--ink-faint); font-family:var(--mono); }
  .manifest-label{
    font-family:var(--mono); font-size:11px; letter-spacing:.1em; text-transform:uppercase;
    color:var(--ink-faint); margin:18px 0 8px; display:flex; justify-content:space-between;
  }
  .manifest-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:3px; }
  .manifest-item{
    display:flex; align-items:center; gap:9px; padding:7px 8px; border-radius:var(--radius-sm);
    cursor:pointer; font-size:12.5px; color:var(--ink-soft); border:1px solid transparent;
    transition:background .12s;
  }
  .manifest-item:hover{ background:var(--surface-alt); }
  .manifest-item.is-active{ background:var(--accent-soft); color:var(--accent-ink); font-weight:700; border-color:#BFD9D7; }
  .m-dot{
    width:8px; height:8px; border-radius:2px; flex:none;
    background:var(--line); border:1px solid var(--line);
  }
  .manifest-item.is-done .m-dot{ background:var(--done); border-color:var(--done); }
  .manifest-item.is-flagged .m-dot{ background:var(--flag); border-color:var(--flag); }
  .manifest-item .m-idx{ font-family:var(--mono); color:var(--ink-faint); width:20px; flex:none; }
  .rail-foot{ margin-top:22px; padding-top:16px; border-top:1px solid var(--line); }
  .rail-foot .who{ font-family:var(--mono); font-size:11.5px; color:var(--ink-faint); word-break:break-word; }

  .task-main{ padding:34px 40px 60px; max-width:760px; margin:0 auto; width:100%; }
  .task-topbar{
    display:none; align-items:center; justify-content:space-between;
    padding:14px 16px; background:var(--surface); border-bottom:1px solid var(--line);
  }

  .qcard{
    background:var(--surface); border:1px solid var(--line); border-radius:var(--radius);
    box-shadow:var(--shadow-card); overflow:hidden;
  }
  .qcard-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 22px; border-bottom:1px solid var(--line); background:var(--surface-alt);
  }
  .qtag{
    font-family:var(--mono); font-size:11px; padding:3px 8px; border-radius:999px;
    background:var(--accent-soft); color:var(--accent-ink); font-weight:700; letter-spacing:.03em;
  }
  .qtag.diff-easy{ background:var(--done-soft); color:#245C39; }
  .qtag.diff-medium{ background:var(--amber-soft); color:#8A5A16; }
  .qtag.diff-hard{ background:var(--flag-soft); color:#8C2F28; }
  .qhead-tags{ display:flex; gap:8px; align-items:center; }
  .qpos{ font-family:var(--mono); font-size:12px; color:var(--ink-faint); }

  .qcard-body{ padding:26px 26px 22px; }
  .qprompt{ font-size:18px; font-weight:700; line-height:1.4; margin:0 0 14px; letter-spacing:-0.005em; }
  .qcontext{
    background:var(--surface-alt); border:1px solid var(--line); border-left:3px solid var(--ink-faint);
    padding:14px 16px; border-radius:var(--radius-sm); font-size:14px; color:var(--ink-soft);
    margin-bottom:18px; white-space:pre-wrap;
  }
  .qcode{
    background:#1B221F; color:#DCEFE9; border-radius:var(--radius-sm);
    padding:14px 16px; margin-bottom:18px; overflow-x:auto;
    font-family:var(--mono); font-size:12.5px; line-height:1.6;
  }
  .qcode .lang{ display:block; color:#7FB3A8; font-size:10.5px; letter-spacing:.1em; text-transform:uppercase; margin-bottom:8px; }
  .qimg{ max-width:100%; border-radius:var(--radius-sm); border:1px solid var(--line); margin-bottom:18px; display:block; }

  .input-zone{ margin-top:6px; }

  /* choice list */
  .choice-list{ display:flex; flex-direction:column; gap:8px; }
  .choice-item{
    display:flex; align-items:center; gap:12px; padding:12px 14px;
    border:1px solid var(--line); border-radius:var(--radius-sm); cursor:pointer;
    transition:border-color .12s, background .12s;
  }
  .choice-item:hover{ border-color:var(--accent); background:var(--accent-soft); }
  .choice-item.selected{ border-color:var(--accent); background:var(--accent-soft); }
  .choice-item{ position:relative; }
  .choice-item input[type="radio"], .choice-item input[type="checkbox"]{
    position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
    clip:rect(0,0,0,0); white-space:nowrap; border:0;
  }
  .choice-item:focus-within{ outline:2px solid var(--accent); outline-offset:2px; }
  .choice-key{
    font-family:var(--mono); font-size:11px; color:var(--ink-faint);
    width:20px; height:20px; border:1px solid var(--line); border-radius:4px;
    display:flex; align-items:center; justify-content:center; flex:none; background:var(--surface);
  }
  .choice-item.selected .choice-key{ background:var(--accent); color:#fff; border-color:var(--accent); }
  .choice-mark{ flex:none; width:16px; height:16px; border:1.5px solid var(--ink-faint); border-radius:4px; }
  .choice-item.selected .choice-mark{ background:var(--accent); border-color:var(--accent); }
  .choice-text{ font-size:14px; }

  /* likert / rating */
  .scale-row{ display:flex; gap:8px; margin-top:4px; }
  .scale-btn{
    flex:1; padding:12px 6px; text-align:center; border:1px solid var(--line);
    border-radius:var(--radius-sm); cursor:pointer; font-family:var(--mono); font-weight:700; font-size:14px;
    background:var(--surface); transition:all .12s;
  }
  .scale-btn:hover{ border-color:var(--accent); }
  .scale-btn.selected{ background:var(--accent); color:#fff; border-color:var(--accent); }
  .scale-labels{ display:flex; justify-content:space-between; margin-top:8px; font-size:11.5px; color:var(--ink-faint); }

  .slider-wrap{ padding:10px 4px; }
  .slider-val{ font-family:var(--mono); font-weight:700; font-size:22px; color:var(--accent); text-align:center; margin-bottom:6px; }
  input[type="range"]{ width:100%; accent-color:var(--accent); }

  /* free text */
  textarea.qtext{
    width:100%; min-height:130px; padding:14px; border:1px solid var(--line);
    border-radius:var(--radius-sm); resize:vertical; font-size:14px; background:var(--surface-alt);
    outline:none; transition:border-color .15s, background .15s;
  }
  textarea.qtext:focus{ border-color:var(--accent); background:#fff; }
  .char-count{ text-align:right; font-family:var(--mono); font-size:11px; color:var(--ink-faint); margin-top:6px; }
  .char-count.warn{ color:var(--flag); }

  /* ranking */
  .rank-list{ display:flex; flex-direction:column; gap:6px; }
  .rank-item{
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--surface);
  }
  .rank-num{
    font-family:var(--mono); font-weight:700; font-size:13px; color:#fff; background:var(--accent);
    width:22px; height:22px; border-radius:5px; display:flex; align-items:center; justify-content:center; flex:none;
  }
  .rank-text{ flex:1; font-size:14px; }
  .rank-arrows{ display:flex; gap:4px; }
  .rank-arrows button{
    border:1px solid var(--line); background:var(--surface); border-radius:4px; cursor:pointer;
    width:26px; height:26px; color:var(--ink-soft); font-size:12px;
  }
  .rank-arrows button:hover{ border-color:var(--accent); color:var(--accent); }
  .rank-arrows button:disabled{ opacity:.35; cursor:not-allowed; }

  /* pairwise */
  .pair-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
  .pair-box{ border:1px solid var(--line); border-radius:var(--radius-sm); overflow:hidden; }
  .pair-box-head{
    font-family:var(--mono); font-size:11px; letter-spacing:.08em; text-transform:uppercase;
    padding:8px 12px; background:var(--surface-alt); border-bottom:1px solid var(--line); color:var(--ink-soft);
  }
  .pair-box-body{ padding:12px 14px; font-size:13.5px; white-space:pre-wrap; color:var(--ink); max-height:280px; overflow-y:auto; }
  @media (max-width:640px){ .pair-grid{ grid-template-columns:1fr; } }
  .pair-choice-row{ display:flex; gap:8px; flex-wrap:wrap; }
  .pair-choice-row .choice-item{ flex:1; min-width:120px; justify-content:center; text-align:center; }

  /* span highlight */
  .span-box{
    border:1px solid var(--line); border-radius:var(--radius-sm); padding:14px 16px;
    background:var(--surface-alt); font-size:14.5px; line-height:1.9;
  }
  .span-chip{
    cursor:pointer; padding:1px 3px; border-radius:3px; transition:background .1s;
  }
  .span-chip:hover{ background:var(--accent-soft); }
  .span-chip.selected{ background:var(--amber-soft); box-shadow:inset 0 -2px 0 var(--amber); }
  .span-hint{ font-size:11.5px; color:var(--ink-faint); margin-top:8px; font-family:var(--mono); }

  /* footer controls */
  .qcard-foot{
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;
    padding:16px 26px; border-top:1px solid var(--line); background:var(--surface-alt);
  }
  .confidence-row{ display:flex; align-items:center; gap:8px; }
  .confidence-row .lbl{ font-size:11.5px; color:var(--ink-faint); font-family:var(--mono); }
  .conf-dot{
    width:22px; height:22px; border-radius:50%; border:1.5px solid var(--line); background:var(--surface);
    cursor:pointer; font-family:var(--mono); font-size:10px; color:var(--ink-faint);
    display:flex; align-items:center; justify-content:center;
  }
  .conf-dot.selected{ background:var(--accent); border-color:var(--accent); color:#fff; }

  .validation-msg{
    font-size:12.5px; color:var(--flag); margin-top:10px; display:none; font-weight:600;
  }
  .validation-msg.show{ display:block; }
  @keyframes shake{ 10%,90%{transform:translateX(-1px);} 20%,80%{transform:translateX(2px);} 30%,50%,70%{transform:translateX(-4px);} 40%,60%{transform:translateX(4px);} }
  .shake{ animation:shake .4s; }

  .nav-row{ display:flex; align-items:center; justify-content:space-between; margin-top:20px; gap:10px; }
  .nav-right{ display:flex; gap:10px; }
  .kbd-hint{ font-family:var(--mono); font-size:11px; color:var(--ink-faint); margin-top:14px; text-align:center; }
  .kbd-hint kbd{ background:var(--surface-alt); border:1px solid var(--line); border-radius:3px; padding:1px 5px; }

  /* ================= COMPLETE VIEW ================= */
  #view-complete{
    display:none; min-height:100vh; align-items:center; justify-content:center; padding:32px 20px;
  }
  #view-complete.active{ display:flex; }

  /* The submitting and failed screens share the complete screen's layout. Without
     these rules they exist in the DOM and never appear, which would have left the
     worker on a blank page while the request was in flight. */
  #view-submitting, #view-failed{
    display:none; align-items:center; justify-content:center;
    min-height:70vh; padding:40px 20px;
  }
  #view-submitting.active, #view-failed.active{ display:flex; }

  @keyframes spin{ to{ transform:rotate(360deg); } }
  .complete-card{
    width:100%; max-width:560px; background:var(--surface); border:1px solid var(--line);
    border-radius:var(--radius); box-shadow:var(--shadow-card); padding:36px 34px; text-align:center;
  }
  .complete-icon{
    width:56px; height:56px; border-radius:50%; background:var(--done-soft); color:var(--done);
    display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:26px; font-weight:700;
  }
  .complete-stats{
    display:flex; justify-content:center; gap:26px; margin:22px 0; flex-wrap:wrap;
  }
  .cstat b{ display:block; font-family:var(--mono); font-size:22px; }
  .cstat span{ font-size:11px; color:var(--ink-faint); font-family:var(--mono); text-transform:uppercase; letter-spacing:.06em; }
  .file-pill{
    font-family:var(--mono); font-size:12px; background:var(--surface-alt); border:1px solid var(--line);
    padding:8px 12px; border-radius:var(--radius-sm); margin:18px 0; word-break:break-all; color:var(--ink-soft);
  }

  /* responsive */
  @media (max-width:860px){
    #view-task.active{ grid-template-columns:1fr; }
    .rail{ display:none; }
    .rail.mobile-open{ display:block; position:fixed; z-index:20; width:100%; height:100vh; top:0; left:0; }
    .task-topbar{ display:flex; }
    .task-main{ padding:22px 16px 50px; }
    .pair-grid{ grid-template-columns:1fr; }
  }

  /* reduced motion */
  @media (prefers-reduced-motion: reduce){
    *{ animation:none !important; transition:none !important; }
  }
</style>
</head>
<body>

<!-- ============ START VIEW ============ -->
<div id="view-start">
  <div class="start-card">
    <div class="start-head">
      <div class="eyebrow" id="start-eyebrow">TASK MANIFEST</div>
      <h1 id="start-title">Loading task…</h1>
      <div class="start-meta" id="start-meta"></div>
    </div>
    <div class="start-body">
      <div id="resume-banner" class="resume-banner" style="display:none;">
        <span>You have work in progress on this task. Resume where you left off?</span>
        <div style="display:flex; gap:8px;">
          <button class="btn btn-ghost" id="btn-discard-resume">Start Over</button>
          <button class="btn btn-primary" id="btn-do-resume">Resume</button>
        </div>
      </div>
      <div class="import-row">
        <span class="import-label">Resuming on a different browser or computer? Import your progress file.</span>
        <input type="file" id="import-progress-file" accept="application/json">
      </div>
      <p id="start-instructions">—</p>
      <div id="annotator-fields"></div>
      <div class="btn-row">
        <button class="btn btn-primary" id="btn-begin" disabled>Begin Task</button>
        <span class="eyebrow" id="qcount-label"></span>
      </div>
    </div>
  </div>
</div>

<!-- ============ TASK VIEW ============ -->
<div id="view-task">
  <aside class="rail">
    <div class="eyebrow">Annotation Console</div>
    <div class="rail-title" id="rail-title">—</div>
    <div class="gauge-wrap">
      <svg width="52" height="52" viewBox="0 0 52 52">
        <circle cx="26" cy="26" r="22" fill="none" stroke="#D7DEDA" stroke-width="5"/>
        <circle id="gauge-ring" cx="26" cy="26" r="22" fill="none" stroke="#2A6F77" stroke-width="5"
          stroke-linecap="round" stroke-dasharray="138.2" stroke-dashoffset="138.2"
          transform="rotate(-90 26 26)"/>
      </svg>
      <div>
        <div class="gauge-num" id="gauge-num">0%</div>
        <div class="gauge-sub" id="gauge-sub">0 / 0 answered</div>
      </div>
    </div>
    <div class="manifest-label"><span>Manifest</span><span id="manifest-count"></span></div>
    <ul class="manifest-list" id="manifest-list"></ul>
    <div class="rail-foot">
      <div class="eyebrow">Annotator</div>
      <div class="who" id="rail-who">—</div>
    </div>
  </aside>

  <main class="task-main">
    <div class="task-topbar">
      <div class="gauge-num" id="gauge-num-mobile" style="font-size:14px;">0%</div>
      <button class="btn btn-ghost" id="btn-toggle-manifest" style="font-size:12px; padding:7px 12px;">Question List</button>
    </div>

    <div class="qcard" id="qcard">
      <div class="qcard-head">
        <span class="qpos" id="qpos">Q1 / 1</span>
        <div class="qhead-tags" id="qhead-tags"></div>
      </div>
      <div class="qcard-body">
        <p class="qprompt" id="qprompt">—</p>
        <div id="qcontext-slot"></div>
        <div class="input-zone" id="input-zone"></div>
        <div class="validation-msg" id="validation-msg">Please provide a response before continuing.</div>
      </div>
      <div class="qcard-foot">
        <div class="confidence-row" id="confidence-row">
          <span class="lbl">CONFIDENCE</span>
          <div id="confidence-dots" style="display:flex; gap:5px;"></div>
        </div>
        <button class="btn btn-flag" id="btn-flag">⚑ Flag for review</button>
      </div>
    </div>

    <div class="nav-row">
      <button class="btn btn-ghost" id="btn-prev">← Previous</button>
      <div class="nav-right">
        {{-- Export removed. Progress saves to the server continuously, so a file to
             carry between machines has nothing to do — and a downloaded copy of
             half-finished work invites someone to edit it and import it back. --}}
        <button class="btn btn-ghost" id="btn-save-exit">Save &amp; Exit</button>
        <button class="btn btn-primary" id="btn-next">Next →</button>
      </div>
    </div>
    <div class="kbd-hint" id="kbd-hint">Shortcuts: <kbd>1–9</kbd> select option · <kbd>←</kbd>/<kbd>→</kbd> navigate · <kbd>Enter</kbd> continue</div>
  </main>
</div>

<!-- ============ COMPLETE VIEW ============ -->
<div id="view-complete">
  <div class="complete-card">
    <div class="complete-icon">✓</div>
    <h2>Task Submitted</h2>
    <p style="color:var(--ink-soft); margin:10px 0 0;" id="complete-note">Your answers have been received.</p>
    <div class="complete-stats">
      <div class="cstat"><b id="cstat-answered">0</b><span>Answered</span></div>
      <div class="cstat"><b id="cstat-flagged">0</b><span>Flagged</span></div>
      <div class="cstat"><b id="cstat-time">0m</b><span>Duration</span></div>
    </div>
    {{-- The reference, in place of the filename that used to sit here. It is what a
         worker quotes to support, so it is the one thing worth showing prominently. --}}
    <div class="file-pill" id="result-reference">&mdash;</div>
    <div class="btn-row" style="justify-content:center;">
      <button class="btn btn-primary" id="btn-back-to-tasks">Back to My Tasks</button>
      {{-- "Start New Session" removed: a submitted task cannot be revisited, so the
           button could only reload into a blocked screen. --}}
    </div>
  </div>
</div>

<!-- ============ SUBMITTING VIEW ============ -->
{{-- Shown while the POST is in flight. The done screen used to appear immediately,
     which told workers their task was delivered while the request was still going —
     or had failed. Nothing claims success until the server confirms it. --}}
<div id="view-submitting">
  <div class="complete-card">
    <div class="complete-icon" style="animation:spin 1s linear infinite;">&#8635;</div>
    <h2>Submitting your answers</h2>
    <p style="color:var(--ink-soft); margin:10px 0 0;">Please keep this page open. This usually takes a couple of seconds.</p>
  </div>
</div>

<!-- ============ SUBMISSION FAILED VIEW ============ -->
<div id="view-failed">
  <div class="complete-card">
    <div class="complete-icon" style="background:#fee2e2; color:#b91c1c;">!</div>
    <h2>We could not submit your answers</h2>
    <p style="color:var(--ink-soft); margin:10px 0 0;" id="failed-reason">Something went wrong on the way to our server.</p>
    <p style="color:var(--ink-soft); margin:14px 0 0;">
      <strong>Your work is safe.</strong> Every answer is still saved on this device and on our
      server from the last autosave. Nothing has been lost and you do not need to start again.
    </p>
    <div class="btn-row" style="justify-content:center; margin-top:20px;">
      <button class="btn btn-primary" id="btn-retry-submit">Try again</button>
      <button class="btn btn-ghost" id="btn-failed-back">Back to My Tasks</button>
    </div>
  </div>
</div>

<!-- Task data is loaded from data.js as a local <script> tag (not fetch/XHR), -->
<!-- which is the one loading method that works reliably when this file is    -->
<!-- opened directly from disk (file://) with no server and no internet.      -->
{{-- data.js is not loaded here: TASK_DATA is injected at the top of this file
     from the assigned work. Leaving the tag would request /annotate/data.js and 404. --}}
<script>
(function(){
  "use strict";

  // ---------- fallback if data.js is missing ----------
  /* ── Remotox server bridge ────────────────────────────────────────────────
     Injected by Laravel. The console is otherwise unchanged and still runs from
     a zip with a local data.js — REMOTOX being undefined is the offline case,
     and every hook below falls back to the original localStorage behaviour.   */
  var REMOTOX = window.REMOTOX || null;

  /* Served by Laravel but with no bridge means the injection failed, and the console
     would otherwise carry on in offline mode: rendering fallback data, generating its
     own annotator ID, and downloading a file at the end instead of submitting. That
     looks like a working task and produces nothing the platform can see, which is a
     far worse outcome than an error. */
  if (! REMOTOX && location.protocol.indexOf("http") === 0) {
    document.addEventListener("DOMContentLoaded", function () {
      document.body.innerHTML =
        '<div style="max-width:520px;margin:80px auto;padding:26px;border:1px solid #fca5a5;' +
        'background:#fef2f2;border-radius:12px;font:14px/1.6 system-ui,sans-serif;color:#7f1d1d;">' +
        '<strong style="display:block;font-size:16px;margin-bottom:8px;">This task could not load</strong>' +
        'The console did not receive its task data, so nothing you do here would be saved. ' +
        'Please reload the page. If it happens again, contact support and quote this URL.' +
        '</div>';
    });
    throw new Error("REMOTOX bridge missing — refusing to run in offline mode over HTTP.");
  }

  var TASK = (typeof window.TASK_DATA !== "undefined") ? window.TASK_DATA : {
    meta:{ task_id:"missing_task", title:"No task data found",
      instructions:"data.js was not found next to index.html, or it failed to load. Make sure data.js sits in the same folder as index.html and defines window.TASK_DATA.",
      version:"0", estimated_minutes:0, annotator_fields:[] },
    questions:[]
  };

  var STORAGE_KEY = "annotation_progress__" + (TASK.meta.task_id || "task");

  // ---------- self-check: validate data.js the moment this file opens ----------
  // This runs for every viewer, including you when you open index.html to
  // double-check a task before sending it out. If data.js has a mistake,
  // this is shown instead of the normal start screen so it never reaches
  // an annotator silently broken.
  var ALLOWED_TYPES = ["single_choice","multi_choice","likert","rating_scale","free_text","ranking","pairwise_comparison","span_highlight"];

  function validateTaskData(task){
    var errors = [], warnings = [];
    if(!task || typeof task !== "object"){
      errors.push("TASK_DATA is missing or not an object — check that data.js defines window.TASK_DATA.");
      return { errors:errors, warnings:warnings };
    }
    if(!task.meta || !task.meta.task_id) errors.push("meta.task_id is required.");
    if(!task.meta || !task.meta.title) errors.push("meta.title is required.");
    if(!Array.isArray(task.questions) || !task.questions.length){
      errors.push("questions must be a non-empty array.");
      return { errors:errors, warnings:warnings };
    }
    var seenIds = {};
    task.questions.forEach(function(q, i){
      var where = "questions[" + i + "]" + (q && q.id ? " (id: " + q.id + ")" : "");
      if(!q || !q.id){ errors.push(where + ": missing id."); return; }
      if(seenIds[q.id]) errors.push(where + ": duplicate id '" + q.id + "'.");
      seenIds[q.id] = true;

      if(!q.type || ALLOWED_TYPES.indexOf(q.type) === -1){
        errors.push(where + ": invalid or missing type '" + q.type + "'. Must be one of: " + ALLOWED_TYPES.join(", "));
        return;
      }
      if(!q.prompt || !String(q.prompt).trim()) errors.push(where + ": missing prompt.");

      if(["single_choice","multi_choice","ranking"].indexOf(q.type) > -1){
        if(!Array.isArray(q.options) || q.options.length < 2){
          errors.push(where + ": type '" + q.type + "' needs at least 2 entries in options.");
        } else {
          var vals = {};
          q.options.forEach(function(o, oi){
            if(!o || o.value === undefined || o.value === null || o.value === "") errors.push(where + ": options[" + oi + "] missing value.");
            else if(vals[o.value]) errors.push(where + ": duplicate option value '" + o.value + "'.");
            else vals[o.value] = true;
            if(!o || !o.label) warnings.push(where + ": options[" + oi + "] missing label.");
          });
        }
      }
      if(q.type === "pairwise_comparison"){
        if(!q.response_a || !String(q.response_a).trim()) errors.push(where + ": pairwise_comparison needs response_a.");
        if(!q.response_b || !String(q.response_b).trim()) errors.push(where + ": pairwise_comparison needs response_b.");
      }
      if((q.type === "likert" || q.type === "rating_scale") && q.scale){
        if(typeof q.scale.min !== "number" || typeof q.scale.max !== "number" || q.scale.min >= q.scale.max){
          errors.push(where + ": scale.min must be a number less than scale.max.");
        }
      }
      if(q.type === "free_text" && q.min_length && q.max_length && q.min_length > q.max_length){
        errors.push(where + ": min_length cannot be greater than max_length.");
      }
      if(q.is_gold && (q.expected_answer === undefined || q.expected_answer === null)){
        errors.push(where + ": is_gold is true but expected_answer is missing.");
      }
      if(q.is_gold && q.required === false){
        warnings.push(where + ": gold question is marked optional (required:false) — consider making it required so it's always scored.");
      }
    });
    return { errors: errors, warnings: warnings };
  }

  function renderTaskErrors(report){
    $("start-eyebrow").textContent = "TASK FILE ERROR";
    $("start-title").textContent = "This task file can't be opened yet";
    $("start-meta").innerHTML = "";
    var body = $("start-body");
    body.innerHTML = "";
    var intro = document.createElement("p");
    intro.textContent = "data.js has " + report.errors.length + " problem" +
      (report.errors.length===1?"":"s") + " that need fixing before this task is sent out. " +
      "This message only appears because something is wrong — with valid data it never shows.";
    body.appendChild(intro);
    var list = document.createElement("ul");
    list.className = "err-list";
    report.errors.forEach(function(e){
      var li = document.createElement("li"); li.textContent = e; list.appendChild(li);
    });
    body.appendChild(list);
    if(report.warnings.length){
      var wNote = document.createElement("p");
      wNote.className = "warn-note";
      wNote.textContent = "Also, " + report.warnings.length + " non-blocking warning" + (report.warnings.length===1?"":"s") + ":";
      body.appendChild(wNote);
      var wlist = document.createElement("ul");
      wlist.className = "warn-list";
      report.warnings.forEach(function(w){ var li=document.createElement("li"); li.textContent=w; wlist.appendChild(li); });
      body.appendChild(wlist);
    }
  }

  function renderTaskWarningBanner(warnings){
    var body = $("start-body");
    var box = document.createElement("div");
    box.className = "warn-banner";
    box.textContent = "Heads up — " + warnings.length + " non-blocking warning" +
      (warnings.length===1?"":"s") + " in data.js (see browser console for details). This won't stop the task from working.";
    body.insertBefore(box, body.firstChild);
    if(window.console && console.warn) console.warn("Task data warnings:", warnings);
  }

  var STATE = {
    index: 0,
    responses: {},      // qid -> {answer, confidence, flagged, timeSpent}
    annotator: {},
    startedAt: null,
    questionEnteredAt: null
  };

  // ---------- integrity signals (quiet, non-blocking) ----------
  // Best-effort client-side heuristics only. This is a single offline file
  // the person running it fully controls — nothing here is tamper-proof
  // against a technically determined user (they can read/edit this exact
  // code). It's meant to catch casual automation/AI-assisted completion,
  // not to stop a sophisticated one. Never surfaced in the UI.
  STATE.integrity = {
    automationSignals: detectAutomationSignals(),
    mouseMoved: false,
    keyEventCount: 0,
    pasteCount: 0,
    freeTextKeyCounts: {}   // qid -> keydown count while that field was focused
  };

  function detectAutomationSignals(){
    var s = [];
    try{
      if(navigator.webdriver) s.push("webdriver_flag");
      if(/HeadlessChrome/.test(navigator.userAgent)) s.push("headless_ua");
      if(window.callPhantom || window._phantom || window.phantom) s.push("phantomjs");
      if(window.Cypress) s.push("cypress");
      if(window.__nightmare) s.push("nightmare");
      if(window.__selenium_evaluate || window.__selenium_unwrapped ||
         window.__webdriver_evaluate || window.__webdriver_unwrapped ||
         window.__webdriver_script_function || window.__driver_evaluate ||
         window.__driver_unwrapped || window.__fxdriver_evaluate || window.__fxdriver_unwrapped){
        s.push("driver_globals");
      }
      Object.keys(window).forEach(function(k){
        if(k.indexOf("$cdc_") === 0 || k.indexOf("$chrome_") === 0) s.push("cdc_global");
      });
      if(navigator.languages && navigator.languages.length === 0) s.push("no_languages");
      if(navigator.plugins && navigator.plugins.length === 0 && /Chrome/.test(navigator.userAgent)) s.push("no_plugins_chrome");
    }catch(e){ /* ignore */ }
    return s;
  }

  document.addEventListener("mousemove", function(){ STATE.integrity.mouseMoved = true; }, { once:true, passive:true });
  document.addEventListener("keydown", function(e){
    STATE.integrity.keyEventCount++;
    var tag = (document.activeElement && document.activeElement.tagName) || "";
    if(tag === "TEXTAREA" && document.activeElement.classList.contains("qtext")){
      var q = currentQ();
      if(q){
        STATE.integrity.freeTextKeyCounts[q.id] = (STATE.integrity.freeTextKeyCounts[q.id]||0) + 1;
      }
    }
  }, true);
  document.addEventListener("paste", function(e){
    STATE.integrity.pasteCount++;
  }, true);

  // Rough word count for "read time vs answer time" plausibility checks.
  function wordCount(str){ return str ? String(str).trim().split(/\s+/).filter(Boolean).length : 0; }

  function computeIntegritySignals(){
    var s = (STATE.integrity.automationSignals || []).slice();
    if(!STATE.integrity.mouseMoved) s.push("no_mouse_movement");
    if(STATE.integrity.pasteCount > 0) s.push("paste_x" + STATE.integrity.pasteCount);

    TASK.questions.forEach(function(q){
      var r = STATE.responses[q.id];
      if(!r) return;
      // Free text with a real answer but no recorded keystrokes on that field
      // usually means the value was set programmatically (console/automation)
      // rather than typed.
      if(q.type === "free_text" && r.answer && String(r.answer).trim().length){
        var keys = STATE.integrity.freeTextKeyCounts[q.id] || 0;
        if(keys === 0) s.push("text_no_keystrokes:" + q.id);
      }
      // Implausibly fast relative to how much there was to read.
      var words = wordCount(q.prompt) + wordCount(q.context);
      var secs = r.timeSpent || 0;
      if(words > 60 && secs > 0 && secs < words / 12){ // ~700+ wpm reading — not plausible
        s.push("fast_relative_to_content:" + q.id);
      }
    });
    return s;
  }

  function encodeIntegrity(obj){
    try{ return btoa(unescape(encodeURIComponent(JSON.stringify(obj)))); }
    catch(e){ return null; }
  }

  // ---------- element refs ----------
  var $ = function(id){ return document.getElementById(id); };
  var viewStart = $("view-start"), viewTask = $("view-task"), viewComplete = $("view-complete");

  // ================= START VIEW =================
  function initStart(){
    var report = validateTaskData(TASK);
    if(report.errors.length){
      renderTaskErrors(report);
      return;
    }

    $("start-title").textContent = TASK.meta.title || "Untitled Task";
    $("start-instructions").textContent = TASK.meta.instructions || "";
    $("qcount-label").textContent = (TASK.questions||[]).length + " item" + ((TASK.questions||[]).length===1?"":"s");

    var meta = $("start-meta");
    meta.innerHTML = "";
    var chips = [
      ["ID", TASK.meta.task_id],
      ["v" + (TASK.meta.version||"1.0")],
      [(TASK.meta.estimated_minutes || "?") + " min est."],
      [TASK.meta.domain || null]
    ];
    chips.forEach(function(c){
      var label = c.length===2 ? (c[0]+": "+c[1]) : c[0];
      if(!c[c.length-1]) return;
      var el = document.createElement("span");
      el.className = "meta-chip";
      el.textContent = label;
      meta.appendChild(el);
    });

    var fieldsWrap = $("annotator-fields");
    fieldsWrap.innerHTML = "";
    (TASK.meta.annotator_fields || [{id:"annotator_id", label:"Annotator ID", required:true, auto_generate:true}]).forEach(function(f){
      var g = document.createElement("div");
      g.className = "field-group";
      var autoGen = !!f.auto_generate;
      /* A value supplied by the task wins over the generated one. Served by Laravel
         the annotator IS the annotate code, and a locally generated AN-R5XR9 next to
         an AN-N96HHAT4 in the URL is two identities for one worker — the one they
         would quote to support is the one nobody can look up. */
      var fixedValue = (f.value !== undefined && f.value !== null && f.value !== "") ? String(f.value) : null;
      var genValue   = fixedValue !== null ? fixedValue : (autoGen ? genAnnotatorId(f.id_prefix) : "");
      var readOnly   = fixedValue !== null || autoGen;
      g.innerHTML = '<label>'+f.label+(f.required?' <span class="req">*</span>':'')+
          (readOnly ? '<span class="auto-tag">'+(fixedValue !== null ? 'from your task code' : 'auto-assigned')+'</span>' : '')+'</label>' +
        '<input type="text" data-field="'+f.id+'" placeholder="'+(f.placeholder||"")+'"' +
          (readOnly ? ' value="'+genValue+'" readonly' : '') + '>';
      fieldsWrap.appendChild(g);
    });
    fieldsWrap.addEventListener("input", validateStart);

    // resume check
    var saved = loadSaved();
    if(saved && saved.responses && Object.keys(saved.responses).length){
      $("resume-banner").style.display = "flex";
    }
    if(report.warnings.length) renderTaskWarningBanner(report.warnings);
    validateStart();
  }

  // Generates a random annotator ID like "AN-7K4QM". Ambiguous characters
  // (0/O, 1/I/L) are excluded so IDs stay easy to read back if ever needed.
  function genAnnotatorId(prefix){
    var chars = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
    var s = "";
    for(var i=0;i<5;i++) s += chars[Math.floor(Math.random()*chars.length)];
    return (prefix || "AN") + "-" + s;
  }

  function validateStart(){
    var ok = true;
    var inputs = document.querySelectorAll('#annotator-fields input');
    var fieldsMeta = TASK.meta.annotator_fields || [{id:"annotator_id", required:true}];
    inputs.forEach(function(inp, i){
      var meta = fieldsMeta[i] || {};
      if(meta.required && !inp.value.trim()) ok = false;
    });
    if(!TASK.questions || !TASK.questions.length) ok = false;
    $("btn-begin").disabled = !ok;
  }

  function loadSaved(){
    try{
      /* The server copy wins when there is one: it is the only version that has
         seen work done on another device. */
      var raw = (REMOTOX && REMOTOX.progress) ? JSON.stringify(REMOTOX.progress) : localStorage.getItem(STORAGE_KEY);
      if(!raw) return null;
      return JSON.parse(raw);
    }catch(e){ return null; }
  }

  function persist(){
    try{
      /* Written locally FIRST, then pushed to the server.
         Local is the copy that survives a dropped connection, a closed laptop or
         a dead battery; the server copy is what makes the task resumable on a
         different device. Writing the server first and failing would lose the
         keystrokes entirely. */
      if (REMOTOX) { scheduleServerSave(); }
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        annotator: STATE.annotator,
        index: STATE.index,
        responses: STATE.responses,
        startedAt: STATE.startedAt
      }));
    }catch(e){ /* storage unavailable — degrade silently */ }
  }

  $("btn-do-resume").addEventListener("click", function(){
    var saved = loadSaved();
    if(saved){
      STATE.responses = saved.responses || {};
      STATE.index = Math.min(saved.index || 0, Math.max(TASK.questions.length-1,0));
      STATE.annotator = saved.annotator || {};
      STATE.startedAt = saved.startedAt || new Date().toISOString();
      Object.keys(STATE.annotator).forEach(function(k){
        var inp = document.querySelector('#annotator-fields input[data-field="'+k+'"]');
        if(inp) inp.value = STATE.annotator[k];
      });
    }
    beginTask(true);
  });
  $("btn-discard-resume").addEventListener("click", function(){
    localStorage.removeItem(STORAGE_KEY);
    $("resume-banner").style.display = "none";
  });

  $("btn-begin").addEventListener("click", function(){ beginTask(false); });

  function beginTask(fromResume){
    if(!fromResume){
      STATE.annotator = {};
      document.querySelectorAll('#annotator-fields input').forEach(function(inp){
        STATE.annotator[inp.dataset.field] = inp.value.trim();
      });
      STATE.startedAt = new Date().toISOString();
      STATE.index = 0;
      STATE.responses = {};
    }
    $("rail-who").textContent = Object.keys(STATE.annotator).map(function(k){
      return k+": "+STATE.annotator[k];
    }).join(" · ") || "—";
    $("rail-title").textContent = TASK.meta.title || "—";

    viewStart.style.display = "none";
    viewTask.classList.add("active");

    buildManifest();
    STATE.questionEnteredAt = Date.now();
    renderQuestion();
    persist();
  }

  // ================= TASK VIEW =================
  function buildManifest(){
    var list = $("manifest-list");
    list.innerHTML = "";
    $("manifest-count").textContent = TASK.questions.length + " items";
    TASK.questions.forEach(function(q, i){
      var li = document.createElement("li");
      li.className = "manifest-item";
      li.dataset.idx = i;
      li.innerHTML = '<span class="m-idx">'+String(i+1).padStart(2,"0")+'</span><span class="m-dot"></span><span class="m-label"></span>';
      li.querySelector(".m-label").textContent = truncate(q.prompt, 34);
      li.addEventListener("click", function(){ jumpTo(i); });
      list.appendChild(li);
    });
  }

  function truncate(s, n){ s = s||""; return s.length>n ? s.slice(0,n-1)+"…" : s; }

  function refreshManifest(){
    var items = document.querySelectorAll(".manifest-item");
    items.forEach(function(li){
      var i = parseInt(li.dataset.idx, 10);
      var q = TASK.questions[i];
      var r = STATE.responses[q.id];
      li.classList.toggle("is-active", i === STATE.index);
      li.classList.toggle("is-done", !!(r && hasAnswer(q, r)));
      li.classList.toggle("is-flagged", !!(r && r.flagged));
    });
    var answered = TASK.questions.filter(function(q){
      var r = STATE.responses[q.id]; return r && hasAnswer(q, r);
    }).length;
    var total = TASK.questions.length;
    var pct = total ? Math.round((answered/total)*100) : 0;
    $("gauge-num").textContent = pct + "%";
    $("gauge-num-mobile").textContent = pct + "%";
    $("gauge-sub").textContent = answered + " / " + total + " answered";
    var circumference = 138.2;
    $("gauge-ring").setAttribute("stroke-dashoffset", circumference - (circumference*pct/100));
  }

  function hasAnswer(q, r){
    if(!r) return false;
    var a = r.answer;
    if(a === undefined || a === null) return false;
    if(Array.isArray(a)) return a.length>0;
    if(typeof a === "string") return a.trim().length>0;
    if(typeof a === "object") return Object.keys(a).length>0;
    return true;
  }

  function currentQ(){ return TASK.questions[STATE.index]; }

  function ensureResponse(qid){
    if(!STATE.responses[qid]) STATE.responses[qid] = { answer:null, confidence:null, flagged:false, timeSpent:0 };
    return STATE.responses[qid];
  }

  function renderQuestion(){
    var q = currentQ();
    if(!q) return;
    $("qpos").textContent = "Q" + (STATE.index+1) + " / " + TASK.questions.length;

    var tagsWrap = $("qhead-tags");
    tagsWrap.innerHTML = "";
    var typeTag = document.createElement("span");
    typeTag.className = "qtag";
    typeTag.textContent = (q.type||"").replace(/_/g," ");
    tagsWrap.appendChild(typeTag);
    if(q.difficulty){
      var d = document.createElement("span");
      d.className = "qtag diff-"+q.difficulty;
      d.textContent = q.difficulty;
      tagsWrap.appendChild(d);
    }

    $("qprompt").textContent = q.prompt || "";

    var ctxSlot = $("qcontext-slot");
    ctxSlot.innerHTML = "";
    if(q.context){
      var c = document.createElement("div");
      c.className = "qcontext";
      c.textContent = q.context;
      ctxSlot.appendChild(c);
    }
    if(q.code && q.code.content){
      var codeBox = document.createElement("div");
      codeBox.className = "qcode";
      codeBox.innerHTML = '<span class="lang">'+(q.code.language||"code")+'</span>';
      var pre = document.createElement("pre");
      pre.style.margin = "0";
      pre.style.whiteSpace = "pre-wrap";
      pre.textContent = q.code.content;
      codeBox.appendChild(pre);
      ctxSlot.appendChild(codeBox);
    }
    if(q.image){
      var img = document.createElement("img");
      img.className = "qimg";
      img.src = q.image;
      img.alt = q.image_alt || "Reference image";
      ctxSlot.appendChild(img);
    }

    renderInput(q);
    renderConfidence(q);

    var r = STATE.responses[q.id];
    $("btn-flag").classList.toggle("active", !!(r && r.flagged));
    $("validation-msg").classList.remove("show");
    $("btn-prev").disabled = STATE.index === 0;
    $("btn-next").textContent = (STATE.index === TASK.questions.length-1) ? "Submit Task ✓" : "Next →";

    refreshManifest();
    updateKbdHint(q);
  }

  var TYPES_WITH_NUMBER_SHORTCUT = { single_choice:1, multi_choice:1, pairwise_comparison:1 };
  function updateKbdHint(q){
    var hint = $("kbd-hint");
    if(!hint) return;
    if(TYPES_WITH_NUMBER_SHORTCUT[q.type]){
      hint.innerHTML = "Shortcuts: <kbd>1–9</kbd> select option · <kbd>←</kbd>/<kbd>→</kbd> navigate · <kbd>Enter</kbd> continue";
    } else {
      hint.innerHTML = "Shortcuts: <kbd>←</kbd>/<kbd>→</kbd> navigate · <kbd>Enter</kbd> continue";
    }
  }

  function renderConfidence(q){
    var wrap = $("confidence-dots");
    wrap.innerHTML = "";
    var r = STATE.responses[q.id];
    for(var i=1;i<=5;i++){
      (function(i){
        var d = document.createElement("div");
        d.className = "conf-dot" + ((r && r.confidence===i) ? " selected" : "");
        d.textContent = i;
        d.title = "Confidence " + i + "/5";
        d.addEventListener("click", function(){
          var resp = ensureResponse(q.id);
          resp.confidence = (resp.confidence === i) ? null : i;
          renderConfidence(q);
          persist();
        });
        wrap.appendChild(d);
      })(i);
    }
  }

  $("btn-flag").addEventListener("click", function(){
    var q = currentQ();
    var r = ensureResponse(q.id);
    r.flagged = !r.flagged;
    $("btn-flag").classList.toggle("active", r.flagged);
    refreshManifest();
    persist();
  });

  // ---------- input renderers ----------
  function renderInput(q){
    var zone = $("input-zone");
    zone.innerHTML = "";
    var r = ensureResponse(q.id);
    var renderer = RENDERERS[q.type] || RENDERERS.free_text;
    renderer(q, r, zone);
  }

  var RENDERERS = {};

  function buildChoiceInput(kind, name, checked, onToggle){
    var input = document.createElement("input");
    input.type = kind; // "radio" | "checkbox"
    input.name = name;
    input.checked = checked;
    input.addEventListener("change", onToggle);
    return input;
  }

  RENDERERS.single_choice = function(q, r, zone){
    var list = document.createElement("div");
    list.className = "choice-list";
    list.setAttribute("role", "radiogroup");
    list.setAttribute("aria-label", q.prompt || "Choose one");
    (q.options||[]).forEach(function(opt, i){
      var item = document.createElement("label");
      item.className = "choice-item" + (r.answer===opt.value ? " selected" : "");
      var input = buildChoiceInput("radio", "choice_"+q.id, r.answer===opt.value, function(){
        r.answer = opt.value;
        renderInput(q); refreshManifest(); persist();
      });
      item.appendChild(input);
      item.insertAdjacentHTML("beforeend", '<span class="choice-key" aria-hidden="true">'+(i+1)+'</span><span class="choice-mark" style="border-radius:50%;" aria-hidden="true"></span><span class="choice-text"></span>');
      item.querySelector(".choice-text").textContent = opt.label;
      list.appendChild(item);
    });
    zone.appendChild(list);
  };

  RENDERERS.multi_choice = function(q, r, zone){
    if(!Array.isArray(r.answer)) r.answer = [];
    var list = document.createElement("div");
    list.className = "choice-list";
    list.setAttribute("role", "group");
    list.setAttribute("aria-label", q.prompt || "Choose all that apply");
    (q.options||[]).forEach(function(opt, i){
      var checked = r.answer.indexOf(opt.value) > -1;
      var item = document.createElement("label");
      item.className = "choice-item" + (checked ? " selected" : "");
      var input = buildChoiceInput("checkbox", "choice_"+q.id+"_"+i, checked, function(){
        var idx = r.answer.indexOf(opt.value);
        if(idx>-1) r.answer.splice(idx,1); else r.answer.push(opt.value);
        renderInput(q); refreshManifest(); persist();
      });
      item.appendChild(input);
      item.insertAdjacentHTML("beforeend", '<span class="choice-key" aria-hidden="true">'+(i+1)+'</span><span class="choice-mark" aria-hidden="true"></span><span class="choice-text"></span>');
      item.querySelector(".choice-text").textContent = opt.label;
      list.appendChild(item);
    });
    zone.appendChild(list);
  };

  RENDERERS.likert = function(q, r, zone){
    var scale = q.scale || {min:1, max:5, minLabel:"Strongly disagree", maxLabel:"Strongly agree"};
    var row = document.createElement("div");
    row.className = "scale-row";
    for(var v=scale.min; v<=scale.max; v++){
      (function(v){
        var b = document.createElement("div");
        b.className = "scale-btn" + (r.answer===v ? " selected" : "");
        b.textContent = v;
        b.addEventListener("click", function(){ r.answer=v; renderInput(q); refreshManifest(); persist(); });
        row.appendChild(b);
      })(v);
    }
    zone.appendChild(row);
    var labels = document.createElement("div");
    labels.className = "scale-labels";
    labels.innerHTML = "<span></span><span></span>";
    labels.children[0].textContent = scale.minLabel || "";
    labels.children[1].textContent = scale.maxLabel || "";
    zone.appendChild(labels);
  };

  RENDERERS.rating_scale = function(q, r, zone){
    var scale = q.scale || {min:0, max:100};
    var wrap = document.createElement("div");
    wrap.className = "slider-wrap";
    var val = document.createElement("div");
    val.className = "slider-val";
    var initial = (r.answer!==null && r.answer!==undefined) ? r.answer : Math.round((scale.min+scale.max)/2);
    val.textContent = initial;
    var input = document.createElement("input");
    input.type = "range";
    input.min = scale.min; input.max = scale.max; input.step = scale.step || 1;
    input.value = initial;
    input.addEventListener("input", function(){
      val.textContent = input.value;
      r.answer = Number(input.value);
      refreshManifest(); persist();
    });
    wrap.appendChild(val); wrap.appendChild(input);
    var labels = document.createElement("div");
    labels.className = "scale-labels";
    labels.innerHTML = "<span></span><span></span>";
    labels.children[0].textContent = scale.minLabel || scale.min;
    labels.children[1].textContent = scale.maxLabel || scale.max;
    wrap.appendChild(labels);
    zone.appendChild(wrap);
    if(r.answer===null || r.answer===undefined){ r.answer = initial; persist(); }
  };

  RENDERERS.free_text = function(q, r, zone){
    var ta = document.createElement("textarea");
    ta.className = "qtext";
    ta.placeholder = q.placeholder || "Type your response…";
    ta.value = r.answer || "";
    zone.appendChild(ta);
    var count = document.createElement("div");
    count.className = "char-count";
    zone.appendChild(count);
    function updateCount(){
      var len = ta.value.length;
      var min = q.min_length||0, max = q.max_length;
      var txt = len + " chars";
      if(min) txt += " · min " + min;
      if(max) txt += " · max " + max;
      count.textContent = txt;
      count.classList.toggle("warn", (max && len>max) || (min && len<min && len>0));
    }
    updateCount();
    ta.addEventListener("input", function(){
      r.answer = ta.value;
      updateCount(); refreshManifest(); persist();
    });
  };

  RENDERERS.ranking = function(q, r, zone){
    if(!Array.isArray(r.answer) || r.answer.length !== (q.options||[]).length){
      r.answer = (q.options||[]).map(function(o){ return o.value; });
    }
    var byValue = {};
    (q.options||[]).forEach(function(o){ byValue[o.value] = o.label; });

    var list = document.createElement("div");
    list.className = "rank-list";
    function draw(){
      list.innerHTML = "";
      r.answer.forEach(function(val, i){
        var item = document.createElement("div");
        item.className = "rank-item";
        item.innerHTML = '<span class="rank-num"></span><span class="rank-text"></span><span class="rank-arrows"><button data-dir="up">▲</button><button data-dir="down">▼</button></span>';
        item.querySelector(".rank-num").textContent = i+1;
        item.querySelector(".rank-text").textContent = byValue[val] || val;
        var upBtn = item.querySelector('[data-dir="up"]');
        var downBtn = item.querySelector('[data-dir="down"]');
        upBtn.disabled = i===0;
        downBtn.disabled = i===r.answer.length-1;
        upBtn.addEventListener("click", function(){
          [r.answer[i-1], r.answer[i]] = [r.answer[i], r.answer[i-1]];
          draw(); refreshManifest(); persist();
        });
        downBtn.addEventListener("click", function(){
          [r.answer[i+1], r.answer[i]] = [r.answer[i], r.answer[i+1]];
          draw(); refreshManifest(); persist();
        });
        list.appendChild(item);
      });
    }
    draw();
    zone.appendChild(list);
  };

  RENDERERS.pairwise_comparison = function(q, r, zone){
    var grid = document.createElement("div");
    grid.className = "pair-grid";
    grid.innerHTML =
      '<div class="pair-box"><div class="pair-box-head">Response A</div><div class="pair-box-body"></div></div>' +
      '<div class="pair-box"><div class="pair-box-head">Response B</div><div class="pair-box-body"></div></div>';
    grid.querySelectorAll(".pair-box-body")[0].textContent = q.response_a || "";
    grid.querySelectorAll(".pair-box-body")[1].textContent = q.response_b || "";
    zone.appendChild(grid);

    var choices = [
      {value:"a_better", label:"A is better"},
      {value:"b_better", label:"B is better"},
      {value:"tie", label:"Tie"},
      {value:"both_bad", label:"Both are bad"}
    ];
    var row = document.createElement("div");
    row.className = "pair-choice-row";
    row.setAttribute("role", "radiogroup");
    row.setAttribute("aria-label", "Verdict");
    if(!r.answer || typeof r.answer !== "object") r.answer = {verdict:null, reasoning:""};
    choices.forEach(function(c){
      var item = document.createElement("label");
      item.className = "choice-item" + (r.answer.verdict===c.value ? " selected":"");
      var input = buildChoiceInput("radio", "verdict_"+q.id, r.answer.verdict===c.value, function(){
        r.answer.verdict = c.value;
        renderInput(q); refreshManifest(); persist();
      });
      item.appendChild(input);
      item.insertAdjacentHTML("beforeend", '<span class="choice-text"></span>');
      item.querySelector(".choice-text").textContent = c.label;
      row.appendChild(item);
    });
    zone.appendChild(row);

    if(q.require_reasoning !== false){
      var ta = document.createElement("textarea");
      ta.className = "qtext";
      ta.style.minHeight = "80px";
      ta.style.marginTop = "12px";
      ta.placeholder = "Briefly explain your judgment…";
      ta.value = r.answer.reasoning || "";
      ta.addEventListener("input", function(){
        r.answer.reasoning = ta.value; refreshManifest(); persist();
      });
      zone.appendChild(ta);
    }
  };

  RENDERERS.span_highlight = function(q, r, zone){
    if(!Array.isArray(r.answer)) r.answer = [];
    var text = q.context || q.prompt || "";
    var sentences = text.match(/[^.!?]+[.!?]*/g) || [text];
    var box = document.createElement("div");
    box.className = "span-box";
    sentences.forEach(function(s, i){
      var span = document.createElement("span");
      span.className = "span-chip" + (r.answer.indexOf(i)>-1 ? " selected":"");
      span.textContent = s;
      span.addEventListener("click", function(){
        var idx = r.answer.indexOf(i);
        if(idx>-1) r.answer.splice(idx,1); else r.answer.push(i);
        span.classList.toggle("selected");
        refreshManifest(); persist();
      });
      box.appendChild(span);
    });
    zone.appendChild(box);
    var hint = document.createElement("div");
    hint.className = "span-hint";
    hint.textContent = "Click each sentence that applies (" + r.answer.length + " selected)";
    zone.appendChild(hint);
  };

  // ---------- navigation ----------
  function commitTimeSpent(){
    var q = currentQ();
    if(!q || !STATE.questionEnteredAt) return;
    var r = ensureResponse(q.id);
    r.timeSpent = (r.timeSpent||0) + (Date.now() - STATE.questionEnteredAt)/1000;
    STATE.questionEnteredAt = Date.now();
  }

  function isRequired(q){ return q.required !== false; }

  function validateCurrent(){
    var q = currentQ();
    var r = STATE.responses[q.id];
    var ok = true;
    var msg = "Please provide a response before continuing.";

    if(isRequired(q)){
      if(q.type === "pairwise_comparison"){
        ok = !!(r && r.answer && r.answer.verdict);
      } else {
        ok = hasAnswer(q, r);
      }
    }

    if(ok && q.type === "free_text" && r && typeof r.answer === "string" && r.answer.length){
      if(q.min_length && r.answer.length < q.min_length){
        ok = false;
        msg = "Response must be at least " + q.min_length + " characters — currently " + r.answer.length + ".";
      } else if(q.max_length && r.answer.length > q.max_length){
        ok = false;
        msg = "Response must be under " + q.max_length + " characters — currently " + r.answer.length + ".";
      }
    }

    $("validation-msg").textContent = msg;
    return ok;
  }

  function jumpTo(i){
    commitTimeSpent();
    STATE.index = i;
    STATE.questionEnteredAt = Date.now();
    renderQuestion();
    persist();
    window.scrollTo({top:0, behavior:"smooth"});
  }

  $("btn-prev").addEventListener("click", function(){
    if(STATE.index>0) jumpTo(STATE.index-1);
  });

  $("btn-next").addEventListener("click", function(){
    if(!validateCurrent()){
      $("validation-msg").classList.add("show");
      $("qcard").classList.remove("shake"); void $("qcard").offsetWidth;
      $("qcard").classList.add("shake");
      return;
    }
    if(STATE.index === TASK.questions.length-1){
      finishTask();
    } else {
      jumpTo(STATE.index+1);
    }
  });

  $("btn-save-exit").addEventListener("click", function(){
    commitTimeSpent();
    persist();
    /* Saves and leaves. This used to call submitToServer(), which meant Save & Exit
       delivered the task — a worker stopping halfway would have had partial work
       submitted for review without ever pressing Finish. */
    if (window.pushProgressNow) { window.pushProgressNow(); }
    window.location.href = (window.REMOTOX && window.REMOTOX.tasksUrl) ? window.REMOTOX.tasksUrl : "/dashboard/tasks";
  });

  // ---------- cross-browser / cross-machine progress export & import ----------
  // localStorage is scoped to a single browser profile on a single machine, so
  // "Resume" via localStorage only ever works back in the exact browser that
  // wrote it. This exports a small JSON snapshot the annotator can re-import
  // in ANY browser/machine to continue exactly where they left off.
  function exportProgress(){
    commitTimeSpent();
    persist();
    var payload = {
      format: "annotation_progress_v1",
      task_id: TASK.meta.task_id,
      annotator: STATE.annotator,
      index: STATE.index,
      responses: STATE.responses,
      startedAt: STATE.startedAt,
      savedAt: new Date().toISOString()
    };
    var blob = new Blob([JSON.stringify(payload, null, 2)], {type:"application/json"});
    var url = URL.createObjectURL(blob);
    var annId = STATE.annotator.annotator_id || Object.values(STATE.annotator)[0] || "anon";
    var a = document.createElement("a");
    a.href = url;
    a.download = (TASK.meta.task_id||"task") + "__" + slug(annId) + "__progress.json";
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
  // btn-export-progress no longer exists; guarded so the offline build does not throw.
  if ($("btn-export-progress")) { $("btn-export-progress").addEventListener("click", exportProgress); }

  // Accepts both the mid-task "progress" format (from exportProgress above)
  // and the final/partial "results" format (the one Save & Exit already
  // downloads), so previously-saved exports of either kind still work.
  function normalizeImportedPayload(data){
    if(!data || typeof data !== "object") return null;
    if(data.format === "annotation_progress_v1"){
      return {
        task_id: data.task_id, annotator: data.annotator, index: data.index,
        responses: data.responses, startedAt: data.startedAt
      };
    }
    if(Array.isArray(data.responses)){ // results-file shape (Save & Exit / final submit)
      var respObj = {};
      data.responses.forEach(function(r){
        respObj[r.question_id] = {
          answer: r.answer, confidence: r.confidence,
          flagged: r.flagged, timeSpent: r.time_spent_seconds || 0
        };
      });
      return {
        task_id: data.task_id, annotator: data.annotator, index: 0,
        responses: respObj, startedAt: data.started_at
      };
    }
    return null;
  }

  var importFileInput = $("import-progress-file");
  if(importFileInput){
    importFileInput.addEventListener("change", function(e){
      var file = e.target.files[0];
      if(!file) return;
      var reader = new FileReader();
      reader.onload = function(){
        try{
          var data = JSON.parse(reader.result);
          var normalized = normalizeImportedPayload(data);
          if(!normalized){
            alert("This file doesn't look like a valid progress or results file.");
            return;
          }
          if(normalized.task_id && normalized.task_id !== TASK.meta.task_id){
            if(!confirm("This file was saved for a different task (" + normalized.task_id + "). Import anyway?")) return;
          }
          STATE.responses = normalized.responses || {};
          STATE.index = Math.min(normalized.index || 0, Math.max(TASK.questions.length-1, 0));
          STATE.annotator = normalized.annotator || {};
          STATE.startedAt = normalized.startedAt || new Date().toISOString();
          Object.keys(STATE.annotator).forEach(function(k){
            var inp = document.querySelector('#annotator-fields input[data-field="'+k+'"]');
            if(inp) inp.value = STATE.annotator[k];
          });
          $("resume-banner").style.display = "none";
          beginTask(true);
        }catch(err){
          alert("Couldn't read that file — make sure it's an exported progress or results JSON.");
        }
      };
      reader.readAsText(file);
    });
  }

  $("btn-toggle-manifest").addEventListener("click", function(){
    document.querySelector(".rail").classList.toggle("mobile-open");
  });

  document.addEventListener("keydown", function(e){
    if(!viewTask.classList.contains("active")) return;
    var tag = (document.activeElement && document.activeElement.tagName) || "";
    if(tag === "TEXTAREA" || tag === "INPUT") {
      if(e.key === "Escape") document.activeElement.blur();
      return;
    }
    if(e.key >= "1" && e.key <= "9"){
      var i = parseInt(e.key,10)-1;
      var q = currentQ();
      var opts = document.querySelectorAll(".choice-item");
      if(opts[i]) opts[i].click();
    } else if(e.key === "ArrowRight"){
      $("btn-next").click();
    } else if(e.key === "ArrowLeft"){
      $("btn-prev").click();
    } else if(e.key === "Enter"){
      $("btn-next").click();
    }
  });

  // ---------- gold-question quality scoring ----------
  // A question with is_gold:true carries a hidden expected_answer used only
  // to score annotator quality on submit — it is never shown in the UI, so
  // the annotator has no way to tell a gold question apart from a real one.
  function valuesEqual(a, b){
    if(Array.isArray(a) && Array.isArray(b)){
      if(a.length !== b.length) return false;
      for(var i=0;i<a.length;i++){ if(a[i] !== b[i]) return false; }
      return true;
    }
    if(a && b && typeof a === "object" && typeof b === "object"){
      return JSON.stringify(a) === JSON.stringify(b);
    }
    return a === b;
  }

  function evaluateGold(q, answer){
    if(!q.is_gold || q.expected_answer === undefined || q.expected_answer === null) return null;
    if(answer === undefined || answer === null) return { passed:false, expected:q.expected_answer };
    var expected = q.expected_answer;
    var passed;
    if(q.type === "rating_scale"){
      var tol = (q.gold_tolerance !== undefined) ? q.gold_tolerance : 5;
      passed = Math.abs(Number(answer) - Number(expected)) <= tol;
    } else if(q.type === "multi_choice"){
      var a1 = (answer||[]).slice().sort();
      var a2 = (expected||[]).slice().sort();
      passed = valuesEqual(a1, a2);
    } else if(q.type === "pairwise_comparison"){
      passed = !!(answer && answer.verdict === expected);
    } else {
      passed = valuesEqual(answer, expected);
    }
    return { passed: !!passed, expected: expected };
  }

  // ---------- completion & export ----------
  /* Exposed on window for the Remotox bridge, which lives in a separate script and
     otherwise cannot see a function declared in this one. Without this the bridge
     found window.buildResultPayload undefined and fell back to downloading a file —
     the exact behaviour the bridge exists to replace. */
  function buildResultPayload(){
    var answered = 0, flagged = 0;
    var responses = TASK.questions.map(function(q){
      var r = STATE.responses[q.id] || {};
      var counted = hasAnswer(q, r);
      if(counted) answered++;
      if(r.flagged) flagged++;
      // Gold-question pass/fail is intentionally NOT included anywhere in
      // this exported file. It's computed in-memory only (see evaluateGold)
      // and never written to the downloaded JSON, partial or final.
      return {
        question_id: q.id,
        type: q.type,
        answer: (r.answer===undefined) ? null : r.answer,
        confidence: r.confidence || null,
        flagged: !!r.flagged,
        time_spent_seconds: Math.round((r.timeSpent||0)*10)/10
      };
    });
    var completedAt = new Date().toISOString();
    var durationSec = STATE.startedAt ? Math.round((new Date(completedAt) - new Date(STATE.startedAt))/1000) : null;
    return {
      task_id: TASK.meta.task_id,
      task_title: TASK.meta.title,
      task_version: TASK.meta.version || "1.0",
      annotator: STATE.annotator,
      started_at: STATE.startedAt,
      completed_at: completedAt,
      duration_seconds: durationSec,
      responses: responses,
      summary: {
        total_questions: TASK.questions.length,
        answered: answered,
        flagged_count: flagged
      },
      client: {
        user_agent: navigator.userAgent,
        screen: window.screen.width + "x" + window.screen.height,
        generated_by: "Offline Annotation Console v1.0"
      },
      checksum: encodeIntegrity({ s: computeIntegritySignals(), t: Date.now() })
    };
  }

  function downloadResults(partial){
    var payload = buildResultPayload();
    if(partial) payload.partial = true;
    var json = JSON.stringify(payload, null, 2);
    var blob = new Blob([json], {type:"application/json"});
    var url = URL.createObjectURL(blob);
    var ts = new Date().toISOString().replace(/[:.]/g,"-");
    var annId = STATE.annotator.annotator_id || Object.values(STATE.annotator)[0] || "anon";
    var fname = (TASK.meta.task_id||"task") + "__" + slug(annId) + "__" + ts + (partial ? "__partial" : "") + ".json";
    var a = document.createElement("a");
    a.href = url; a.download = fname;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    return {fname: fname, payload: payload};
  }

  window.buildResultPayload = buildResultPayload;
  window.downloadResults    = downloadResults;

  function slug(s){ return String(s).toLowerCase().replace(/[^a-z0-9]+/g,"-").replace(/(^-|-$)/g,"") || "anon"; }

  /* Nothing is cleared and nothing claims success until the server has confirmed.
     The previous version switched to the done screen, wiped localStorage and never
     submitted at all — the POST had been wired to Save & Exit by mistake. A worker
     saw "Task Submitted" over work that had gone nowhere and been deleted locally. */
  function finishTask(){
    commitTimeSpent();
    persist();

    var payload = buildResultPayload();

    showScreen("submitting");

    submitToServer(payload)
      .then(function (res) {
        // Only now is it safe to drop the local copy.
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        showDone(payload, res);
      })
      .catch(function (err) {
        showFailure(err && err.message ? err.message : "We could not reach the server.");
      });
  }

  function showScreen(which){
    viewTask.classList.remove("active");
    viewComplete.classList.remove("active");
    $("view-submitting").classList.remove("active");
    $("view-failed").classList.remove("active");

    if (which === "submitting") { $("view-submitting").classList.add("active"); }
    else if (which === "failed") { $("view-failed").classList.add("active"); }
    else { viewComplete.classList.add("active"); }
  }

  function showDone(payload, res){
    showScreen("done");

    var sum = (payload && payload.summary) ? payload.summary : {};
    $("cstat-answered").textContent = (sum.answered || 0) + "/" + (sum.total_questions || 0);
    $("cstat-flagged").textContent  = sum.flagged_count || 0;
    $("cstat-time").textContent     = (payload && payload.duration_seconds ? Math.round(payload.duration_seconds/60) : 0) + "m";

    $("result-reference").textContent = (res && res.submission_code) ? res.submission_code : "";

    $("complete-note").textContent = (res && res.already)
      ? "This task had already been submitted and is awaiting review."
      : (res && res.message) ? res.message : "Your answers have been received.";
  }

  function showFailure(reason){
    showScreen("failed");
    $("failed-reason").textContent = reason;
  }

  $("btn-retry-submit").addEventListener("click", function(){ finishTask(); });
  $("btn-failed-back").addEventListener("click", goToTasks);
  $("btn-back-to-tasks").addEventListener("click", goToTasks);

  function goToTasks(){
    window.location.href = (window.REMOTOX && window.REMOTOX.tasksUrl) ? window.REMOTOX.tasksUrl : "/dashboard/tasks";
  }

  // ---------- boot ----------
  initStart();
})();
</script>

<script>
/* ── Remotox bridge implementation ──────────────────────────────────────────
   Added by the server. Absent when the console runs offline from a zip.       */
(function () {
  if (! window.REMOTOX) { return; }

  var saveTimer = null;
  var inFlight  = false;
  var pending   = false;

  function statusEl() {
    var el = document.getElementById('remotox-status');
    if (! el) {
      el = document.createElement('div');
      el.id = 'remotox-status';
      el.style.cssText = 'position:fixed;bottom:14px;left:14px;z-index:9999;font:12px/1.4 ui-monospace,monospace;' +
                         'padding:7px 11px;border-radius:8px;background:rgba(0,0,0,0.75);color:#fff;opacity:0;' +
                         'transition:opacity .2s;pointer-events:none;';
      document.body.appendChild(el);
    }
    return el;
  }

  function say(text, hold) {
    var el = statusEl();
    el.textContent = text;
    el.style.opacity = '1';
    if (! hold) { setTimeout(function () { el.style.opacity = '0'; }, 1800); }
  }

  /* Debounced: the console saves on every keystroke and every option click, and
     one request per keystroke would be both wasteful and slower than the typing. */
  window.scheduleServerSave = function () {
    if (saveTimer) { clearTimeout(saveTimer); }
    saveTimer = setTimeout(pushProgress, 1200);
  };

  function pushProgress() {
    if (inFlight) { pending = true; return; }

    var raw = null;
    try { raw = localStorage.getItem("annotation_progress__" + (window.TASK_DATA.meta.task_id || "task")); }
    catch (e) { return; }
    if (! raw) { return; }

    inFlight = true;

    fetch(window.REMOTOX.saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.REMOTOX.csrf, 'Accept': 'application/json' },
      body: JSON.stringify({ progress: JSON.parse(raw) })
    })
    .then(function (r) { if (! r.ok) { throw new Error(r.status); } return r.json(); })
    .then(function () { say('Saved'); })
    .catch(function () {
      /* Deliberately not alarming. The local copy is intact, so nothing is lost —
         the worker just cannot resume on another device until this succeeds. It
         retries on the next change, and again at submit. */
      say('Offline — saved on this device', true);
    })
    .finally(function () {
      inFlight = false;
      if (pending) { pending = false; scheduleServerSave(); }
    });
  }

  /* Returns a promise. It deliberately does not touch the screens: the console owns
     what the worker sees, and having both do it is how the done screen ended up
     showing over a failed request. */
  window.submitToServer = function (payload) {
    if (! payload) {
      return Promise.reject(new Error("Your answers could not be prepared. Please reload and try again."));
    }

    say("Submitting…", true);

    return fetch(window.REMOTOX.submitUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.REMOTOX.csrf,
        "Accept": "application/json"
      },
      body: JSON.stringify({ result: payload })
    })
    .then(function (r) {
      return r.json()
        .catch(function () { return {}; })
        .then(function (body) { return { status: r.status, ok: r.ok, body: body }; });
    })
    .then(function (res) {
      if (res.status === 419) {
        // Session expired mid-task. Says so plainly: "try again" would just fail again.
        throw new Error("Your session expired. Please reload the page and log in again — your answers are saved.");
      }

      if (! res.ok || ! res.body.ok) {
        var msg = res.body && res.body.error ? res.body.error : "The server rejected the submission.";
        if (res.body && res.body.missing && res.body.missing.length) {
          msg += " Unanswered: " + res.body.missing.join(", ");
        }
        throw new Error(msg);
      }

      say("Submitted", true);
      return res.body;
    });
  };

  /* Flush progress immediately, used by Save & Exit where a debounce would lose the
     last few seconds of typing on navigation. */
  window.pushProgressNow = function () { pushProgress(); };

  /* A final push on the way out, for the case where someone closes the tab
     between the last debounce and the timer firing. */
  window.addEventListener('beforeunload', function () {
    if (saveTimer) { clearTimeout(saveTimer); pushProgress(); }
  });
})();
</script>
</body>
</html>
