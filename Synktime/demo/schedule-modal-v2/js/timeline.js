/**
 * timeline.js
 * Gestión del timeline visual y drag & drop de turnos
 */

import { CONFIG, timeToMinutes, minutesToTime, snapToInterval } from './schedule-config.js';

export class Timeline {
    constructor(container, state) {
        this.container = container;
        this.state = state;
        this.track = null;
        this.dragState = {
            isDragging: false,
            isResizing: false,
            currentShift: null,
            startX: 0,
            startMinutes: 0,
            resizeHandle: null
        };
        
        this.init();
    }

    init() {
        this.track = this.container.querySelector('.timeline-track');
        if (!this.track) {
            console.error('No se encontró .timeline-track');
            return;
        }

        this.renderHourMarkers();
        this.attachEventListeners();
    }

    renderHourMarkers() {
        const markers = this.container.querySelector('.hour-markers');
        if (!markers) return;

        markers.innerHTML = '';
        for (let hour = CONFIG.HOURS_START; hour <= CONFIG.HOURS_END; hour++) {
            const marker = document.createElement('div');
            marker.className = 'hour-marker';
            marker.textContent = `${String(hour).padStart(2, '0')}:00`;
            marker.style.left = `${(hour / 24) * 100}%`;
            markers.appendChild(marker);
        }
    }

    renderShifts() {
        const shiftsContainer = this.track.querySelector('.shifts-container');
        if (!shiftsContainer) return;

        shiftsContainer.innerHTML = '';
        const shifts = this.state.getShiftsForDay(this.state.currentDay);

        shifts.forEach(shift => {
            const block = this.createShiftBlock(shift);
            shiftsContainer.appendChild(block);
        });
    }

    createShiftBlock(shift) {
        const block = document.createElement('div');
        block.className = `shift-block shift-${shift.shiftType}`;
        block.dataset.shiftId = shift.id;

        const startMinutes = timeToMinutes(shift.startTime);
        const endMinutes = timeToMinutes(shift.endTime);
        const duration = endMinutes > startMinutes ? endMinutes - startMinutes : (1440 - startMinutes) + endMinutes;

        const left = (startMinutes / 1440) * 100;
        const width = (duration / 1440) * 100;

        block.style.left = `${left}%`;
        block.style.width = `${width}%`;

        block.innerHTML = `
            <div class="shift-time">${shift.startTime} - ${shift.endTime}</div>
            <div class="shift-type-badge">${this.getShiftTypeName(shift.shiftType)}</div>
            <div class="resize-handle resize-start" data-handle="start"></div>
            <div class="resize-handle resize-end" data-handle="end"></div>
        `;

        block.addEventListener('mousedown', (e) => this.onShiftMouseDown(e, shift));
        
        const handles = block.querySelectorAll('.resize-handle');
        handles.forEach(handle => {
            handle.addEventListener('mousedown', (e) => this.onResizeStart(e, shift, handle.dataset.handle));
        });

        return block;
    }

    getShiftTypeName(type) {
        const types = {
            'regular': 'Regular',
            'night': 'Nocturno',
            'rotative': 'Rotativo'
        };
        return types[type] || type;
    }

    onShiftMouseDown(e, shift) {
        if (e.target.classList.contains('resize-handle')) return;

        e.preventDefault();
        this.dragState.isDragging = true;
        this.dragState.currentShift = shift;
        this.dragState.startX = e.clientX;
        this.dragState.startMinutes = timeToMinutes(shift.startTime);

        document.addEventListener('mousemove', this.onMouseMove);
        document.addEventListener('mouseup', this.onMouseUp);

        e.target.closest('.shift-block').classList.add('dragging');
    }

    onResizeStart(e, shift, handle) {
        e.preventDefault();
        e.stopPropagation();

        this.dragState.isResizing = true;
        this.dragState.currentShift = shift;
        this.dragState.startX = e.clientX;
        this.dragState.resizeHandle = handle;
        this.dragState.startMinutes = timeToMinutes(handle === 'start' ? shift.startTime : shift.endTime);

        document.addEventListener('mousemove', this.onMouseMove);
        document.addEventListener('mouseup', this.onMouseUp);

        e.target.closest('.shift-block').classList.add('resizing');
    }

