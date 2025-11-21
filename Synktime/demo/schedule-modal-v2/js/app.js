                                                                                                                                                                                                       /**
 * app.js
 * Inicialización y coordinación de la aplicación de demo
 */

import { ScheduleState, createShift } from './schedule-config.js';
import { Timeline } from './timeline.js';
import { Validator } from './validator.js';

class ScheduleModalApp {
    constructor() {
        this.state = new ScheduleState();
        this.timeline = null;
        this.validator = null;
        this.elements = {};
        
        this.init();
    }

    init() {
        this.cacheElements();
        this.initializeComponents();
        this.attachEventListeners();
        this.loadTestData();
        this.updateUI();
    }

    cacheElements() {
        this.elements = {
            employeeName: document.getElementById('employeeName'),
            dayButtons: document.querySelectorAll('.day-btn'),
            dayShortcuts: document.querySelectorAll('.day-shortcut'),
            addShiftBtn: document.getElementById('addShiftBtn'),
            saveBtn: document.getElementById('saveBtn'),
            cancelBtn: document.getElementById('cancelBtn'),
            shiftsList: document.getElementById('shiftsList'),
            validationList: document.getElementById('validationList'),
            validationSummary: document.querySelector('.validation-summary'),
            timelineContainer: document.getElementById('timelineContainer')
        };
    }

    initializeComponents() {
        this.timeline = new Timeline(this.elements.timelineContainer, this.state);
        this.validator = new Validator(this.state);
    }

