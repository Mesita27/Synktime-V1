<?php
// modal_ayuda_biometrico.php - Modal de ayuda optimizado para el módulo de inscripción biométrica
?>
<!-- Modal de Ayuda usando Bootstrap Modal Optimizado -->
<div class="modal fade" id="modalAyudaBiometrico" tabindex="-1" aria-labelledby="modalAyudaBiometricoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #667eea; color: #fff;">
                <h5 class="modal-title" id="modalAyudaBiometricoLabel">
                    <i class="fas fa-fingerprint me-2"></i>
                    Ayuda - Inscripción Biométrica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="help-content">

                    <!-- Introducción Optimizada -->
                    <div class="alert alert-info" style="background: #f8f9ff; border: 1px solid #e3f2fd; border-radius: 8px;">
                        <h6 style="color: #2c3e50; margin-bottom: 8px;"><i class="fas fa-info-circle me-2"></i>¿Qué es la Inscripción Biométrica?</h6>
                        <p style="margin: 0; color: #6c757d; line-height: 1.5;">La inscripción biométrica permite registrar las características faciales de los empleados para un reconocimiento automático durante el registro de asistencia.</p>
                    </div>

                    <!-- Funcionalidades Principales Optimizadas -->
                    <h6 style="color: #667eea; margin: 20px 0 12px 0;"><i class="fas fa-cogs me-2"></i>Funcionalidades Principales</h6>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 10px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                            <i class="fas fa-user-plus" style="color: #4caf50; font-size: 16px; margin-top: 2px;"></i>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 4px;">Registrar empleados</strong>
                                <small style="color: #6c757d;">Inscribir nuevos empleados en el sistema biométrico</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                            <i class="fas fa-camera" style="color: #2196f3; font-size: 16px; margin-top: 2px;"></i>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 4px;">Captura facial</strong>
                                <small style="color: #6c757d;">Tomar fotos del rostro del empleado</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                            <i class="fas fa-check-circle" style="color: #ff9800; font-size: 16px; margin-top: 2px;"></i>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 4px;">Verificación automática</strong>
                                <small style="color: #6c757d;">Validar que la captura sea correcta</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                            <i class="fas fa-users" style="color: #667eea; font-size: 16px; margin-top: 2px;"></i>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 4px;">Gestión masiva</strong>
                                <small style="color: #6c757d;">Administrar múltiples inscripciones</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pasos Optimizados -->
                    <h6 style="color: #667eea; margin: 20px 0 12px 0;"><i class="fas fa-play-circle me-2"></i>Cómo Usar la Inscripción Biométrica</h6>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">1</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Seleccionar empleado</strong>
                                <small style="color: #6c757d;">Elige el empleado de la lista disponible</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">2</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Posicionamiento</strong>
                                <small style="color: #6c757d;">Asegúrate de que el empleado mire directamente a la cámara</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">3</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Iluminación</strong>
                                <small style="color: #6c757d;">Verifica buena iluminación en el rostro</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">4</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Captura automática</strong>
                                <small style="color: #6c757d;">El sistema detectará automáticamente el rostro</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">5</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Verificación</strong>
                                <small style="color: #6c757d;">Confirma que la imagen capturada es clara y nítida</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="width: 24px; height: 24px; background: #667eea; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; flex-shrink: 0;">6</span>
                            <div>
                                <strong style="color: #2c3e50; display: block; margin-bottom: 2px;">Guardar</strong>
                                <small style="color: #6c757d;">Registra la inscripción en el sistema</small>
                            </div>
                        </div>
                    </div>

                    <!-- Recomendaciones Optimizadas -->
                    <h6 style="color: #667eea; margin: 20px 0 12px 0;"><i class="fas fa-lightbulb me-2"></i>Recomendaciones para una Buena Captura</h6>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
                        <div style="background: #fff3cd; padding: 12px; border-radius: 6px; border: 1px solid #ffeaa7;">
                            <h6 style="color: #856404; margin-bottom: 8px;"><i class="fas fa-sun me-2"></i>Iluminación</h6>
                            <ul style="margin: 0; padding-left: 16px; color: #6c757d; font-size: 13px;">
                                <li>Luz natural o iluminación blanca uniforme</li>
                                <li>Evita sombras fuertes en el rostro</li>
                                <li>No uses flash directo</li>
                            </ul>
                        </div>
                        <div style="background: #d1ecf1; padding: 12px; border-radius: 6px; border: 1px solid #bee5eb;">
                            <h6 style="color: #0c5460; margin-bottom: 8px;"><i class="fas fa-eye me-2"></i>Posición del Rostro</h6>
                            <ul style="margin: 0; padding-left: 16px; color: #6c757d; font-size: 13px;">
                                <li>Mira directamente a la cámara</li>
                                <li>Mantén la cabeza erguida</li>
                                <li>Evita ángulos extremos</li>
                            </ul>
                        </div>
                        <div style="background: #d4edda; padding: 12px; border-radius: 6px; border: 1px solid #c3e6cb;">
                            <h6 style="color: #155724; margin-bottom: 8px;"><i class="fas fa-camera me-2"></i>Calidad de Imagen</h6>
                            <ul style="margin: 0; padding-left: 16px; color: #6c757d; font-size: 13px;">
                                <li>Distancia: 30-50 cm de la cámara</li>
                                <li>Resolución mínima: 640x480 píxeles</li>
                                <li>Formatos: JPG o PNG</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Alertas Optimizadas -->
                    <div style="background: #fff3cd; padding: 12px; border-radius: 6px; border: 1px solid #ffeaa7; margin-bottom: 12px;">
                        <h6 style="color: #856404; margin-bottom: 8px;"><i class="fas fa-exclamation-triangle me-2"></i>Consideraciones Importantes</h6>
                        <ul style="margin: 0; padding-left: 16px; color: #6c757d; font-size: 13px;">
                            <li><strong>Privacidad:</strong> Las imágenes se almacenan de forma segura y encriptada</li>
                            <li><strong>Consentimiento:</strong> Asegúrate de tener el consentimiento del empleado</li>
                            <li><strong>Actualización:</strong> Re-inscribe si hay cambios significativos en la apariencia</li>
                        </ul>
                    </div>

                    <div style="background: #d4edda; padding: 12px; border-radius: 6px; border: 1px solid #c3e6cb;">
                        <h6 style="color: #155724; margin-bottom: 8px;"><i class="fas fa-check-circle me-2"></i>Consejos para un Mejor Rendimiento</h6>
                        <ul style="margin: 0; padding-left: 16px; color: #6c757d; font-size: 13px;">
                            <li>Realiza las inscripciones en un ambiente controlado</li>
                            <li>Verifica la calidad de la imagen antes de guardar</li>
                            <li>Mantén actualizado el software de reconocimiento facial</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>