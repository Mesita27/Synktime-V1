<?php
/**
 * API de respaldo que proporciona datos de empleados simulados
 * Útil cuando hay problemas con la base de datos
 */

// Encabezados necesarios
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Simular retardo para emular consulta a base de datos
usleep(200000); // 200ms

// Generar datos simulados
$employees = [];
$names = ['Juan', 'María', 'Pedro', 'Ana', 'Carlos', 'Laura', 'Miguel', 'Isabel', 'Roberto', 'Elena', 
          'José', 'Lucía', 'Francisco', 'Patricia', 'Fernando', 'Sofía', 'Alberto', 'Carmen', 'David', 'Cristina'];
$lastnames = ['Pérez', 'González', 'Rodríguez', 'López', 'Martínez', 'Sánchez', 'Fernández', 'García', 'Díaz', 'Torres',
             'Ramírez', 'Morales', 'Ortiz', 'Vargas', 'Jiménez', 'Castillo', 'Romero', 'Navarro', 'Moreno', 'Vega'];
$establishments = ['Sede Principal', 'Sucursal Norte', 'Sucursal Sur', 'Oficina Este', 'Oficina Oeste', 'Centro Comercial'];
$sedes = ['Sede Central', 'Sede Norte', 'Sede Sur'];
$biometricStatuses = ['enrolled', 'pending', 'partial'];

// Generar entre 20 y 50 empleados
$count = rand(20, 50);

for ($i = 1; $i <= $count; $i++) {
    $name = $names[array_rand($names)];
    $lastname = $lastnames[array_rand($lastnames)];
    $establishment = $establishments[array_rand($establishments)];
    $sede = $sedes[array_rand($sedes)];
    $biometricStatus = $biometricStatuses[array_rand($biometricStatuses)];
    $codigo = 'E' . str_pad($i, 3, '0', STR_PAD_LEFT);
    
    // Determinar estado de inscripciones según el estado biométrico
    $facialEnrolled = false;
    $fingerprintEnrolled = false;
    
    switch ($biometricStatus) {
        case 'enrolled':
            $facialEnrolled = true;
            $fingerprintEnrolled = true;
            break;
        case 'partial':
            // Aleatoriamente elegir cuál está inscrito
            if (rand(0, 1) == 1) {
                $facialEnrolled = true;
            } else {
                $fingerprintEnrolled = true;
            }
            break;
    }
    
    // Generar fecha de última actualización para los que tienen alguna inscripción
    $lastUpdated = null;
    if ($facialEnrolled || $fingerprintEnrolled) {
        $daysAgo = rand(1, 60);
        $lastUpdated = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
    }
    
    // Añadir empleado
    $employees[] = [
        'id' => $i,
        'ID_EMPLEADO' => $i,
        'codigo' => $codigo,
        'nombre' => "$name $lastname",
        'NOMBRE' => $name,
        'APELLIDO' => $lastname,
        'establecimiento' => $establishment,
        'ESTABLECIMIENTO' => $establishment,
        'sede' => $sede,
        'SEDE' => $sede,
        'biometric_status' => $biometricStatus,
        'facial_enrolled' => $facialEnrolled,
        'fingerprint_enrolled' => $fingerprintEnrolled,
        'last_updated' => $lastUpdated
    ];
}

// Responder con los datos
echo json_encode([
    'success' => true,
    'message' => 'Datos simulados generados correctamente',
    'count' => count($employees),
    'data' => $employees,
    'mock' => true
]);
?>