    attachEventListeners() {
        this.elements.dayButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const day = parseInt(btn.dataset.day);
                this.selectDay(day);
            });
        });

        this.elements.dayShortcuts.forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action;
                this.handleDayShortcut(action);
            });
        });

        this.elements.addShiftBtn.addEventListener('click', () => {
            this.addDefaultShift();
        });

        this.elements.saveBtn.addEventListener('click', () => {
            this.save();
        });

        this.elements.cancelBtn.addEventListener('click', () => {
            this.cancel();
        });

        window.addEventListener('shiftsChanged', () => {
            this.onShiftsChanged();
        });
    }

    loadTestData() {
        this.state.setEmployee(100, 'Juan Pérez Demo');
        
        this.state.addShift(1, createShift('08:00', '17:00', 'regular'));
        this.state.addShift(2, createShift('08:00', '17:00', 'regular'));
        this.state.addShift(3, createShift('22:00', '06:00', 'night'));
        this.state.addShift(4, createShift('14:00', '22:00', 'rotative'));
        this.state.addShift(5, createShift('08:00', '12:00', 'regular'));
        this.state.addShift(5, createShift('14:00', '18:00', 'regular'));

        this.elements.employeeName.textContent = this.state.employeeName;
    }

    selectDay(day) {
        this.state.setCurrentDay(day);
        
        this.elements.dayButtons.forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.day) === day);
        });

        this.timeline.update();
        this.updateShiftsList();
    }

    handleDayShortcut(action) {
        const currentDay = this.state.currentDay;
        let newDay = currentDay;

        switch (action) {
            case 'prev':
                newDay = currentDay > 1 ? currentDay - 1 : 7;
                break;
            case 'next':
                newDay = currentDay < 7 ? currentDay + 1 : 1;
                break;
            case 'weekdays':
                this.applyToWeekdays();
                return;
            case 'clear':
                this.clearCurrentDay();
                return;
        }

        this.selectDay(newDay);
    }

    applyToWeekdays() {
        const currentShifts = this.state.getShiftsForDay(this.state.currentDay);
        
        if (currentShifts.length === 0) {
            alert('No hay turnos en el día actual para copiar');
            return;
        }

        if (!confirm('¿Aplicar los turnos del día actual a todos los días de semana (Lunes a Viernes)?')) {
            return;
        }

        for (let day = 1; day <= 5; day++) {
            if (day === this.state.currentDay) continue;
            
            this.state.shifts.set(day, []);
            
            currentShifts.forEach(shift => {
                this.state.addShift(day, { ...shift, id: Date.now() + Math.random() });
            });
        }

        this.onShiftsChanged();
        alert('Turnos aplicados a días de semana');
    }

    clearCurrentDay() {
        if (!confirm('¿Eliminar todos los turnos del día actual?')) {
            return;
        }

        this.state.shifts.set(this.state.currentDay, []);
        this.onShiftsChanged();
    }

    addDefaultShift() {
        const shift = createShift('09:00', '17:00', 'regular');
        this.state.addShift(this.state.currentDay, shift);
        this.onShiftsChanged();
    }

    updateShiftsList() {
        const shifts = this.state.getShiftsForDay(this.state.currentDay);
        
        if (shifts.length === 0) {
            this.elements.shiftsList.innerHTML = '<div class="no-shifts">No hay turnos asignados para este día. Haz doble clic en el timeline para agregar uno.</div>';
            return;
        }

        const html = shifts.map(shift => `
            <div class="shift-item shift-${shift.shiftType}" data-shift-id="${shift.id}">
                <div class="shift-item-time">
                    <strong>${shift.startTime}</strong> - <strong>${shift.endTime}</strong>
                </div>
                <div class="shift-item-type">
                    <span class="badge badge-${shift.shiftType}">${this.getShiftTypeName(shift.shiftType)}</span>
                </div>
                <button class="btn-icon delete-shift" data-shift-id="${shift.id}" title="Eliminar turno">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `).join('');

        this.elements.shiftsList.innerHTML = html;

        this.elements.shiftsList.querySelectorAll('.delete-shift').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const shiftId = parseFloat(btn.dataset.shiftId);
                this.deleteShift(shiftId);
            });
        });
    }

    deleteShift(shiftId) {
        this.state.removeShift(this.state.currentDay, shiftId);
        this.onShiftsChanged();
    }

    onShiftsChanged() {
        this.validator.validateAll();
        this.timeline.update();
        this.updateShiftsList();
        this.updateValidation();
    }

    updateValidation() {
        const summary = this.validator.getValidationSummary();
        const errors = this.state.validationErrors;

        const summaryHtml = `
            <div class="summary-item">
                <span class="summary-label">Horas semanales:</span>
                <span class="summary-value ${summary.weeklyHours > 48 ? 'text-danger' : ''}">${summary.weeklyHours.toFixed(1)}h</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Errores:</span>
                <span class="summary-value ${summary.byType.error > 0 ? 'text-danger' : 'text-success'}">${summary.byType.error}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Advertencias:</span>
                <span class="summary-value ${summary.byType.warning > 0 ? 'text-warning' : 'text-success'}">${summary.byType.warning}</span>
            </div>
        `;
        this.elements.validationSummary.innerHTML = summaryHtml;

        if (errors.length === 0) {
            this.elements.validationList.innerHTML = '<div class="validation-success">✓ No hay problemas de validación</div>';
        } else {
            const html = errors.map(error => `
                <div class="validation-item validation-${error.type}">
                    <div class="validation-icon">
                        ${error.type === 'error' ? '⚠' : 'ℹ'}
                    </div>
                    <div class="validation-message">${error.message}</div>
                </div>
            `).join('');
            this.elements.validationList.innerHTML = html;
        }
    }

    getShiftTypeName(type) {
        const names = {
            'regular': 'Regular',
            'night': 'Nocturno',
            'rotative': 'Rotativo'
        };
        return names[type] || type;
    }

    async save() {
        const errors = this.validator.validateAll();
        const hasErrors = errors.filter(e => e.type === 'error').length > 0;

        if (hasErrors) {
            if (!confirm('Hay errores de validación. ¿Desea guardar de todas formas?')) {
                return;
            }
        }

        const data = this.state.toJSON();
        console.log('Guardando configuración:', data);

        this.elements.saveBtn.disabled = true;
        this.elements.saveBtn.textContent = 'Guardando...';

        await new Promise(resolve => setTimeout(resolve, 1000));

        this.elements.saveBtn.disabled = false;
        this.elements.saveBtn.textContent = 'Guardar Configuración';

        alert('✓ Configuración guardada exitosamente\n\nRevisa la consola para ver los datos enviados.');
        this.state.isDirty = false;
    }

    cancel() {
        if (this.state.isDirty) {
            if (!confirm('Hay cambios sin guardar. ¿Desea salir de todas formas?')) {
                return;
            }
        }

        alert('Modal cerrado (en la implementación real, esto cerraría el modal)');
    }

    updateUI() {
        this.selectDay(1);
        this.validator.validateAll();
        this.updateValidation();
    }
}

// Inicializar cuando se abre el modal
function initScheduleApp() {
    if (!window.scheduleApp) {
        window.scheduleApp = new ScheduleModalApp();
    }
}

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScheduleApp);
} else {
    // DOM ya está listo
    initScheduleApp();
}

// Exportar para uso externo si es necesario
export { ScheduleModalApp, initScheduleApp };
