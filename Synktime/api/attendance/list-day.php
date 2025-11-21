<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$userRole = $_SESSION['rol'] ?? '';

$where = ['e.ACTIVO = "S"'];
$params = [];

// Capturar filtros
$sede = $_GET['sede'] ?? '';
$establecimiento = $_GET['establecimiento'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$nombre = $_GET['nombre'] ?? '';

// Agregamos filtros si existen
if ($sede) {
    $where[] = 's.ID_SEDE = :sede';
    $params[':sede'] = $sede;
}
if ($establecimiento) {
    $where[] = 'est.ID_ESTABLECIMIENTO = :establecimiento';
    $params[':establecimiento'] = $establecimiento;
}
if ($codigo) {
    $where[] = 'e.ID_EMPLEADO = :codigo';
    $params[':codigo'] = $codigo;
}
if ($nombre) {
    $nombreBusqueda = trim($nombre);
    $palabras = array_filter(explode(' ', $nombreBusqueda)); // Separar por espacios y filtrar vacíos
    
    if (count($palabras) > 1) {
        // Si hay múltiples palabras, buscar cada una en nombre o apellido
        $condiciones = [];
        foreach ($palabras as $index => $palabra) {
            $paramNombre = ":nombre_{$index}";
            $paramApellido = ":apellido_{$index}";
            $condiciones[] = "(e.NOMBRE LIKE {$paramNombre} OR e.APELLIDO LIKE {$paramApellido})";
            $params[$paramNombre] = '%' . $palabra . '%';
            $params[$paramApellido] = '%' . $palabra . '%';
        }
        $where[] = '(' . implode(' AND ', $condiciones) . ')';
    } else {
        // Si es una sola palabra, buscar en nombre, apellido o combinación
        $where[] = '(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre OR CONCAT(e.NOMBRE, " ", e.APELLIDO) LIKE :nombre)';
        $params[':nombre'] = '%' . $nombreBusqueda . '%';
    }
}

// Lógica corregida: mostrar empleados que tuvieron ENTRADA en últimas 20 horas
// y TODAS sus asistencias RECIENTES (incluyendo de ayer si la entrada fue reciente)
$fecha_20_horas_atras = date('Y-m-d H:i:s', strtotime('-20 hours'));

// Construir la condición WHERE para la subconsulta
$subquery_where = "WHERE a.ID_EMPLEADO IN (
    SELECT DISTINCT a2.ID_EMPLEADO
    FROM ASISTENCIA a2
    WHERE a2.TIPO = 'ENTRADA'
    AND CONCAT(a2.FECHA, ' ', a2.HORA) >= :fecha_20_horas_atras
)";

// También filtrar que las asistencias mostradas sean de las últimas 20 horas
$subquery_where .= " AND CONCAT(a.FECHA, ' ', a.HORA) >= :fecha_20_horas_atras";

// Utilizamos subconsultas para obtener la última entrada y salida para cada combinación empleado/horario/fecha
$sql = "
SELECT
  e.ID_EMPLEADO,
  e.NOMBRE,
  e.APELLIDO,
  est.NOMBRE AS establecimiento,
  s.NOMBRE AS sede,
  a.OBSERVACION as observacion,
  h.ID_HORARIO,
  h.NOMBRE AS HORARIO_NOMBRE,
  h.HORA_ENTRADA AS HORA_ENTRADA_PROGRAMADA,
  h.HORA_SALIDA AS HORA_SALIDA_PROGRAMADA,
  h.TOLERANCIA,
  a_fecha.FECHA,

  -- Entrada (seleccionamos el registro de entrada más reciente para cada combinación empleado/horario/fecha)
  entrada.ID_ASISTENCIA AS ENTRADA_ID,
  entrada.HORA AS ENTRADA_HORA,
  entrada.TARDANZA AS ENTRADA_TARDANZA,
  entrada.FOTO AS ENTRADA_FOTO,

  -- Salida (seleccionamos el registro de salida más reciente para cada combinación empleado/horario/fecha)
  salida.ID_ASISTENCIA AS SALIDA_ID,
  salida.HORA AS SALIDA_HORA,
  salida.TARDANZA AS SALIDA_TARDANZA,
  salida.FOTO AS SALIDA_FOTO

FROM EMPLEADO e
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE

-- Subconsulta para obtener fechas únicas de asistencia según permisos de rol
JOIN (
    SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
    FROM ASISTENCIA a
    $subquery_where
) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO

-- Unión con HORARIO a través del ID_HORARIO en la asistencia
LEFT JOIN HORARIO h ON h.ID_HORARIO = a_fecha.ID_HORARIO

