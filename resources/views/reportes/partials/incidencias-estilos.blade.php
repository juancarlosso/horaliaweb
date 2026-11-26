<style>
    .page-inner { max-width: none; }
    .ir-type-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .ir-type-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; }
    .ir-type {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .7rem .875rem;
        border: 1px solid var(--card-border);
        border-left: 3px solid var(--incidence-color);
        border-radius: .625rem;
        background: color-mix(in srgb, var(--incidence-color) 7%, var(--card-bg));
    }
    .ir-type label { display: flex; flex: 1; gap: .6rem; align-items: center; cursor: pointer; }
    .ir-type input { flex: none; width: 18px; height: 18px; margin: 0; accent-color: var(--incidence-color); }
    .ir-type label span { color: var(--body-text); font-size: .875rem; font-weight: 600; }
    .ir-info { border: 0; background: none; padding: 0; color: var(--incidence-color); }
    .ir-table { min-width: 780px; }
    .ir-table th, .ir-table td { vertical-align: middle; }
    .ir-empty { padding: 2.5rem 1rem; text-align: center; color: var(--body-text-muted); }
    .ir-empty h2 { font-size: 1rem; color: var(--body-text); }
    .ir-head, .ir-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .ir-head p { margin: .25rem 0 0; color: var(--body-text-muted); font-size: .8rem; }
    .ir-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--body-border); color: var(--body-text-muted); font-size: .8rem; }
    .ir-pagination { display: flex; align-items: center; gap: .55rem; }
    .ir-pagination [aria-disabled="true"] { pointer-events: none; opacity: .55; }
    .ir-loader { position: fixed; inset: 0; z-index: 25000; display: grid; place-items: center; background: rgba(17, 24, 39, .42); }
    .ir-loader[hidden] { display: none; }
    .ir-loader-card { display: flex; align-items: center; gap: .8rem; border-radius: .85rem; padding: 1rem 1.25rem; background: var(--card-bg); color: var(--body-text); font-weight: 650; }
    @media (max-width: 900px) { .ir-type-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) {
        .ir-type-grid { grid-template-columns: 1fr; }
        .ir-head, .ir-footer { align-items: flex-start; flex-direction: column; }
    }
</style>
