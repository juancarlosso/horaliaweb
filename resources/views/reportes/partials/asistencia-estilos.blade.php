<style>
    .page-inner { max-width:none; }
    .attendance-report-page { width:100%; }
    .report-filters { display:grid; grid-template-columns:minmax(0,4fr) minmax(0,1fr) minmax(0,1fr) auto; align-items:end; gap:1rem; width:100%; }
    .report-filter { min-width:0; }
    .report-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .report-filter .hr-input { width:100%; }
    .report-form-actions { display:flex; gap:.5rem; white-space:nowrap; }
    .report-form-actions .btn-hr,.report-export-btn { text-decoration:none; }
    .report-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin:1.25rem 0; }
    .report-summary-card { display:flex; align-items:center; gap:.9rem; min-height:102px; padding:1rem 1.1rem; border:1px solid var(--card-border); border-radius:.875rem; background:var(--card-bg); box-shadow:0 4px 14px rgba(27,35,72,.035); }
    .report-summary-icon { display:grid; place-items:center; width:42px; height:42px; flex:none; border-radius:.75rem; color:#536df5; background:rgba(83,109,245,.1); font-size:1rem; }
    .report-summary-icon.is-late { color:#c78300; background:rgba(245,158,11,.13); }
    .report-summary-icon.is-range { color:#dc3545; background:rgba(239,68,68,.1); }
    .report-summary-icon.is-exit { color:#0891b2; background:rgba(6,182,212,.1); }
    .report-summary-copy small { display:block; margin-bottom:.2rem; color:var(--body-text-muted); font-size:.78rem; }
    .report-summary-copy strong { color:var(--body-text); font-size:1.25rem; font-weight:800; }
    .report-card-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .report-card-heading p { margin:.25rem 0 0; color:var(--body-text-muted); font-size:.8rem; }
    .report-table { min-width:1450px; }
    .report-table th,.report-table td { vertical-align:middle; }
    .report-employee { min-width:180px; }
    .report-employee strong { display:block; color:var(--body-text); font-weight:700; }
    .report-employee small { display:block; margin-top:.2rem; color:var(--body-text-muted); }
    .report-status { display:inline-flex; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.73rem; font-weight:700; white-space:nowrap; }
    .report-status.is-ok { color:#087f5b; background:rgba(16,185,129,.1); }
    .report-status.is-bad { color:#b42318; background:rgba(239,68,68,.08); }
    .report-minute { white-space:nowrap; }
    .report-evidence { display:flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .report-icon-action { display:inline-grid; place-items:center; width:28px; height:28px; padding:0; border:1px solid var(--body-border); border-radius:.45rem; color:var(--body-text-muted); background:var(--card-bg); text-decoration:none !important; }
    .report-icon-action:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .report-icon-action i { font-size:.8rem; }
    .report-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .report-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .report-empty p { margin:0; font-size:.845rem; }
    .report-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .report-pagination { display:flex; align-items:center; gap:.55rem; }
    .report-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    .attendance-loader { position:fixed; inset:0; z-index:25000; display:grid; place-items:center; padding:1rem; background:rgba(17,24,39,.42); }
    .attendance-loader[hidden] { display:none; }
    .attendance-loader-card { display:flex; align-items:center; gap:.8rem; padding:1rem 1.25rem; border:1px solid var(--card-border); border-radius:.85rem; color:var(--body-text); background:var(--card-bg); box-shadow:0 12px 40px rgba(17,24,39,.2); font-weight:650; }
    .attendance-loader-spinner { width:1.35rem; height:1.35rem; border:3px solid var(--body-border); border-top-color:var(--color-primary); border-radius:50%; animation:attendance-loader-spin .7s linear infinite; }
    @keyframes attendance-loader-spin { to { transform:rotate(360deg); } }
    .report-photo-modal .modal-content { border:1px solid var(--card-border); border-radius:1rem; color:var(--body-text); background:var(--card-bg); }
    .report-photo-modal .modal-header,.report-photo-modal .modal-footer { border-color:var(--body-border); }
    .report-photo-modal .modal-body { background:var(--body-bg); text-align:center; }
    .report-photo-modal img { max-width:100%; max-height:70vh; border-radius:.65rem; }
    @media(max-width:1050px) { .report-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .report-form-actions { grid-column:1 / -1; } .report-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:650px) { .report-filters,.report-summary { grid-template-columns:1fr; } .report-form-actions { grid-column:auto; } .report-card-heading,.report-footer { align-items:flex-start; flex-direction:column; } }
</style>
