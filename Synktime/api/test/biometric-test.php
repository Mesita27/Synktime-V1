<?php
// API de diagnóstico para el módulo biométrico
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para pruebas locales

// Crear datos de prueba
$empleados = [];

// Generar 10 empleados de prueba
for ($i = 1; $i <= 10; $i++) {
    $empleados[] = [
        'id' => $i,
        'ID_EMPLEADO' => $i,
        'nombre' => "Nombre$i",
        'NOMBRE' => "Nombre$i", // Duplicado para compatibilidad
        'apellido' => "Apellido$i",
        'APELLIDO' => "Apellido$i", // Duplicado para compatibilidad
        'establecimiento' => "Establecimiento " . ($i % 3 + 1),
        'ESTABLECIMIENTO' => "Establecimiento " . ($i % 3 + 1), // Duplicado para compatibilidad
        'sede' => "Sede " . ($i % 2 + 1),
        'SEDE' => "Sede " . ($i % 2 + 1), // Duplicado para compatibilidad
        'ID_SEDE' => ($i % 2 + 1),
        'ID_ESTABLECIMIENTO' => ($i % 3 + 1),
        'correo' => "empleado$i@example.com",
        'telefono' => "123456789$i",
        'fecha_contratacion' => date('Y-m-d', strtotime("-$i months")),
        'estado' => 'A'
    ];
}

// Formato de respuesta similar a las APIs del sistema
$response = [
    'success' => true,
    'message' => 'Datos de prueba para biometría',
    'data' => $empleados, // Formato principal
    'employees' => $empleados, // Formato alternativo para compatibilidad
    'timestamp' => date('Y-m-d H:i:s'),
    'info' => 'Este es un archivo de prueba para diagnóstico de compatibilidad de formatos JSON'
];

// Devolver la respuesta JSON
echo json_encode($response);
