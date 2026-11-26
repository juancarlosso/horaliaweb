<style>
    .department-report-page { width:100%; }
    .department-report-filters { display:grid; grid-template-columns:minmax(0,4fr) minmax(0,1fr) minmax(0,1fr) auto; align-items:end; gap:1rem; width:100%; }
    .department-report-filter { min-width:0; }
    .department-report-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .department-report-filter .hr-input { width:100%; }
    .department-report-actions { display:flex; gap:.5rem; white-space:nowrap; }
    .department-report-actions a { text-decoration:none; }
    .department-report-summary { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.875rem; margin:1.25rem 0; }
    .department-report-widget { display:flex; align-items:flex-start; gap:.8rem; min-width:0; padding:1rem 1.05rem; border:1px solid var(--card-border); border-radius:.95rem; background:var(--card-bg); transition:transform .18s ease,box-shadow .18s ease; }
    a.department-report-widget { color:inherit; text-decoration:none; }
    .department-report-widget:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(27,35,72,.08); }
    .department-report-widget-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; flex:none; border-radius:.8rem; font-size:1rem; }
    .department-report-widget-value { color:var(--body-text); font-size:1.25rem; font-weight:900; line-height:1.15; }
    .department-report-widget-label { margin-top:.25rem; color:var(--body-text-muted); font-size:.68rem; font-weight:700; letter-spacing:.055em; text-transform:uppercase; }
    .department-report-widget-note { margin-top:.35rem; font-size:.72rem; font-weight:700; }
    .department-report-table { min-width:700px; }
    .department-report-table th,.department-report-table td { vertical-align:middle; }
    .department-report-table td:not(:first-child),.department-report-table th:not(:first-child) { text-align:right; }
    .department-report-name { color:var(--body-text); font-weight:700; }
    .department-report-count-link { color:var(--color-primary); font-weight:700; text-decoration:none; }
    .department-report-count-link:hover { text-decoration:underline; }
    .department-report-period { margin:.25rem 0 0; color:var(--body-text-muted); font-size:.8rem; }
    .department-report-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .department-report-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .department-report-empty p { margin:0; font-size:.845rem; }
    @media(max-width:1200px) { .department-report-summary { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:1050px) { .department-report-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .department-report-actions { grid-column:1 / -1; } .department-report-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:650px) { .department-report-filters,.department-report-summary { grid-template-columns:1fr; } .department-report-actions { grid-column:auto; } .department-report-widget { padding:.85rem .95rem; } }
</style>
