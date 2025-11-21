/**
 * validator.js
 * Validación automática y detección de tipo de turno
 */

import { CONFIG, timeToMinutes } from './schedule-config.js';

export class Validator {
    constructor(state) {
        this.state = state;
    }

    validateAll() {
        const errors = [];
        const shifts = this.state.getAllShifts();

        shifts.forEach(shift => {
            const detectedType = this.detectShiftType(shift.startTime, shift.endTime);
            if (shift.shiftType !== detectedType) {
                this.state.updateShift(shift.dayOfWeek, shift.id, {
                    shiftType: detectedType
                });
            }
        });

        for (let day = 1; day <= 7; day++) {
            const dayShifts = this.state.getShiftsForDay(day);
            const dayErrors = this.validateDay(day, dayShifts);
            errors.push(...dayErrors);
        }

        const weeklyHours = this.calculateWeeklyHours();
        if (weeklyHours > 48) {
            errors.push({
                type: 'warning',
                message: `Total semanal: ${weeklyHours.toFixed(1)}h excede las 48h permitidas`,
                severity: 'high'
            });
        }

        this.state.setValidationErrors(errors);
        return errors;
    }

    detectShiftType(startTime, endTime) {
        const startMinutes = timeToMinutes(startTime);
        const endMinutes = timeToMinutes(endTime);
        const startHour = Math.floor(startMinutes / 60);
        const endHour = Math.floor(endMinutes / 60);

        const nightStart = CONFIG.SHIFT_TYPES.NIGHT.minHour;
        const nightEnd = CONFIG.SHIFT_TYPES.NIGHT.maxHour;

        const isNightStart = startHour >= nightStart || startHour < nightEnd;
        const isNightEnd = endHour <= nightEnd || endHour >= nightStart;
        const crossesMidnight = endMinutes < startMinutes;

        if (isNightStart && (isNightEnd || crossesMidnight)) {
            return 'night';
        }

        const regularStart = CONFIG.SHIFT_TYPES.REGULAR.minHour;
        const regularEnd = CONFIG.SHIFT_TYPES.REGULAR.maxHour;

        const startsBeforeRegular = startHour < regularStart;
        const endsAfterRegular = endHour > regularEnd;
        const spansMultipleShifts = (endMinutes - startMinutes) > (12 * 60);

        if (spansMultipleShifts || (startsBeforeRegular && endsAfterRegular)) {
            return 'rotative';
        }

        return 'regular';
    }

    validateDay(dayOfWeek, shifts) {
        const errors = [];
        const dayNames = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        if (shifts.length === 0) {
            return errors;
        }

        const sortedShifts = [...shifts].sort((a, b) => {
            return timeToMinutes(a.startTime) - timeToMinutes(b.startTime);
        });

        for (let i = 0; i < sortedShifts.length - 1; i++) {
            const current = sortedShifts[i];
            const next = sortedShifts[i + 1];

            if (this.shiftsOverlap(current, next)) {
                errors.push({
                    type: 'error',
                    message: `${dayNames[dayOfWeek]}: Turnos solapados (${current.startTime}-${current.endTime} y ${next.startTime}-${next.endTime})`,
                    severity: 'high',
                    dayOfWeek
                });
            }
        }

        shifts.forEach(shift => {
            const duration = this.calculateDuration(shift.startTime, shift.endTime);
            if (duration < CONFIG.MIN_DURATION) {
                errors.push({
                    type: 'error',
                    message: `${dayNames[dayOfWeek]}: Turno ${shift.startTime}-${shift.endTime} dura menos de ${CONFIG.MIN_DURATION} minutos`,
                    severity: 'medium',
                    dayOfWeek
                });
            }

            if (shift.shiftType === 'regular' && duration > 720) {
                errors.push({
                    type: 'warning',
                    message: `${dayNames[dayOfWeek]}: Turno regular ${shift.startTime}-${shift.endTime} excede 12 horas`,
                    severity: 'medium',
                    dayOfWeek
                });
            }
        });

        const dailyMinutes = shifts.reduce((total, shift) => {
            return total + this.calculateDuration(shift.startTime, shift.endTime);
        }, 0);

        const dailyHours = dailyMinutes / 60;
        if (dailyHours > 12) {
            errors.push({
                type: 'warning',
                message: `${dayNames[dayOfWeek]}: Total diario ${dailyHours.toFixed(1)}h excede las 12h recomendadas`,
                severity: 'medium',
                dayOfWeek
            });
        }

        return errors;
    }

    shiftsOverlap(shift1, shift2) {
        const start1 = timeToMinutes(shift1.startTime);
        const end1 = timeToMinutes(shift1.endTime);
        const start2 = timeToMinutes(shift2.startTime);
        const end2 = timeToMinutes(shift2.endTime);

        if (end1 > start1 && end2 > start2) {
            return start1 < end2 && start2 < end1;
        }

        if (end1 < start1) {
            return start2 < end1 || start2 >= start1;
        }

        if (end2 < start2) {
            return start1 < end2 || start1 >= start2;
        }

        return false;
    }

    calculateDuration(startTime, endTime) {
        const start = timeToMinutes(startTime);
        const end = timeToMinutes(endTime);
        
        if (end >= start) {
            return end - start;
        } else {
            return (1440 - start) + end;
        }
    }

    calculateWeeklyHours() {
        let totalMinutes = 0;

        for (let day = 1; day <= 7; day++) {
            const shifts = this.state.getShiftsForDay(day);
            shifts.forEach(shift => {
                totalMinutes += this.calculateDuration(shift.startTime, shift.endTime);
            });
        }

        return totalMinutes / 60;
    }

    getValidationSummary() {
        const errors = this.state.validationErrors;
        const summary = {
            total: errors.length,
            byType: {
                error: errors.filter(e => e.type === 'error').length,
                warning: errors.filter(e => e.type === 'warning').length
            },
            bySeverity: {
                high: errors.filter(e => e.severity === 'high').length,
                medium: errors.filter(e => e.severity === 'medium').length,
                low: errors.filter(e => e.severity === 'low').length
            },
            weeklyHours: this.calculateWeeklyHours()
        };

        return summary;
    }
}
