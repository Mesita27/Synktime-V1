/**
 * Schedule Modal - Funcionalidad Interactiva del Modal de Horarios
 * Maneja la lógica del timeline, drag & drop, validaciones y wizard
 */

const ScheduleModal = {
    // Estado del modal
    state: {
        currentDay: "monday",
        selectedDays: [],
        workBlocks: [],
        isDragging: false,
        dragStartX: 0,
        dragElement: null,
        resizeHandle: null
    },

    // Configuración del timeline
    config: {
        startHour: 0,
        endHour: 24,
        pixelsPerHour: 60,
        containerWidth: 0
    },

    // Inicializar timeline
    initTimeline() {
        this.cacheTimelineElements();
        this.setupTimeline();
        this.bindTimelineEvents();
        this.createInitialWorkBlock();
        console.log("Timeline initialized");
    },

    // Cache de elementos del timeline
    cacheTimelineElements() {
        this.elements = {
            timeline: document.querySelector(".timeline-container"),
            track: document.querySelector(".timeline-track"),
            dayButtons: document.querySelectorAll(".day-btn"),
            timeInputs: {
                entrada: document.getElementById("hora_entrada"),
                salida: document.getElementById("hora_salida")
            }
        };
    },

    // Configurar timeline
    setupTimeline() {
        if (!this.elements.timeline) return;

        this.config.containerWidth = this.elements.timeline.offsetWidth - 32; // padding
        this.createTimeMarkers();
        this.updateTimelineDisplay();
    },

    // Crear marcadores de tiempo
    createTimeMarkers() {
        const track = this.elements.track;
        if (!track) return;

        // Limpiar marcadores existentes
        track.innerHTML = "";

        // Crear marcadores de horas
        for (let hour = this.config.startHour; hour <= this.config.endHour; hour++) {
            const marker = document.createElement("div");
            marker.className = "timeline-hour-marker";
            marker.textContent = `${hour.toString().padStart(2, "0")}:00`;
            marker.style.left = `${(hour / 24) * 100}%`;
            track.appendChild(marker);

            // Crear marcadores de minutos (cada 30 min)
            if (hour < this.config.endHour) {
                for (let minute = 30; minute < 60; minute += 30) {
                    const minuteMarker = document.createElement("div");
                    minuteMarker.className = "timeline-minute-marker";
                    minuteMarker.style.left = `${((hour + minute / 60) / 24) * 100}%`;
                    track.appendChild(minuteMarker);
                }
            }
        }
    },

    // Vincular eventos del timeline
    bindTimelineEvents() {
        // Botones de días
        this.elements.dayButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                this.selectDay(e.target.dataset.day);
            });
        });

        // Inputs de tiempo
        Object.values(this.elements.timeInputs).forEach(input => {
            if (input) {
                input.addEventListener("change", () => this.updateFromTimeInputs());
            }
        });

        // Eventos de mouse para drag & drop
        document.addEventListener("mousedown", (e) => this.handleMouseDown(e));
        document.addEventListener("mousemove", (e) => this.handleMouseMove(e));
        document.addEventListener("mouseup", () => this.handleMouseUp());

        // Prevenir selección de texto durante drag
        document.addEventListener("selectstart", (e) => {
            if (this.state.isDragging) {
                e.preventDefault();
            }
        });
    },

    // Seleccionar día
    selectDay(day) {
        this.state.currentDay = day;

        // Actualizar botones
        this.elements.dayButtons.forEach(btn => {
            btn.classList.toggle("active", btn.dataset.day === day);
        });

        // Actualizar timeline
        this.updateTimelineDisplay();
    },

    // Crear bloque de trabajo inicial
    createInitialWorkBlock() {
        const workBlock = {
            id: "work-block-1",
            day: this.state.currentDay,
            startTime: "09:00",
            endTime: "18:00",
            label: "Jornada Laboral"
        };

        this.state.workBlocks = [workBlock];
        this.renderWorkBlocks();
        this.updateTimeInputs();
    },

    // Renderizar bloques de trabajo
    renderWorkBlocks() {
        // Limpiar bloques existentes
        const existingBlocks = this.elements.timeline.querySelectorAll(".work-block");
        existingBlocks.forEach(block => block.remove());

        // Renderizar bloques para el día actual
        const dayBlocks = this.state.workBlocks.filter(block => block.day === this.state.currentDay);

        dayBlocks.forEach(block => {
            const blockElement = this.createWorkBlockElement(block);
            this.elements.timeline.appendChild(blockElement);
        });
    },

    // Crear elemento de bloque de trabajo
    createWorkBlockElement(block) {
        const blockElement = document.createElement("div");
        blockElement.className = "work-block";
        blockElement.dataset.id = block.id;
        blockElement.dataset.day = block.day;

        // Calcular posición y ancho
        const startPercent = this.timeToPercent(block.startTime);
        const endPercent = this.timeToPercent(block.endTime);
        const widthPercent = endPercent - startPercent;

        blockElement.style.left = `${startPercent}%`;
        blockElement.style.width = `${widthPercent}%`;

        blockElement.innerHTML = `
            <div class="block-handle start-handle" data-handle="start"></div>
            <div class="block-content">
                <span class="block-time">${block.startTime} - ${block.endTime}</span>
                <span class="block-label">${block.label}</span>
            </div>
            <div class="block-handle end-handle" data-handle="end"></div>
        `;

        return blockElement;
    },

    // Convertir tiempo a porcentaje
    timeToPercent(timeString) {
        const [hours, minutes] = timeString.split(":").map(Number);
        const totalMinutes = hours * 60 + minutes;
        const totalDayMinutes = 24 * 60;
        return (totalMinutes / totalDayMinutes) * 100;
    },

    // Convertir porcentaje a tiempo
    percentToTime(percent) {
        const totalMinutes = (percent / 100) * 24 * 60;
        const hours = Math.floor(totalMinutes / 60);
        const minutes = Math.round(totalMinutes % 60);

        // Redondear a intervalos de 15 minutos
        const roundedMinutes = Math.round(minutes / 15) * 15;
        const finalHours = hours + Math.floor(roundedMinutes / 60);
        const finalMinutes = roundedMinutes % 60;

        return `${finalHours.toString().padStart(2, "0")}:${finalMinutes.toString().padStart(2, "0")}`;
    },

    // Manejar mouse down
    handleMouseDown(e) {
        const workBlock = e.target.closest(".work-block");
        const handle = e.target.closest(".block-handle");

        if (workBlock) {
            e.preventDefault();
            this.state.isDragging = true;
            this.state.dragElement = workBlock;
            this.state.dragStartX = e.clientX;

            if (handle) {
                this.state.resizeHandle = handle.dataset.handle;
                workBlock.classList.add("resizing");
            } else {
                this.state.resizeHandle = null;
                workBlock.classList.add("dragging");
            }

            // Guardar posición inicial
            this.state.initialLeft = parseFloat(workBlock.style.left);
            this.state.initialWidth = parseFloat(workBlock.style.width);
        }
    },

    // Manejar mouse move
    handleMouseMove(e) {
        if (!this.state.isDragging || !this.state.dragElement) return;

        const deltaX = e.clientX - this.state.dragStartX;
        const containerRect = this.elements.timeline.getBoundingClientRect();
        const containerWidth = containerRect.width - 32; // padding
        const deltaPercent = (deltaX / containerWidth) * 100;

        if (this.state.resizeHandle) {
            this.handleResize(deltaPercent);
        } else {
            this.handleDrag(deltaPercent);
        }
    },

    // Manejar redimensionamiento
    handleResize(deltaPercent) {
        const block = this.state.dragElement;
        let newLeft = this.state.initialLeft;
        let newWidth = this.state.initialWidth;

        if (this.state.resizeHandle === "start") {
            newLeft = Math.max(0, this.state.initialLeft + deltaPercent);
            newWidth = Math.max(5, this.state.initialWidth - deltaPercent);
        } else if (this.state.resizeHandle === "end") {
            newWidth = Math.max(5, this.state.initialWidth + deltaPercent);
        }

        // Limitar al contenedor
        if (newLeft + newWidth > 100) {
            newWidth = 100 - newLeft;
        }

        block.style.left = `${newLeft}%`;
        block.style.width = `${newWidth}%`;

        // Actualizar tiempos
        this.updateBlockTimes(block);
    },

    // Manejar arrastre
    handleDrag(deltaPercent) {
        const block = this.state.dragElement;
        const currentWidth = parseFloat(block.style.width);
        let newLeft = this.state.initialLeft + deltaPercent;

        // Limitar al contenedor
        newLeft = Math.max(0, Math.min(100 - currentWidth, newLeft));

        block.style.left = `${newLeft}%`;

        // Actualizar tiempos
        this.updateBlockTimes(block);
    },

    // Actualizar tiempos del bloque
    updateBlockTimes(block) {
        const leftPercent = parseFloat(block.style.left);
        const widthPercent = parseFloat(block.style.width);

        const startTime = this.percentToTime(leftPercent);
        const endTime = this.percentToTime(leftPercent + widthPercent);

        // Actualizar display
        const timeElement = block.querySelector(".block-time");
        if (timeElement) {
            timeElement.textContent = `${startTime} - ${endTime}`;
        }

        // Actualizar estado
        const blockId = block.dataset.id;
        const blockData = this.state.workBlocks.find(b => b.id === blockId);
        if (blockData) {
            blockData.startTime = startTime;
            blockData.endTime = endTime;
        }

        // Actualizar inputs
        this.updateTimeInputs();
    },

    // Manejar mouse up
    handleMouseUp() {
        if (this.state.isDragging) {
            this.state.isDragging = false;

            if (this.state.dragElement) {
                this.state.dragElement.classList.remove("dragging", "resizing");
            }

            this.state.dragElement = null;
            this.state.resizeHandle = null;

            // Validar y ajustar el bloque
            this.validateAndAdjustBlock();
        }
    },

    // Validar y ajustar bloque
    validateAndAdjustBlock() {
        // Aquí se podrían agregar validaciones adicionales
        // Por ejemplo, asegurar que no haya solapamientos, horas mínimas, etc.
        this.renderWorkBlocks();
    },

    // Actualizar inputs de tiempo
    updateTimeInputs() {
        const dayBlocks = this.state.workBlocks.filter(block => block.day === this.state.currentDay);

        if (dayBlocks.length > 0) {
            const firstBlock = dayBlocks[0];
            if (this.elements.timeInputs.entrada) {
                this.elements.timeInputs.entrada.value = firstBlock.startTime;
            }
            if (this.elements.timeInputs.salida) {
                this.elements.timeInputs.salida.value = firstBlock.endTime;
            }
        }
    },

    // Actualizar desde inputs de tiempo
    updateFromTimeInputs() {
        const entrada = this.elements.timeInputs.entrada?.value;
        const salida = this.elements.timeInputs.salida?.value;

        if (entrada && salida) {
            const dayBlocks = this.state.workBlocks.filter(block => block.day === this.state.currentDay);
            if (dayBlocks.length > 0) {
                dayBlocks[0].startTime = entrada;
                dayBlocks[0].endTime = salida;
                this.renderWorkBlocks();
            }
        }
    },

    // Actualizar display del timeline
    updateTimelineDisplay() {
        this.renderWorkBlocks();
        this.updateTimeInputs();
    },

    // Agregar bloque de trabajo
    addWorkBlock(startTime = "09:00", endTime = "18:00", label = "Nuevo Bloque") {
        const blockId = `work-block-${Date.now()}`;
        const workBlock = {
            id: blockId,
            day: this.state.currentDay,
            startTime,
            endTime,
            label
        };

        this.state.workBlocks.push(workBlock);
        this.renderWorkBlocks();
    },

    // Eliminar bloque de trabajo
    removeWorkBlock(blockId) {
        this.state.workBlocks = this.state.workBlocks.filter(block => block.id !== blockId);
        this.renderWorkBlocks();
    },

    // Obtener datos del horario
    getScheduleData() {
        return {
            workBlocks: this.state.workBlocks,
            selectedDays: this.state.selectedDays
        };
    },

    // Resetear timeline
    reset() {
        this.state.workBlocks = [];
        this.state.selectedDays = [];
        this.state.currentDay = "monday";
        this.createInitialWorkBlock();
    }
};

// Hacer disponible globalmente
window.ScheduleModal = ScheduleModal;
