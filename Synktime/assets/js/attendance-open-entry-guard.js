(function () {
    'use strict';

    const CHECK_URL = 'api/attendance/check-open-entries.php';
    const POLL_INTERVAL_MS = 60_000; // 1 minuto
    const CACHE_MS = 15_000; // reutilizar resultados recientes para evitar peticiones redundantes

    const state = {
        lastResult: null,
        lastCheckedAt: 0,
        hasOpenEntries: false,
        pollingIntervalId: null,
        pendingLogoutHref: null,
        skipUnloadPrompt: false,
        modalElement: null,
        modalInstance: null,
        inFlightPromise: null,
        activeLogoutLink: null,
    };

    function cleanupActiveLogoutLink() {
        if (!state.activeLogoutLink) {
            return;
        }
        delete state.activeLogoutLink.dataset.pendingLogout;
        state.activeLogoutLink.classList.remove('disabled', 'opacity-75', 'pe-none');
        state.activeLogoutLink = null;
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getModalInstance() {
        if (state.modalInstance) {
            return state.modalInstance;
        }

        const modalElement = document.getElementById('openEntriesWarningModal');
        if (!modalElement) {
            return null;
        }

        state.modalElement = modalElement;

        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.warn('attendance-open-entry-guard: Bootstrap Modal no disponible. Se utilizará confirmación nativa.');
            return null;
        }

        state.modalInstance = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true,
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            state.pendingLogoutHref = null;
            cleanupActiveLogoutLink();
        });

        const confirmButton = modalElement.querySelector('#confirmLogoutWithOpenEntries');
        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                if (!state.pendingLogoutHref) {
                    state.modalInstance?.hide();
                    return;
                }

                state.modalInstance.hide();
                proceedWithLogout(state.pendingLogoutHref);
            });
        }

        return state.modalInstance;
    }

    function setModalError(message) {
        if (!state.modalElement) {
            return;
        }
        const errorBox = state.modalElement.querySelector('#openEntriesWarningError');
        if (!errorBox) {
            return;
        }
        if (message) {
            errorBox.textContent = message;
            errorBox.classList.remove('d-none');
        } else {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }
    }

    function renderModalContent(result) {
        if (!state.modalElement) {
            return;
        }

        const summary = state.modalElement.querySelector('#openEntriesWarningSummary');
        const list = state.modalElement.querySelector('#openEntriesWarningList');

        if (!summary || !list) {
            return;
        }

        const total = result?.open_entries?.total ?? 0;
        const entries = Array.isArray(result?.open_entries?.entries)
            ? result.open_entries.entries
            : [];

        summary.textContent = total === 1
            ? 'Se encontró 1 registro de entrada sin salida registrada.'
            : `Se detectaron ${total} registros de entrada sin salida registrada.`;

        if (!entries.length) {
            list.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle me-2"></i>No se pudieron obtener detalles de los registros abiertos.
                    </td>
                </tr>
            `;
            return;
        }

        const rows = entries.map((entry) => {
            const empleado = entry.nombre_completo || 'Empleado sin nombre';
            const codigo = entry.id_empleado ?? 'N/D';
            const establecimiento = entry.establecimiento || '—';
            const sede = entry.sede || '—';
            const fecha = entry.fecha || '—';
            const hora = entry.hora || '—';

            return `
                <tr>
                    <td>${escapeHtml(empleado)}</td>
                    <td>${escapeHtml(codigo)}</td>
                    <td>${escapeHtml(establecimiento)}</td>
                    <td>${escapeHtml(sede)}</td>
                    <td>${escapeHtml(fecha)}</td>
                    <td>${escapeHtml(hora)}</td>
                </tr>
            `;
        }).join('');

        let extraRow = '';
        if (total > entries.length) {
            const restantes = total - entries.length;
            extraRow = `
                <tr>
                    <td colspan="6" class="text-center text-muted small">
                        Se muestran los primeros ${entries.length} registros. Quedan ${restantes} adicionales pendientes por revisar.
                    </td>
                </tr>
            `;
        }

        list.innerHTML = rows + extraRow;
    }

    function requestOpenEntries(options = {}) {
        const { force = false } = options;
        const now = Date.now();

        if (!force && state.inFlightPromise) {
            return state.inFlightPromise;
        }

        if (!force && state.lastResult && now - state.lastCheckedAt < CACHE_MS) {
            return Promise.resolve(state.lastResult);
        }

        const controller = new AbortController();
        const fetchPromise = fetch(CHECK_URL, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (response.redirected) {
                    // Sesión expirada: permitir que la navegación continúe
                    window.location.href = response.url;
                    return Promise.reject(new Error('Sesión expirada'));
                }

                if (!response.ok) {
                    throw new Error(`Error ${response.status} al consultar entradas abiertas`);
                }

                return response.json();
            })
            .then((data) => {
                if (!data || data.success === false) {
                    const message = data?.message || 'No fue posible validar las entradas abiertas.';
                    throw new Error(message);
                }

                state.lastResult = data;
                state.lastCheckedAt = Date.now();
                state.hasOpenEntries = (data.open_entries?.total ?? 0) > 0;
                setModalError('');
                return data;
            })
            .catch((error) => {
                console.error('attendance-open-entry-guard: error al consultar entradas abiertas', error);
                setModalError(error.message);
                throw error;
            })
            .finally(() => {
                state.inFlightPromise = null;
            });

        state.inFlightPromise = fetchPromise;
        return fetchPromise;
    }

    function proceedWithLogout(url) {
        if (!url) {
            return;
        }
        state.skipUnloadPrompt = true;
        state.pendingLogoutHref = null;
        cleanupActiveLogoutLink();
        window.location.href = url;
    }

    function handleLogoutClick(event) {
        const link = event.currentTarget;
        const targetUrl = link.getAttribute('href');
        if (!targetUrl) {
            return;
        }

        event.preventDefault();

        if (link.dataset.pendingLogout === 'true') {
            return;
        }

        link.dataset.pendingLogout = 'true';
        link.classList.add('disabled', 'opacity-75', 'pe-none');
        state.activeLogoutLink = link;
        state.pendingLogoutHref = targetUrl;

        requestOpenEntries({ force: true })
            .then((result) => {
                const total = result?.open_entries?.total ?? 0;
                if (total > 0) {
                    const modal = getModalInstance();
                    if (modal) {
                        renderModalContent(result);
                        modal.show();
                    } else {
                        const confirmLeave = window.confirm(
                            `Se detectaron ${total} registros de entrada sin salida.\n¿Deseas cerrar sesión de todas formas?`
                        );
                        if (confirmLeave) {
                            proceedWithLogout(targetUrl);
                        }
                    }
                } else {
                    proceedWithLogout(targetUrl);
                }
            })
            .catch((error) => {
                console.error('attendance-open-entry-guard: no se pudo validar antes de cerrar sesión', error);
                const fallback = window.confirm('No fue posible comprobar si existen entradas abiertas. ¿Deseas cerrar sesión igualmente?');
                if (fallback) {
                    proceedWithLogout(targetUrl);
                } else {
                    state.pendingLogoutHref = null;
                    cleanupActiveLogoutLink();
                }
            })
            .finally(() => {
                if (!state.pendingLogoutHref) {
                    cleanupActiveLogoutLink();
                }
            });
    }

    function onBeforeUnload(event) {
        // Maneja el evento de cerrar la ventana del navegador cuando hay entradas abiertas
        // Muestra un mensaje personalizado con detalles de las entradas sin salida registrada
        if (state.skipUnloadPrompt) {
            return;
        }

        if (!state.hasOpenEntries || !state.lastResult) {
            return;
        }

        event.preventDefault();

        // Construir mensaje detallado con información de las entradas abiertas
        const total = state.lastResult.open_entries?.total ?? 0;
        const entries = Array.isArray(state.lastResult.open_entries?.entries)
            ? state.lastResult.open_entries.entries
            : [];

        let message = `ATENCIÓN: Hay ${total} registro(s) de entrada sin salida registrada en el sistema de asistencia. `;

        if (entries.length > 0) {
            const firstEntry = entries[0];
            const empleado = firstEntry.nombre_completo || 'Empleado sin nombre';
            const fecha = firstEntry.fecha || 'fecha desconocida';
            const hora = firstEntry.hora || 'hora desconocida';

            message += `Ejemplo: ${empleado} registró entrada el ${fecha} a las ${hora}. `;
        }

        message += 'Si cierras esta página, podrías perder estos registros. ¿Confirmas que deseas salir?';

        // Asegurar que el mensaje no exceda el límite recomendado para beforeunload (alrededor de 200 caracteres)
        if (message.length > 180) {
            message = `Hay ${total} entrada(s) sin salida registrada. Si cierras la página, podrías perder estos registros. ¿Salir de todos modos?`;
        }

        event.returnValue = message;
        return message;
    }

    function startPolling() {
        requestOpenEntries({ force: true }).catch(() => {
            /* se manejará mediante modal/error */
        });

        if (state.pollingIntervalId !== null) {
            clearInterval(state.pollingIntervalId);
        }

        state.pollingIntervalId = setInterval(() => {
            if (document.hidden) {
                return;
            }
            requestOpenEntries().catch(() => {
                /* errores ya gestionados en requestOpenEntries */
            });
        }, POLL_INTERVAL_MS);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                requestOpenEntries({ force: true }).catch(() => {
                    /* manejo ya realizado */
                });
            }
        });
    }

    function init() {
        const logoutLinks = document.querySelectorAll('a.logout-btn');
        if (logoutLinks.length === 0) {
            return;
        }

        logoutLinks.forEach((link) => {
            link.addEventListener('click', handleLogoutClick, { passive: false });
        });

        window.addEventListener('beforeunload', onBeforeUnload, { passive: false });

        startPolling();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
