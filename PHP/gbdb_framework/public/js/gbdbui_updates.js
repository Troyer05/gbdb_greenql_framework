// Framework-Updates: Phasen nacheinander ausführen (jede Phase eine eigene Anfrage).
(() => {
    const panel = document.querySelector('[data-update-job="running"]');

    if (!panel) {
        return;
    }

    const progress = panel.querySelector('[data-update-progress]');
    const url = panel.dataset.updateUrl;
    const csrf = panel.dataset.csrf;

    const step = async () => {
        const body = new URLSearchParams({ update_action: 'step', ajax: '1', csrf });
        let result;

        try {
            const response = await fetch(url, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            result = await response.json();
        } catch (error) {
            // Wartungsmodus oder Verbindungsabbruch: Seite neu laden zeigt den tatsächlichen Stand.
            window.setTimeout(() => window.location.reload(), 2000);

            return;
        }

        if (!result.ok) {
            window.location.reload();

            return;
        }

        if (result.status === 'running') {
            if (progress && result.label) {
                progress.textContent = 'Wird ausgeführt: ' + result.label + ' …';
            }

            step();

            return;
        }

        if (result.status === 'verify_pending') {
            if (progress) {
                progress.textContent = 'Dateien installiert – Selbstprüfung mit dem neuen Code …';
            }

            window.location.href = panel.dataset.finishUrl;

            return;
        }

        window.location.reload();
    };

    step();
})();
