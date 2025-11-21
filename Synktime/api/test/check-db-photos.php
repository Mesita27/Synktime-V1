<?php
// Verificar estado de la base de datos para fotos
header('Content-Type: application/json');

require_once '../config.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);

    // Verificar tabla asistencia
    $stmt = $pdo->query("DESCRIBE asistencia");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_verification_photo = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'FOTO') {
            $has_verification_photo = true;
            break;
        }
    }

    // Contar registros con fotos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia WHERE FOTO IS NOT NULL AND FOTO != ''");
    $photo_count = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener registros recientes con fotos
    $stmt = $pdo->query("SELECT ID_ASISTENCIA, ID_EMPLEADO, FECHA, HORA, FOTO FROM asistencia WHERE FOTO IS NOT NULL AND FOTO != '' ORDER BY CREATED_AT DESC LIMIT 5");
    $recent_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [
        'table_exists' => true,
        'has_verification_photo_column' => $has_verification_photo,
        'photo_records_count' => $photo_count['total'],
        'recent_photos' => $recent_photos,
        'columns' => array_column($columns, 'Field')
    ];

} catch (Exception $e) {
    $result = [
        'error' => $e->getMessage(),
        'table_exists' => false
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