-- Subconsulta para obtener la entrada más reciente
LEFT JOIN (
    SELECT a_entrada.ID_ASISTENCIA, a_entrada.ID_EMPLEADO, a_entrada.FECHA, a_entrada.ID_HORARIO, 
           a_entrada.HORA, a_entrada.TARDANZA, a_entrada.FOTO
    FROM ASISTENCIA a_entrada
    WHERE a_entrada.TIPO = 'ENTRADA'
    AND NOT EXISTS (
        SELECT 1 FROM ASISTENCIA a2
        WHERE a2.ID_EMPLEADO = a_entrada.ID_EMPLEADO
        AND a2.FECHA = a_entrada.FECHA
        AND a2.ID_HORARIO = a_entrada.ID_HORARIO
        AND a2.TIPO = 'ENTRADA'
        AND a2.ID_ASISTENCIA > a_entrada.ID_ASISTENCIA
    )
) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO 
              AND a_fecha.FECHA = entrada.FECHA
              AND a_fecha.ID_HORARIO = entrada.ID_HORARIO

-- Subconsulta para obtener la salida más reciente
LEFT JOIN (
    SELECT a_salida.ID_ASISTENCIA, a_salida.ID_EMPLEADO, a_salida.FECHA, a_salida.ID_HORARIO, 
           a_salida.HORA, a_salida.TARDANZA, a_salida.FOTO
    FROM ASISTENCIA a_salida
    WHERE a_salida.TIPO = 'SALIDA'
    AND NOT EXISTS (
        SELECT 1 FROM ASISTENCIA a2
        WHERE a2.ID_EMPLEADO = a_salida.ID_EMPLEADO
        AND a2.FECHA = a_salida.FECHA
        AND a2.ID_HORARIO = a_salida.ID_HORARIO
        AND a2.TIPO = 'SALIDA'
        AND a2.ID_ASISTENCIA > a_salida.ID_ASISTENCIA
    )
) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO 
             AND a_fecha.FECHA = salida.FECHA
             AND a_fecha.ID_HORARIO = salida.ID_HORARIO

WHERE " . implode(' AND ', $where) . "
GROUP BY e.ID_EMPLEADO, a_fecha.FECHA, a_fecha.ID_HORARIO
ORDER BY a_fecha.FECHA DESC, h.HORA_ENTRADA ASC
";

$stmt = $conn->prepare($sql);

// Agregar parámetros
$params[':fecha_20_horas_atras'] = $fecha_20_horas_atras;

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();

$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result = [];

// Procesamos para calcular los estados
foreach ($asistencias as $registro) {
    // Calculamos el estado de la entrada
    $estado_entrada = '--';
    if ($registro['ENTRADA_HORA'] && $registro['HORA_ENTRADA_PROGRAMADA']) {
        $ts_entrada_programada = strtotime($registro['FECHA'] . ' ' . $registro['HORA_ENTRADA_PROGRAMADA']);
        $ts_entrada_real = strtotime($registro['FECHA'] . ' ' . $registro['ENTRADA_HORA']);
        $tolerancia = (int)($registro['TOLERANCIA'] ?? 0);
        
        if ($ts_entrada_real < $ts_entrada_programada) {
            $estado_entrada = 'Temprano';
        } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
            $estado_entrada = 'Puntual';
        } else {
            $estado_entrada = 'Tardanza';
        }
    }
    
    // Calculamos el estado de la salida
    $estado_salida = '--';
    if ($registro['SALIDA_HORA'] && $registro['HORA_SALIDA_PROGRAMADA']) {
        $ts_salida_programada = strtotime($registro['FECHA'] . ' ' . $registro['HORA_SALIDA_PROGRAMADA']);
        $ts_salida_real = strtotime($registro['FECHA'] . ' ' . $registro['SALIDA_HORA']);
        $tolerancia = (int)($registro['TOLERANCIA'] ?? 0);
        
        if ($ts_salida_real < $ts_salida_programada - $tolerancia * 60) {
            $estado_salida = 'Temprano';
        } elseif ($ts_salida_real <= $ts_salida_programada + $tolerancia * 60) {
            $estado_salida = 'Normal';
        } else {
            $estado_salida = 'Tardanza';
        }
    }
    
    // Añadir estados al registro
    $registro['ENTRADA_ESTADO'] = $estado_entrada;
    $registro['SALIDA_ESTADO'] = $estado_salida;
    
    $result[] = $registro;
}

echo json_encode(['success' => true, 'data' => $result]);
?>