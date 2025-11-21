<?php
header('Content-Type: application/json');
header('Access-    // Crear tabla attendance si no existe
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            verification_method ENUM('facial', 'fingerprint', 'rfid', 'manual') NOT NULL,
            confidence DECIMAL(5,2) NULL,
            timestamp DATETIME NOT NULL,
            status ENUM('success', 'failed', 'pending') DEFAULT 'success',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES empleado(ID_EMPLEADO) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableQuery);rigin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos no válidos'
        ]);
        exit;
    }

    $employee_id = $input['employee_id'] ?? null;
    $verification_method = $input['verification_method'] ?? null;
    $confidence = $input['confidence'] ?? null;
    $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

    if (!$employee_id || !$verification_method) {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos requeridos: employee_id y verification_method'
        ]);
        exit;
    }

    // Verificar que el empleado existe
    $checkEmployee = $pdo->prepare("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $checkEmployee->execute([$employee_id]);
    $employee = $checkEmployee->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode([
            'success' => false,
            'message' => 'Empleado no encontrado o inactivo'
        ]);
        exit;
    }

    // Verificar que no haya una asistencia reciente (evitar duplicados)
    $checkRecent = $pdo->prepare("
        SELECT id FROM attendance
        WHERE employee_id = ?
        AND DATE(timestamp) = CURDATE()
        AND verification_method = ?
        AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $checkRecent->execute([$employee_id, $verification_method]);
    $recentAttendance = $checkRecent->fetch(PDO::FETCH_ASSOC);

    if ($recentAttendance) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una verificación reciente para este empleado con el mismo método'
        ]);
        exit;
    }

    // Crear tabla asistencia si no existe
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS asistencia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            verification_method ENUM('facial', 'fingerprint', 'rfid', 'manual') NOT NULL,
            confidence DECIMAL(5,2) NULL,
            timestamp DATETIME NOT NULL,
            status ENUM('success', 'failed', 'pending') DEFAULT 'success',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES empleado(ID_EMPLEADO) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableQuery);

    // Insertar registro de asistencia
    $insertQuery = "
        INSERT INTO attendance
        (employee_id, verification_method, confidence, timestamp, status, notes)
        VALUES (?, ?, ?, ?, 'success', ?)
    ";

    $notes = "Verificación biométrica exitosa - Confianza: " . ($confidence ? $confidence . '%' : 'N/A');

    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        $employee_id,
        $verification_method,
        $confidence,
        $timestamp,
        $notes
    ]);

    $attendanceId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Asistencia registrada exitosamente',
        'attendance_id' => $attendanceId,
        'employee_name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'verification_method' => $verification_method,
        'timestamp' => $timestamp
    ]);

} catch (Exception $e) {
    error_log("Error en record-attendance.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar asistencia: ' . $e->getMessage()
    ]);
}
?>
