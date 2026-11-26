const result = document.getElementById('hours-report-result');

if (result?.querySelector('[data-report-pending]')) {
    const check = async () => {
        if (!document.hidden) {
            try {
                const response = await fetch(result.dataset.statusUrl, {
                    headers: { 'X-Report-Fragment': '1', 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if ([401, 403, 404, 419].includes(response.status) || response.redirected) {
                    window.location.reload();
                    return;
                }
                if (response.ok) {
                    const fragment = document.createElement('template');
                    fragment.innerHTML = await response.text();
                    const pending = fragment.content.querySelector('[data-report-pending]');
                    if (!pending && result.hasAttribute('data-reload-ready')) {
                        window.location.reload();
                        return;
                    }
                    if (!pending || pending.textContent.trim() !== result.querySelector('[data-report-pending]').textContent.trim()) {
                        result.replaceChildren(fragment.content);
                    }
                    if (!pending) return;
                }
            } catch {
                // Retry when the connection comes back.
            }
        }
        window.setTimeout(check, 4000);
    };

    window.setTimeout(check, 4000);
}
