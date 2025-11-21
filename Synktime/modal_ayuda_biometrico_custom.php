<?php
// modal_ayuda_biometrico_custom.php - Modal personalizado de ayuda biométrica
?>
<!-- Modal Personalizado de Ayuda Biométrica -->
<div id="biometricHelpModal" class="biometric-modal-overlay" style="display: none;">
    <div class="biometric-modal-container">
        <div class="biometric-modal-content">
            <!-- Header del Modal -->
            <div class="biometric-modal-header">
                <div class="biometric-modal-title">
                    <div class="biometric-modal-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="biometric-modal-title-text">
                        <h2>Guía de Ayuda Biométrica</h2>
                        <p>Sistema de Inscripción Facial</p>
                    </div>
                </div>
                <button class="biometric-modal-close" onclick="closeBiometricHelpModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body del Modal -->
            <div class="biometric-modal-body">
                <div class="biometric-help-content">

                    <!-- Introducción -->
                    <div class="biometric-help-section">
                        <div class="biometric-help-intro">
                            <div class="biometric-intro-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="biometric-intro-content">
                                <h3>¿Qué es la Inscripción Biométrica?</h3>
                                <p>La inscripción biométrica permite registrar las características faciales únicas de los empleados para un reconocimiento automático durante el registro de asistencia, proporcionando mayor seguridad y eficiencia en el control de acceso.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Funcionalidades Principales -->
                    <div class="biometric-help-section">
                        <h3 class="biometric-section-title">
                            <i class="fas fa-cogs"></i>
                            Funcionalidades Principales
                        </h3>
                        <div class="biometric-features-grid">
                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Registro de Empleados</h4>
                                    <p>Inscribir nuevos empleados en el sistema biométrico de manera sencilla y rápida.</p>
                                </div>
                            </div>

                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Captura Facial</h4>
                                    <p>Tomar fotos del rostro del empleado con tecnología avanzada de detección facial.</p>
                                </div>
                            </div>

                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Verificación Automática</h4>
                                    <p>Validar automáticamente que la captura facial sea correcta y de calidad óptima.</p>
                                </div>
                            </div>

                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Gestión Masiva</h4>
                                    <p>Administrar múltiples inscripciones de manera eficiente y organizada.</p>
                                </div>
                            </div>

                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Estadísticas y Reportes</h4>
                                    <p>Visualizar el progreso de inscripciones y generar reportes detallados.</p>
                                </div>
                            </div>

                            <div class="biometric-feature-card">
                                <div class="biometric-feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="biometric-feature-content">
                                    <h4>Seguridad Avanzada</h4>
                                    <p>Protección de datos biométricos con encriptación y medidas de seguridad.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cómo Usar el Sistema -->
                    <div class="biometric-help-section">
                        <h3 class="biometric-section-title">
                            <i class="fas fa-play-circle"></i>
                            Cómo Usar la Inscripción Biométrica
                        </h3>
                        <div class="biometric-steps-container">
                            <div class="biometric-step">
                                <div class="biometric-step-number">1</div>
                                <div class="biometric-step-content">
                                    <h4>Seleccionar Empleado</h4>
                                    <p>Elige el empleado de la lista disponible en el sistema.</p>
                                </div>
                            </div>

                            <div class="biometric-step">
                                <div class="biometric-step-number">2</div>
                                <div class="biometric-step-content">
                                    <h4>Posicionamiento</h4>
                                    <p>Asegúrate de que el empleado mire directamente a la cámara.</p>
                                </div>
                            </div>

                            <div class="biometric-step">
                                <div class="biometric-step-number">3</div>
                                <div class="biometric-step-content">
                                    <h4>Iluminación</h4>
                                    <p>Verifica que haya buena iluminación en el rostro del empleado.</p>
                                </div>
                            </div>

                            <div class="biometric-step">
                                <div class="biometric-step-number">4</div>
                                <div class="biometric-step-content">
                                    <h4>Captura Automática</h4>
                                    <p>El sistema detectará automáticamente el rostro y tomará la foto.</p>
                                </div>
                            </div>

                            <div class="biometric-step">
                                <div class="biometric-step-number">5</div>
                                <div class="biometric-step-content">
                                    <h4>Verificación</h4>
                                    <p>Confirma que la imagen capturada es clara y nítida.</p>
                                </div>
                            </div>

                            <div class="biometric-step">
                                <div class="biometric-step-number">6</div>
                                <div class="biometric-step-content">
                                    <h4>Guardar</h4>
                                    <p>Registra la inscripción en el sistema de manera permanente.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recomendaciones -->
                    <div class="biometric-help-section">
                        <h3 class="biometric-section-title">
                            <i class="fas fa-lightbulb"></i>
                            Recomendaciones para una Buena Captura
                        </h3>
                        <div class="biometric-recommendations-grid">
                            <div class="biometric-recommendation-card">
                                <div class="biometric-recommendation-header">
                                    <i class="fas fa-sun"></i>
                                    <h4>Iluminación</h4>
                                </div>
                                <ul class="biometric-recommendation-list">
                                    <li>Usa luz natural o iluminación blanca uniforme</li>
                                    <li>Evita sombras fuertes en el rostro</li>
                                    <li>No uses flash directo que pueda deslumbrar</li>
                                    <li>La iluminación debe ser pareja en ambos lados del rostro</li>
                                </ul>
                            </div>

                            <div class="biometric-recommendation-card">
                                <div class="biometric-recommendation-header">
                                    <i class="fas fa-eye"></i>
                                    <h4>Posición del Rostro</h4>
                                </div>
                                <ul class="biometric-recommendation-list">
                                    <li>Mira directamente a la cámara sin inclinar la cabeza</li>
                                    <li>Mantén la cabeza erguida y centrada</li>
                                    <li>Evita ángulos extremos o posiciones forzadas</li>
                                    <li>El rostro debe ocupar el 60-70% del encuadre</li>
                                </ul>
                            </div>

                            <div class="biometric-recommendation-card">
                                <div class="biometric-recommendation-header">
                                    <i class="fas fa-glasses"></i>
                                    <h4>Accesorios</h4>
                                </div>
                                <ul class="biometric-recommendation-list">
                                    <li>Si usas gafas, asegúrate de que no reflejen luz</li>
                                    <li>Evita sombreros o gorras que cubran el rostro</li>
                                    <li>Quita mascarillas si es posible para mejor precisión</li>
                                    <li>El cabello no debe cubrir los ojos o cejas</li>
                                </ul>
                            </div>

                            <div class="biometric-recommendation-card">
                                <div class="biometric-recommendation-header">
                                    <i class="fas fa-camera"></i>
                                    <h4>Calidad de Imagen</h4>
                                </div>
                                <ul class="biometric-recommendation-list">
                                    <li>Distancia recomendada: 30-50 cm de la cámara</li>
                                    <li>Resolución mínima: 640x480 píxeles</li>
                                    <li>Formato recomendado: JPG o PNG de alta calidad</li>
                                    <li>Evita movimientos durante la captura</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas Importantes -->
                    <div class="biometric-help-section">
                        <div class="biometric-alerts-container">
                            <div class="biometric-alert biometric-alert-warning">
                                <div class="biometric-alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="biometric-alert-content">
                                    <h4>Consideraciones Importantes</h4>
                                    <ul>
                                        <li><strong>Privacidad:</strong> Las imágenes se almacenan de forma segura y encriptada</li>
                                        <li><strong>Consentimiento:</strong> Asegúrate de tener el consentimiento del empleado antes de la captura</li>
                                        <li><strong>Actualización:</strong> Re-inscribe si hay cambios significativos en la apariencia</li>
                                        <li><strong>Mantenimiento:</strong> Realiza verificaciones periódicas del sistema</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="biometric-modal-footer">
                <button class="biometric-btn biometric-btn-primary" onclick="closeBiometricHelpModal()">
                    <i class="fas fa-times"></i>
                    Cerrar Ayuda
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos CSS para el Modal Personalizado -->
<style>
/* Modal Biométrico - Optimizado para dispositivos */
.biometric-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.biometric-modal-container {
    width: 95%;
    max-width: 900px;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.biometric-modal-content {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

/* Header */
.biometric-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #667eea;
    color: #fff;
}

.biometric-modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.biometric-modal-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.biometric-modal-title-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.biometric-modal-title-text p {
    margin: 4px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.biometric-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background 0.2s;
}

.biometric-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Body */
.biometric-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    max-height: calc(90vh - 120px);
}