    onMouseMove = (e) => {
        if (!this.dragState.isDragging && !this.dragState.isResizing) return;

        const rect = this.track.getBoundingClientRect();
        const deltaX = e.clientX - this.dragState.startX;
        const deltaMinutes = (deltaX / rect.width) * 1440;

        if (this.dragState.isDragging) {
            this.handleDrag(deltaMinutes);
        } else if (this.dragState.isResizing) {
            this.handleResize(deltaMinutes);
        }
    }

    onMouseUp = () => {
        if (this.dragState.isDragging || this.dragState.isResizing) {
            document.querySelectorAll('.shift-block').forEach(block => {
                block.classList.remove('dragging', 'resizing');
            });

            this.dragState.isDragging = false;
            this.dragState.isResizing = false;
            this.dragState.currentShift = null;

            window.dispatchEvent(new CustomEvent('shiftsChanged'));
        }

        document.removeEventListener('mousemove', this.onMouseMove);
        document.removeEventListener('mouseup', this.onMouseUp);
    }

    handleDrag(deltaMinutes) {
        const shift = this.dragState.currentShift;
        const newStartMinutes = snapToInterval(this.dragState.startMinutes + deltaMinutes);
        
        const startMinutes = timeToMinutes(shift.startTime);
        const endMinutes = timeToMinutes(shift.endTime);
        const duration = endMinutes > startMinutes ? endMinutes - startMinutes : (1440 - startMinutes) + endMinutes;

        let finalStart = Math.max(0, Math.min(1440 - duration, newStartMinutes));
        let finalEnd = finalStart + duration;

        if (finalEnd >= 1440) {
            finalEnd = finalEnd % 1440;
        }

        this.state.updateShift(this.state.currentDay, shift.id, {
            startTime: minutesToTime(finalStart),
            endTime: minutesToTime(finalEnd)
        });

        this.renderShifts();
    }

    handleResize(deltaMinutes) {
        const shift = this.dragState.currentShift;
        const handle = this.dragState.resizeHandle;
        const newMinutes = snapToInterval(this.dragState.startMinutes + deltaMinutes);

        if (handle === 'start') {
            const endMinutes = timeToMinutes(shift.endTime);
            const maxStart = endMinutes - CONFIG.MIN_DURATION;
            const finalStart = Math.max(0, Math.min(maxStart, newMinutes));

            this.state.updateShift(this.state.currentDay, shift.id, {
                startTime: minutesToTime(finalStart)
            });
        } else {
            const startMinutes = timeToMinutes(shift.startTime);
            const minEnd = startMinutes + CONFIG.MIN_DURATION;
            const finalEnd = Math.max(minEnd, Math.min(1440, newMinutes));

            this.state.updateShift(this.state.currentDay, shift.id, {
                endTime: minutesToTime(finalEnd % 1440)
            });
        }

        this.renderShifts();
    }

    addShiftAtPosition(clientX) {
        const rect = this.track.getBoundingClientRect();
        const relativeX = clientX - rect.left;
        const percentage = relativeX / rect.width;
        const minutes = snapToInterval(percentage * 1440);

        const startTime = minutesToTime(minutes);
        const endTime = minutesToTime((minutes + 480) % 1440);

        const shift = {
            id: Date.now(),
            startTime,
            endTime,
            shiftType: 'regular'
        };

        this.state.addShift(this.state.currentDay, shift);
        this.renderShifts();
        window.dispatchEvent(new CustomEvent('shiftsChanged'));
    }

    attachEventListeners() {
        this.track.addEventListener('dblclick', (e) => {
            if (e.target.classList.contains('timeline-track') || 
                e.target.classList.contains('shifts-container')) {
                this.addShiftAtPosition(e.clientX);
            }
        });
    }

    update() {
        this.renderShifts();
    }
}
