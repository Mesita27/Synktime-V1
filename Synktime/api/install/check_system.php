<?php
/**
 * Verificador del sistema biométrico
 */
header('Content-Type: application/json');

try {
    // Verificar si TensorFlow.js está disponible
    $tfJsCheck = file_exists('../../assets/js/biometric-blazeface.js');
    
    // Verificar si los archivos CSS están disponibles
    $cssCheck = file_exists('../../assets/css/biometric-blazeface.css');
    
    // Verificar si está el modal
    $modalCheck = file_exists('../../components/biometric_enrollment_modal.php');
    
    // Comprobar si todo está bien
    $allOk = $tfJsCheck && $cssCheck && $modalCheck;
    
    $missingComponents = [];
    if (!$tfJsCheck) $missingComponents[] = 'Script de BlazeFace';
    if (!$cssCheck) $missingComponents[] = 'Estilos CSS';
    if (!$modalCheck) $missingComponents[] = 'Modal de enrolamiento';
    
    echo json_encode([
        'success' => $allOk,
        'message' => $allOk ? 'Todos los componentes del sistema biométrico están instalados correctamente' : 
                              'Faltan algunos componentes: ' . implode(', ', $missingComponents),
        'components' => [
            'blazeface_js' => $tfJsCheck,
            'blazeface_css' => $cssCheck,
            'modal' => $modalCheck
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar el sistema: ' . $e->getMessage()
    ]);
}
?>
