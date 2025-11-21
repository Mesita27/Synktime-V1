<?php
// Limpiar cualquier output anterior
ob_clean();

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Simular respuesta de validación
$response = [
    'success' => true,
    'can_register' => true,
    'block_reason' => '',
    'validation_details' => [
        'has_open_entries' => false,
        'available_schedules' => 1,
        'completed_schedules' => 0,
        'exceeded_time_limit' => false,
        'current_schedule_limit' => 1,
        'current_time' => date('H:i:s')
    ],
    'schedules' => [
        [
            'ID_HORARIO' => 1,
            'NOMBRE_HORARIO' => 'Horario Regular',
            'HORA_ENTRADA' => '08:00:00',
            'HORA_SALIDA' => '17:00:00'
        ]
    ],
    'today_attendance' => []
];

echo json_encode($response);
?>
