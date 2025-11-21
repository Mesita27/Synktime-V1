<?php
// Verificar que un archivo existe en uploads
header('Content-Type: application/json');

$filename = $_GET['filename'] ?? '';
$uploads_dir = __DIR__ . '/../uploads/';
$file_path = $uploads_dir . $filename;

$result = [
    'filename' => $filename,
    'exists' => file_exists($file_path),
    'path' => $file_path
];

if ($result['exists']) {
    $result['size'] = filesize($file_path);
    $result['modified'] = date('Y-m-d H:i:s', filemtime($file_path));
}

echo json_encode($result);
?>
