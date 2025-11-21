<?php
// Función para generar imagen placeholder para huellas dactilares
function generateFingerprintPlaceholder($employee_id, $finger_type = 'index_right') {
    // Crear una imagen de 300x300 píxeles
    $width = 300;
    $height = 300;
    
    // Crear imagen en blanco
    $image = imagecreatetruecolor($width, $height);
    
    // Definir colores
    $white = imagecolorallocate($image, 255, 255, 255);
    $gray = imagecolorallocate($image, 150, 150, 150);
    $dark_gray = imagecolorallocate($image, 100, 100, 100);
    $blue = imagecolorallocate($image, 70, 130, 180);
    
    // Llenar fondo
    imagefill($image, 0, 0, $white);
    
    // Dibujar marco circular
    $center_x = $width / 2;
    $center_y = $height / 2;
    $radius = 120;
    
    // Marco exterior
    imageellipse($image, $center_x, $center_y, $radius * 2, $radius * 2, $gray);
    imageellipse($image, $center_x, $center_y, ($radius - 2) * 2, ($radius - 2) * 2, $gray);
    
    // Dibujar patrón de huella simplificado
    for ($i = 0; $i < 8; $i++) {
        $angle = ($i * 45) * pi() / 180;
        $x1 = $center_x + cos($angle) * 40;
        $y1 = $center_y + sin($angle) * 40;
        $x2 = $center_x + cos($angle) * 80;
        $y2 = $center_y + sin($angle) * 80;
        
        imageline($image, $x1, $y1, $x2, $y2, $dark_gray);
    }
    
    // Dibujar círculos concéntricos
    for ($r = 30; $r <= 100; $r += 20) {
        imageellipse($image, $center_x, $center_y, $r * 2, $r * 2, $gray);
    }
    
    // Añadir icono de huella en el centro
    $font_size = 5;
    $text = "HUELLA";
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $text_x = ($width - $text_width) / 2;
    $text_y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $text_x, $text_y, $text, $blue);
    
    // Añadir información del empleado
    $employee_text = "ID: " . $employee_id;
    $finger_text = strtoupper(str_replace('_', ' ', $finger_type));
    
    imagestring($image, 3, 10, 10, $employee_text, $dark_gray);
    imagestring($image, 3, 10, $height - 30, $finger_text, $dark_gray);
    
    // Convertir a base64
    ob_start();
    imagepng($image);
    $image_data = ob_get_contents();
    ob_end_clean();
    
    // Limpiar memoria
    imagedestroy($image);
    
    return 'data:image/png;base64,' . base64_encode($image_data);
}

// Función para obtener placeholder específico según método de verificación
function getBiometricPlaceholder($verification_method, $employee_id = null, $finger_type = 'index_right') {
    switch ($verification_method) {
        case 'fingerprint':
            return generateFingerprintPlaceholder($employee_id ?: 'XXXX', $finger_type);
        
        case 'facial':
            // Para facial, usar una imagen de avatar genérico
            return 'data:image/svg+xml;base64,' . base64_encode('
                <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
                    <rect width="300" height="300" fill="#f8f9fa"/>
                    <circle cx="150" cy="120" r="40" fill="#6c757d"/>
                    <ellipse cx="150" cy="220" rx="60" ry="40" fill="#6c757d"/>
                    <text x="150" y="260" text-anchor="middle" font-family="Arial" font-size="14" fill="#495057">RECONOCIMIENTO FACIAL</text>
                    <text x="150" y="280" text-anchor="middle" font-family="Arial" font-size="12" fill="#6c757d">ID: ' . ($employee_id ?: 'XXXX') . '</text>
                </svg>
            ');
        
        case 'traditional':
        default:
            // Placeholder para foto tradicional
            return 'data:image/svg+xml;base64,' . base64_encode('
                <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
                    <rect width="300" height="300" fill="#e9ecef"/>
                    <rect x="100" y="100" width="100" height="100" fill="#6c757d"/>
                    <circle cx="130" cy="130" r="10" fill="#495057"/>
                    <polygon points="110,180 150,160 190,180 190,190 110,190" fill="#495057"/>
                    <text x="150" y="220" text-anchor="middle" font-family="Arial" font-size="14" fill="#495057">FOTOGRAFÍA</text>
                    <text x="150" y="240" text-anchor="middle" font-family="Arial" font-size="12" fill="#6c757d">ID: ' . ($employee_id ?: 'XXXX') . '</text>
                </svg>
            ');
    }
}

// Si se llama directamente este archivo, generar y mostrar imagen
if (basename($_SERVER['PHP_SELF']) === 'biometric_placeholder.php') {
    $method = $_GET['method'] ?? 'traditional';
    $employee_id = $_GET['employee_id'] ?? null;
    $finger_type = $_GET['finger_type'] ?? 'index_right';
    
    $placeholder = getBiometricPlaceholder($method, $employee_id, $finger_type);
    
    // Extraer tipo de imagen y datos
    if (strpos($placeholder, 'data:image/png;base64,') === 0) {
        header('Content-Type: image/png');
        echo base64_decode(substr($placeholder, 22));
    } elseif (strpos($placeholder, 'data:image/svg+xml;base64,') === 0) {
        header('Content-Type: image/svg+xml');
        echo base64_decode(substr($placeholder, 26));
    }
    exit;
}
?>
