<?php
// Verificar que el directorio uploads existe y es writable
header('Content-Type: application/json');

$uploads_dir = __DIR__ . '/../uploads/';

$result = [
    'exists' => is_dir($uploads_dir),
    'writable' => is_writable($uploads_dir),
    'path' => $uploads_dir
];

echo json_encode($result);
?>
