(function () {
    'use strict';

    const statusLabels = {
        online: 'Disponible',
        maintenance: 'En mantenimiento',
        offline: 'Fuera de línea',
        'coming-soon': 'Próximamente'
    };

    const statusClasses = ['status-online', 'status-maintenance', 'status-offline', 'status-coming-soon'];

    const systems = Array.isArray(window.PORTAL_SYSTEMS) ? window.PORTAL_SYSTEMS : [];
    if (!systems.length) {
        return;
    }

    const normalizeStatus = (value) => {
        return statusLabels[value] ? value : 'online';
    };

    const updateStatus = (card, newStatus) => {
        const statusWrapper = card.querySelector('.portal-card-status');
        if (!statusWrapper) {
            return;
        }

        const statusLabel = statusWrapper.querySelector('span:last-child');
        const normalized = normalizeStatus(newStatus);

        statusClasses.forEach((cls) => statusWrapper.classList.remove(cls));
        statusWrapper.classList.add(`status-${normalized}`);

        if (statusLabel) {
            statusLabel.textContent = statusLabels[normalized];
        }

        card.dataset.status = normalized;
    };

    const pingHealth = (system) => {
        if (!system || !system.healthUrl) {
            return;
        }

        const card = document.querySelector(`[data-system="${CSS.escape(system.key)}"]`);
        if (!card) {
            return;
        }

        const initialStatus = normalizeStatus(card.dataset.status);
        if (initialStatus !== 'online') {
            return; // Respetar el estado configurado manualmente
        }

        const controller = new AbortController();
        const timeoutMs = Number.isFinite(system.timeout) && system.timeout > 0 ? system.timeout : 4000;
        const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);

        fetch(system.healthUrl, {
            method: 'GET',
            cache: 'no-store',
            redirect: 'follow',
            signal: controller.signal
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Unexpected status ${response.status}`);
                }
                return response;
            })
            .then(() => {
                updateStatus(card, 'online');
            })
            .catch(() => {
                updateStatus(card, 'offline');
            })
            .finally(() => {
                window.clearTimeout(timeoutId);
            });
    };

    window.addEventListener('DOMContentLoaded', () => {
        systems.forEach((system) => {
            try {
                pingHealth(system);
            } catch (error) {
                console.error('[portal] health check error:', error);
            }
        });
    });
})();
