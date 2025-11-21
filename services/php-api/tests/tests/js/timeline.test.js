import { JSDOM } from 'jsdom';
import fs from 'fs';
import path from 'path';

// Basic smoke tests for Timeline
import { Timeline } from '../../demo/schedule-modal-v2/js/timeline.js';

describe('Timeline (smoke tests)', () => {
    let dom;
    let container;
    let state;

    beforeEach(() => {
        const html = fs.readFileSync(path.resolve('./demo/schedule-modal-v2/index.html'), 'utf8');
        dom = new JSDOM(html, { runScripts: 'dangerously' });
        container = dom.window.document.querySelector('#timelineContainer');

        state = {
            currentDay: 1,
            shifts: {
                1: []
            },
            getShiftsForDay(day) { return this.shifts[day] || []; },
            addShift(day, shift) { if (!this.shifts[day]) this.shifts[day] = []; this.shifts[day].push(shift); },
            updateShift() { }
        };
    });

    test('renders without throwing', () => {
        const timeline = new Timeline(container, state);
        timeline.renderHourMarkers();
        expect(container.querySelectorAll('.hour-marker').length).toBeGreaterThan(0);
    });

    test('addShiftAtPosition adds a shift to state', () => {
        const timeline = new Timeline(container, state);
        // simulate a click on the center of the track
        const track = container.querySelector('.timeline-track');
        const rect = { left: 0, width: 1440 };
        track.getBoundingClientRect = () => rect;
        timeline.addShiftAtPosition(720);
        expect(state.getShiftsForDay(1).length).toBe(1);
    });
});
