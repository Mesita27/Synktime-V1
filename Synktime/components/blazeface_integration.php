<?php
/**
 * Integrador BlazeFace para Synktime
 * Este archivo carga los scripts y estilos necesarios para BlazeFace
 */
?>

<!-- TensorFlow.js y BlazeFace -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js"></script>

<!-- CSS y JS para la implementación de BlazeFace -->
<link rel="stylesheet" href="assets/css/biometric-blazeface.css">
<script src="assets/js/biometric-blazeface.js"></script>

<script>
// Script para integrar BlazeFace
document.addEventListener('DOMContentLoaded', function() {
    console.log('Integrando BlazeFace en Synktime');
    
    // Verificar si es necesario crear las tablas para biometría
    function checkBiometricTables() {
        // Envío a servidor mediante AJAX para verificar/crear tablas
        $.ajax({
            url: 'api/employee/check_biometric_tables.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Tablas biométricas verificadas');
                } else {
                    console.warn('Advertencia en tablas biométricas:', response.message);
                }
            },
            error: function() {
                console.error('Error al verificar tablas biométricas');
            }
        });
    }
    
    // Llamar a la verificación de tablas
    checkBiometricTables();
});
</script>
