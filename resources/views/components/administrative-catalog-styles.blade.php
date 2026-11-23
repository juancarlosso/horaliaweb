@once
<style>
    .page-inner { max-width:none; }
    .catalog-page { width:100%; }
    .catalog-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem 1rem; margin-bottom:1rem; }
    .catalog-filter { width:min(100%, 420px); flex:1 1 280px; }
    .catalog-filter label { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    .catalog-filter select { width:100%; }
    .catalog-toolbar-action { margin-left:auto; white-space:nowrap; }
    .catalog-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .catalog-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .catalog-actions form { margin:0; }
    .catalog-actions .btn-hr { text-decoration:none; }
    .vacation-info-button { display:inline-grid; place-items:center; width:2rem; height:2rem; padding:0; border:0; border-radius:.5rem; color:#2563eb; background:transparent; cursor:pointer; }
    .vacation-info-button:hover,.vacation-info-button:focus-visible { color:#1d4ed8; background:rgba(37,99,235,.1); }
    .vacation-info-button i { width:auto; min-width:0; font-size:1.1rem; }
    .vacation-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem 1.25rem; margin-bottom:1rem; text-align:left; }
    .vacation-info-item { display:flex; min-width:0; flex-direction:column; gap:.12rem; }
    .vacation-info-item strong { color:#344054; font-size:.78rem; }
    .vacation-info-item span { overflow-wrap:anywhere; color:#101828; font-size:.88rem; }
    .catalog-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .catalog-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .catalog-empty p { margin:0 0 1rem; font-size:.845rem; }
    @media(max-width:600px) { .vacation-info-grid { grid-template-columns:1fr; gap:.55rem; } }
    @media(max-width:700px) { .catalog-toolbar { align-items:stretch; flex-direction:column; } }
</style>
@endonce
