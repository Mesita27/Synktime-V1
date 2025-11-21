/**
 * schedule-config.js
 * Configuración global y gestión del estado para el modal de horarios
 */

export const CONFIG = {
    SNAP_INTERVAL: 30, // minutos
    MIN_DURATION: 30,  // minutos mínimos de un turno
    HOURS_START: 0,
    HOURS_END: 24,
    INTERVALS_PER_HOUR: 2, // Para 30 min
    
    // Límites para clasificación de turnos
    SHIFT_TYPES: {
        REGULAR: {
            name: "Regular",
            minHour: 6,
            maxHour: 18,
            color: "#3b82f6"
        },
        NIGHT: {
            name: "Nocturno",
            minHour: 22,
            maxHour: 6, // Cruce de medianoche
            color: "#8b5cf6"
        },
        ROTATIVE: {
            name: "Rotativo",
            color: "#f59e0b"
        }
    }
};

export class ScheduleState {
    constructor() {
        this.employeeId = null;
        this.employeeName = "Juan Pérez";
        this.shifts = new Map(); // key: dayOfWeek
        this.currentDay = 1; // Lunes por defecto
        this.validationErrors = [];
        this.isDirty = false;
    }

    setEmployee(id, name) {
        this.employeeId = id;
        this.employeeName = name;
    }

    addShift(dayOfWeek, shift) {
        if (!this.shifts.has(dayOfWeek)) {
            this.shifts.set(dayOfWeek, []);
        }
        this.shifts.get(dayOfWeek).push(shift);
        this.isDirty = true;
    }

    removeShift(dayOfWeek, shiftId) {
        if (this.shifts.has(dayOfWeek)) {
            const shifts = this.shifts.get(dayOfWeek);
            const index = shifts.findIndex(s => s.id === shiftId);
            if (index !== -1) {
                shifts.splice(index, 1);
                this.isDirty = true;
            }
        }
    }

    updateShift(dayOfWeek, shiftId, updates) {
        if (this.shifts.has(dayOfWeek)) {
            const shift = this.shifts.get(dayOfWeek).find(s => s.id === shiftId);
            if (shift) {
                Object.assign(shift, updates);
                this.isDirty = true;
            }
        }
    }

    getShiftsForDay(dayOfWeek) {
        return this.shifts.get(dayOfWeek) || [];
    }

    getAllShifts() {
        const all = [];
        this.shifts.forEach((shifts, day) => {
            shifts.forEach(shift => {
                all.push({ ...shift, dayOfWeek: day });
            });
        });
        return all;
    }

    setCurrentDay(day) {
        this.currentDay = day;
    }

    setValidationErrors(errors) {
        this.validationErrors = errors;
    }

    hasErrors() {
        return this.validationErrors.length > 0;
    }

    reset() {
        this.shifts.clear();
        this.validationErrors = [];
        this.isDirty = false;
    }

    toJSON() {
        const data = {
            employeeId: this.employeeId,
            employeeName: this.employeeName,
            schedules: []
        };

        this.shifts.forEach((shifts, dayOfWeek) => {
            shifts.forEach(shift => {
                data.schedules.push({
                    dayOfWeek,
                    startTime: shift.startTime,
                    endTime: shift.endTime,
                    shiftType: shift.shiftType
                });
            });
        });

        return data;
    }
}

export function createShift(startTime, endTime, shiftType = null) {
    return {
        id: Date.now() + Math.random(),
        startTime,
        endTime,
        shiftType: shiftType || "regular",
        isNew: true
    };
}

export function timeToMinutes(time) {
    const [hours, minutes] = time.split(":").map(Number);
    return hours * 60 + minutes;
}

export function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60) % 24;
    const mins = minutes % 60;
    return \`\${String(hours).padStart(2, "0")}:\${String(mins).padStart(2, "0")}\`;
}

export function snapToInterval(minutes, interval = CONFIG.SNAP_INTERVAL) {
    return Math.round(minutes / interval) * interval;
}