/* Help Sections */
.biometric-help-section {
    margin-bottom: 24px;
}

.biometric-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #dee2e6;
}

/* Introducción */
.biometric-help-intro {
    display: flex;
    gap: 16px;
    background: #f8f9ff;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid #e3f2fd;
    margin-bottom: 20px;
}

.biometric-intro-icon {
    width: 48px;
    height: 48px;
    background: #667eea;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.biometric-intro-content h3 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
}

.biometric-intro-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

/* Features Grid */
.biometric-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.biometric-feature-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
    display: flex;
    gap: 12px;
    transition: box-shadow 0.2s;
}

.biometric-feature-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.biometric-feature-icon {
    width: 40px;
    height: 40px;
    background: #667eea;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}

.biometric-feature-content h4 {
    margin: 0 0 6px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.biometric-feature-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Steps */
.biometric-steps-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.biometric-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.biometric-step-number {
    width: 28px;
    height: 28px;
    background: #667eea;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
}

.biometric-step-content h4 {
    margin: 0 0 4px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.biometric-step-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Recommendations Grid */
.biometric-recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.biometric-recommendation-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.biometric-recommendation-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.biometric-recommendation-header i {
    color: #667eea;
    font-size: 18px;
}

.biometric-recommendation-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.biometric-recommendation-list {
    margin: 0;
    padding-left: 16px;
    list-style: none;
}

.biometric-recommendation-list li {
    margin-bottom: 6px;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
    position: relative;
    padding-left: 20px;
}

.biometric-recommendation-list li:before {
    content: "✓";
    color: #4caf50;
    font-weight: bold;
    position: absolute;
    left: 0;
}

/* Alerts */
.biometric-alerts-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.biometric-alert {
    display: flex;
    gap: 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.biometric-alert-warning {
    border-left: 3px solid #ff9800;
}

.biometric-alert-success {
    border-left: 3px solid #4caf50;
}

.biometric-alert-icon {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

.biometric-alert-warning .biometric-alert-icon {
    color: #ff9800;
}

.biometric-alert-success .biometric-alert-icon {
    color: #4caf50;
}

.biometric-alert-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.biometric-alert-content ul {
    margin: 0;
    padding-left: 16px;
}

.biometric-alert-content li {
    margin-bottom: 4px;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Footer */
.biometric-modal-footer {
    background: #f8f9fa;
    padding: 16px 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
}

.biometric-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.biometric-btn-primary {
    background: #667eea;
    color: #fff;
}

.biometric-btn-primary:hover {
    background: #5a67d8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .biometric-modal-container {
        width: 98%;
        margin: 5px;
        max-height: 95vh;
    }

    .biometric-modal-header {
        padding: 12px 16px;
    }

    .biometric-modal-title {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }

    .biometric-modal-body {
        padding: 16px;
    }

    .biometric-features-grid,
    .biometric-recommendations-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .biometric-help-intro {
        flex-direction: column;
        text-align: center;
    }

    .biometric-step {
        flex-direction: column;
        text-align: center;
    }

    .biometric-alert {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .biometric-modal-container {
        width: 100%;
        margin: 0;
        border-radius: 0;
        max-height: 100vh;
    }

    .biometric-modal-header {
        padding: 10px 12px;
    }

    .biometric-modal-body {
        padding: 12px;
    }

    .biometric-feature-card,
    .biometric-recommendation-card,
    .biometric-alert {
        padding: 12px;
    }
}
</style>

<!-- JavaScript para el Modal Personalizado -->
<script>
function showBiometricHelpModal() {
    console.log('🚀 Mostrando modal de ayuda biométrica personalizado...');

    const modal = document.getElementById('biometricHelpModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Agregar event listener para cerrar con Escape
        document.addEventListener('keydown', handleBiometricModalEscape);

        // Agregar event listener para cerrar haciendo clic fuera
        modal.addEventListener('click', handleBiometricModalOutsideClick);

        console.log('✅ Modal de ayuda biométrica mostrado correctamente');
    } else {
        console.error('❌ Modal de ayuda biométrica no encontrado');
    }
}

function closeBiometricHelpModal() {
    console.log('🎯 Cerrando modal de ayuda biométrica personalizado...');

    const modal = document.getElementById('biometricHelpModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';

        // Remover event listeners
        document.removeEventListener('keydown', handleBiometricModalEscape);
        modal.removeEventListener('click', handleBiometricModalOutsideClick);

        console.log('✅ Modal de ayuda biométrica cerrado correctamente');
    } else {
        console.error('❌ Modal de ayuda biométrica no encontrado para cerrar');
    }
}

function handleBiometricModalEscape(event) {
    if (event.key === 'Escape') {
        closeBiometricHelpModal();
    }
}

function handleBiometricModalOutsideClick(event) {
    const modal = document.getElementById('biometricHelpModal');
    if (event.target === modal) {
        closeBiometricHelpModal();
    }
}

// Función de compatibilidad para el código existente
function hideBiometricHelpModal() {
    closeBiometricHelpModal();
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Modal de ayuda biométrica personalizado inicializado');

    // Verificar que el modal existe
    const modal = document.getElementById('biometricHelpModal');
    if (modal) {
        console.log('✅ Modal de ayuda biométrica encontrado en el DOM');
    } else {
        console.warn('⚠️ Modal de ayuda biométrica no encontrado en el DOM');
    }
});
</script>