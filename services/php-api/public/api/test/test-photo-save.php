<?php
// Test script para verificar guardado de fotos
header('Content-Type: application/json');

// Simular datos de prueba
$id_empleado = $_POST['id_empleado'] ?? '999';
$verification_method = $_POST['verification_method'] ?? 'facial';
$confidence = $_POST['confidence'] ?? '0.95';
$verification_photo = $_POST['verification_photo'] ?? null;

error_log("=== TEST PHOTO SAVE ===");
error_log("ID Empleado: " . $id_empleado);
error_log("Método: " . $verification_method);
error_log("Confianza: " . $confidence);
error_log("Foto presente: " . (!empty($verification_photo) ? 'SI' : 'NO'));

// Directorio uploads
$uploads_dir = __DIR__ . '/../uploads/';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

$filename = null;

if ($verification_photo) {
    // Procesar imagen
    $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $verification_photo);
    $img_data = base64_decode($foto_base64_clean);

    if ($img_data !== false) {
        $prefix = $verification_method === 'facial' ? 'facial_' : 'att_';
        $filename = uniqid($prefix) . '_' . date('Ymd_His') . '.jpg';
        $save_path = $uploads_dir . $filename;

        if (file_put_contents($save_path, $img_data)) {
            error_log("TEST: Foto guardada en: " . $save_path);
            echo json_encode([
                'success' => true,
                'message' => 'Foto guardada correctamente',
                'filename' => $filename,
                'path' => $save_path
            ]);
        } else {
            error_log("TEST ERROR: No se pudo guardar la foto");
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar la foto'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de imagen inválido'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió foto'
    ]);
}
?>
