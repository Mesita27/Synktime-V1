/**
 * Demo App - Modal de Horarios Interactivo
 * Controlador principal de la aplicación de demostración
 */

// Estado global de la aplicación
const DemoApp = {
    // Estado del modal
    modalState: {
        isOpen: false,
        currentStep: 1,
        totalSteps: 4,
        scheduleData: {
            nombre: "",
            sede_id: "",
            establecimiento_id: "",
            dias: [],
            hora_entrada: "09:00",
            hora_salida: "18:00"
        }
    },

    // Elementos DOM
    elements: {},

    // Inicializar la aplicación
    init() {
        this.cacheElements();
        this.bindEvents();
        this.initializeDemo();
        console.log("Demo App initialized");
    },

    // Cache de elementos DOM
    cacheElements() {
        this.elements = {
            scheduleModal: document.getElementById("scheduleModal"),
            guideModal: document.getElementById("guideModal"),
            modalContainer: document.querySelector("#scheduleModal .modal-container")
        };
    },

    // Vincular eventos
    bindEvents() {
        // Botones principales
        document.addEventListener("click", (e) => {
            if (e.target.matches(".btn-demo-primary")) {
                this.openScheduleModal();
            }
            if (e.target.matches(".btn-demo-secondary")) {
                this.showGuideModal();
            }
        });

        // Eventos del modal
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                this.closeAllModals();
            }
        });
    },

    // Inicializar funcionalidades de demo
    initializeDemo() {
        this.addInteractiveEffects();
        this.initializeTimelinePreview();
    },

    // Abrir modal de horarios
    openScheduleModal() {
        this.modalState.isOpen = true;
        this.modalState.currentStep = 1;

        // Cargar contenido del modal
        this.loadModalContent();

        // Mostrar modal
        this.elements.scheduleModal.classList.add("active");

        // Prevenir scroll del body
        document.body.style.overflow = "hidden";

        console.log("Schedule modal opened");
    },

    // Cerrar modal de horarios
    closeScheduleModal() {
        this.modalState.isOpen = false;
        this.elements.scheduleModal.classList.remove("active");
        document.body.style.overflow = "";

        // Resetear estado
        this.resetModalState();

        console.log("Schedule modal closed");
    },

    // Mostrar modal de guía
    showGuideModal() {
        this.elements.guideModal.classList.add("active");
        document.body.style.overflow = "hidden";
    },

    // Cerrar modal de guía
    closeGuideModal() {
        this.elements.guideModal.classList.remove("active");
        document.body.style.overflow = "";
    },

    // Cerrar todos los modales
    closeAllModals() {
        this.closeScheduleModal();
        this.closeGuideModal();
    },

    // Cargar contenido del modal
    async loadModalContent() {
        try {
            const response = await fetch("modal-templates/schedule-modal.html");
            if (!response.ok) {
                throw new Error("Failed to load modal content");
            }

            const html = await response.text();
            this.elements.modalContainer.innerHTML = html;

            // Inicializar funcionalidad del modal después de cargar
            this.initializeModalFunctionality();

        } catch (error) {
            console.error("Error loading modal content:", error);
            this.showError("Error al cargar el contenido del modal");
        }
    },

    // Inicializar funcionalidad del modal
    initializeModalFunctionality() {
        // Inicializar pasos del wizard
        this.initializeWizard();

        // Inicializar timeline
        this.initializeTimeline();

        // Vincular eventos del modal
        this.bindModalEvents();
    },

    // Inicializar wizard de pasos
    initializeWizard() {
        this.updateStepIndicator();
        this.showCurrentStep();
    },

    // Actualizar indicador de pasos
    updateStepIndicator() {
        const indicators = document.querySelectorAll(".step-indicator-item");
        indicators.forEach((indicator, index) => {
            const stepNumber = index + 1;
            indicator.classList.remove("active", "completed");

            if (stepNumber === this.modalState.currentStep) {
                indicator.classList.add("active");
            } else if (stepNumber < this.modalState.currentStep) {
                indicator.classList.add("completed");
            }
        });
    },

    // Mostrar paso actual
    showCurrentStep() {
        const sections = document.querySelectorAll(".form-section");
        sections.forEach((section, index) => {
            if (index + 1 === this.modalState.currentStep) {
                section.classList.add("active");
            } else {
                section.classList.remove("active");
            }
        });
    },

    // Inicializar timeline
    initializeTimeline() {
        // Esta funcionalidad se implementará en schedule-modal.js
        if (typeof ScheduleModal !== "undefined") {
            ScheduleModal.initTimeline();
        }
    },

    // Vincular eventos del modal
    bindModalEvents() {
        // Botón cerrar
        const closeBtn = document.querySelector(".modal-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", () => this.closeScheduleModal());
        }

        // Botones de navegación
        const nextBtns = document.querySelectorAll(".btn-next");
        const prevBtns = document.querySelectorAll(".btn-prev");
        const saveBtn = document.querySelector(".btn-save");

        nextBtns.forEach(btn => {
            btn.addEventListener("click", () => this.nextStep());
        });

        prevBtns.forEach(btn => {
            btn.addEventListener("click", () => this.prevStep());
        });

        if (saveBtn) {
            saveBtn.addEventListener("click", () => this.saveSchedule());
        }
    },

    // Navegar al siguiente paso
    nextStep() {
        if (this.modalState.currentStep < this.modalState.totalSteps) {
            this.modalState.currentStep++;
            this.updateStepIndicator();
            this.showCurrentStep();
        }
    },

    // Navegar al paso anterior
    prevStep() {
        if (this.modalState.currentStep > 1) {
            this.modalState.currentStep--;
            this.updateStepIndicator();
            this.showCurrentStep();
        }
    },

    // Guardar horario
    saveSchedule() {
        // Validar datos
        if (!this.validateScheduleData()) {
            this.showError("Por favor complete todos los campos requeridos");
            return;
        }

        // Simular guardado
        this.showLoading(true);

        setTimeout(() => {
            this.showLoading(false);
            this.showSuccess("Horario guardado exitosamente");
            setTimeout(() => {
                this.closeScheduleModal();
            }, 1500);
        }, 2000);
    },

    // Validar datos del horario
    validateScheduleData() {
        const { scheduleData } = this.modalState;

        return scheduleData.nombre &&
               scheduleData.sede_id &&
               scheduleData.establecimiento_id &&
               scheduleData.dias.length > 0;
    },

    // Resetear estado del modal
    resetModalState() {
        this.modalState.currentStep = 1;
        this.modalState.scheduleData = {
            nombre: "",
            sede_id: "",
            establecimiento_id: "",
            dias: [],
            hora_entrada: "09:00",
            hora_salida: "18:00"
        };
    },

    // Mostrar loading
    showLoading(show) {
        const modalBody = document.querySelector(".modal-body");
        const saveBtn = document.querySelector(".btn-save");

        if (show) {
            modalBody.classList.add("loading");
            if (saveBtn) {
                saveBtn.innerHTML = "<span class=\"spinner\"></span> Guardando...";
                saveBtn.disabled = true;
            }
        } else {
            modalBody.classList.remove("loading");
            if (saveBtn) {
                saveBtn.innerHTML = "Guardar Horario";
                saveBtn.disabled = false;
            }
        }
    },

    // Mostrar mensaje de éxito
    showSuccess(message) {
        this.showNotification(message, "success");
    },

    // Mostrar mensaje de error
    showError(message) {
        this.showNotification(message, "error");
    },

    // Mostrar notificación
    showNotification(message, type = "info") {
        // Crear elemento de notificación
        const notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === "success" ? "fa-check-circle" : "fa-exclamation-circle"}"></i>
            <span>${message}</span>
        `;

        // Agregar al DOM
        document.body.appendChild(notification);

        // Mostrar con animación
        setTimeout(() => notification.classList.add("show"), 100);

        // Ocultar después de 3 segundos
        setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    },

    // Agregar efectos interactivos
    addInteractiveEffects() {
        // Efectos hover en tarjetas de características
        const featureCards = document.querySelectorAll(".feature-card");
        featureCards.forEach(card => {
            card.addEventListener("mouseenter", () => {
                card.style.transform = "translateY(-4px)";
            });

            card.addEventListener("mouseleave", () => {
                card.style.transform = "translateY(0)";
            });
        });

        // Efectos en botones de timeline preview
        const timelineBtns = document.querySelectorAll(".timeline-btn");
        timelineBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                timelineBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
            });
        });
    },

    // Inicializar timeline preview
    initializeTimelinePreview() {
        const scheduleBlock = document.querySelector(".schedule-block");
        if (scheduleBlock) {
            // Agregar funcionalidad básica de drag preview
            let isDragging = false;
            let startX = 0;
            let startLeft = 0;

            scheduleBlock.addEventListener("mousedown", (e) => {
                isDragging = true;
                startX = e.clientX;
                startLeft = parseFloat(scheduleBlock.style.left) || 25;
                scheduleBlock.style.cursor = "grabbing";
            });

            document.addEventListener("mousemove", (e) => {
                if (!isDragging) return;

                const deltaX = e.clientX - startX;
                const containerWidth = scheduleBlock.parentElement.offsetWidth;
                const blockWidth = scheduleBlock.offsetWidth;
                const maxLeft = containerWidth - blockWidth;

                let newLeft = startLeft + (deltaX / containerWidth) * 100;
                newLeft = Math.max(0, Math.min(maxLeft / containerWidth * 100, newLeft));

                scheduleBlock.style.left = `${newLeft}%`;
            });

            document.addEventListener("mouseup", () => {
                if (isDragging) {
                    isDragging = false;
                    scheduleBlock.style.cursor = "move";
                }
            });
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
    DemoApp.init();
});

// Funciones globales para acceso desde HTML
function openScheduleModal() {
    DemoApp.openScheduleModal();
}

function closeScheduleModal() {
    DemoApp.closeScheduleModal();
}

function showDemoGuide() {
    DemoApp.showGuideModal();
}

function closeGuideModal() {
    DemoApp.closeGuideModal();
}
