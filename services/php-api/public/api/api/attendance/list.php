<?php
// Limpiar cualquier salida previa
ob_clean();

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    if (!$currentUser || !$currentUser['id_empresa']) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }
    
    $empresaId = $currentUser['id_empresa'];
    $userRole = $currentUser['rol'];
    
    // Validar que tengamos conexión a la base de datos
    if (!isset($conn)) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Filtros
    $filtros = [
        'codigo' => $_GET['codigo'] ?? '',
        'sede' => $_GET['sede'] ?? '',
        'establecimiento' => $_GET['establecimiento'] ?? '',
        'nombre' => $_GET['nombre'] ?? ''
    ];

    // NUEVA LÓGICA SEGÚN ESPECIFICACIONES:
    // 1. Buscar SOLO ENTRADAS de la empresa en las últimas 20 horas
    // 2. Para cada entrada, buscar salida correspondiente por ID_EMPLEADO_HORARIO en las siguientes 20 horas
    // 3. Aplicar filtros básicos
    $fecha_20_horas_atras = date('Y-m-d H:i:s', strtotime('-20 hours'));

    // Construir WHERE clause para filtros básicos
    $whereBase = ["s.ID_EMPRESA = :empresa_id"];
    $paramsBase = [':empresa_id' => $empresaId];

    // Aplicar filtros básicos
    if ($filtros['codigo']) {
        $whereBase[] = "e.ID_EMPLEADO = :codigo";
        $paramsBase[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['sede']) {
        $whereBase[] = "s.ID_SEDE = :sede";
        $paramsBase[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $whereBase[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $paramsBase[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['nombre']) {
        $nombreBusqueda = trim($filtros['nombre']);
        $palabras = array_filter(explode(' ', $nombreBusqueda)); // Separar por espacios y filtrar vacíos
        
        if (count($palabras) > 1) {
            // Si hay múltiples palabras, buscar cada una en nombre o apellido
            $condiciones = [];
            foreach ($palabras as $index => $palabra) {
                $paramNombre = ":nombre_{$index}";
                $paramApellido = ":apellido_{$index}";
                $condiciones[] = "(e.NOMBRE LIKE {$paramNombre} OR e.APELLIDO LIKE {$paramApellido})";
                $paramsBase[$paramNombre] = '%' . $palabra . '%';
                $paramsBase[$paramApellido] = '%' . $palabra . '%';
            }
            $whereBase[] = '(' . implode(' AND ', $condiciones) . ')';
        } else {
            // Si es una sola palabra, buscar en nombre, apellido o combinación
            $whereBase[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre OR CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE :nombre)";
            $paramsBase[':nombre'] = '%' . $nombreBusqueda . '%';
        }
    }

    $whereBaseClause = implode(' AND ', $whereBase);

    // CONSULTA 1: Obtener ENTRADAS de las últimas 20 horas con filtros aplicados
    $entradasSql = "
        SELECT
            a.ID_ASISTENCIA as id_entrada,
            e.ID_EMPLEADO as codigo_empleado,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_empleado,
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            a.FECHA as fecha_entrada,
            a.HORA as hora_entrada,
            a.TARDANZA as tardanza_entrada,
            a.OBSERVACION as observacion_entrada,
            a.FOTO as foto_entrada,
            a.REGISTRO_MANUAL as registro_manual_entrada,
            a.ID_HORARIO,
            a.ID_EMPLEADO_HORARIO,
            -- Información del horario tradicional
            h.NOMBRE as HORARIO_NOMBRE,
            h.HORA_ENTRADA as HORA_ENTRADA,
            h.HORA_SALIDA as HORA_SALIDA,
            h.TOLERANCIA as TOLERANCIA,
            -- Información del horario personalizado
            ehp.NOMBRE_TURNO as NOMBRE_TURNO,
            ehp.HORA_ENTRADA as HORA_ENTRADA_PERSONALIZADO,
            ehp.HORA_SALIDA as HORA_SALIDA_PERSONALIZADO,
            ehp.TOLERANCIA as TOLERANCIA_PERSONALIZADA,
            ehp.ACTIVO as HORARIO_PERSONALIZADO_ACTIVO,
            ehp.ES_TURNO_NOCTURNO as ES_TURNO_NOCTURNO,
            ehp.HORA_CORTE_NOCTURNO as HORA_CORTE_NOCTURNO,
            ds.NOMBRE as DIA_NOMBRE,
            ehp.ORDEN_TURNO as ORDEN_TURNO,
            ehp.FECHA_DESDE as FECHA_DESDE,
            ehp.FECHA_HASTA as FECHA_HASTA
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN HORARIO h ON a.ID_HORARIO = h.ID_HORARIO
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE {$whereBaseClause}
        AND a.TIPO = 'ENTRADA'
        AND CONCAT(a.FECHA, ' ', a.HORA) >= :fecha_20_horas_atras
        ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC
    ";

    error_log("Ejecutando consulta de entradas: " . $entradasSql);
    error_log("Parámetros: " . json_encode($paramsBase));

    $stmtEntradas = $conn->prepare($entradasSql);
    if (!$stmtEntradas) {
        throw new Exception('Error al preparar consulta de entradas: ' . $conn->errorInfo()[2]);
    }

    foreach ($paramsBase as $key => $value) {
        $stmtEntradas->bindValue($key, $value);
    }
    $stmtEntradas->bindValue(':fecha_20_horas_atras', $fecha_20_horas_atras);

    if (!$stmtEntradas->execute()) {
        throw new Exception('Error al ejecutar consulta de entradas: ' . $stmtEntradas->errorInfo()[2]);
    }

    $entradas = $stmtEntradas->fetchAll(PDO::FETCH_ASSOC);
    error_log("Encontradas " . count($entradas) . " entradas");

    // CONSULTA 2: Para cada entrada, buscar salida por ID_EMPLEADO_HORARIO en las siguientes 20 horas
    $result = [];
    foreach ($entradas as $entrada) {
        // DEBUG: Log de entrada procesada
        error_log("Procesando entrada: Empleado {$entrada['codigo_empleado']} - ID_HORARIO_EMPLEADO {$entrada['ID_EMPLEADO_HORARIO']} - {$entrada['fecha_entrada']} {$entrada['hora_entrada']}");

        $salida = null;

        // Si tiene ID_EMPLEADO_HORARIO (horario personalizado), buscar salida por este ID
        if ($entrada['ID_EMPLEADO_HORARIO']) {
            $salidaSql = "
                SELECT
                    a.ID_ASISTENCIA as id_salida,
                    a.FECHA as fecha_salida,
                    a.HORA as hora_salida,
                    a.TARDANZA as tardanza_salida,
                    a.OBSERVACION as observacion_salida,
                    a.FOTO as foto_salida,
                    a.REGISTRO_MANUAL as registro_manual_salida
                FROM ASISTENCIA a
                WHERE a.ID_EMPLEADO_HORARIO = :id_empleado_horario
                AND a.ID_EMPLEADO = :id_empleado
                AND a.TIPO = 'SALIDA'
                AND CONCAT(a.FECHA, ' ', a.HORA) > CONCAT(:fecha_entrada, ' ', :hora_entrada)
                AND CONCAT(a.FECHA, ' ', a.HORA) <= DATE_ADD(CONCAT(:fecha_entrada, ' ', :hora_entrada), INTERVAL 20 HOUR)
                ORDER BY CONCAT(a.FECHA, ' ', a.HORA) ASC
                LIMIT 1
            ";

            $stmtSalida = $conn->prepare($salidaSql);
            if (!$stmtSalida) {
                throw new Exception('Error al preparar consulta de salida: ' . $conn->errorInfo()[2]);
            }

            $stmtSalida->bindValue(':id_empleado_horario', $entrada['ID_EMPLEADO_HORARIO']);
            $stmtSalida->bindValue(':id_empleado', $entrada['codigo_empleado']);
            $stmtSalida->bindValue(':fecha_entrada', $entrada['fecha_entrada']);
            $stmtSalida->bindValue(':hora_entrada', $entrada['hora_entrada']);

            if (!$stmtSalida->execute()) {
                throw new Exception('Error al ejecutar consulta de salida: ' . $stmtSalida->errorInfo()[2]);
            }

            $salida = $stmtSalida->fetch(PDO::FETCH_ASSOC);
        }
        // Si no tiene horario personalizado o no encontró salida, buscar por horario tradicional
        elseif ($entrada['ID_HORARIO']) {
            $salidaSql = "
                SELECT
                    a.ID_ASISTENCIA as id_salida,
                    a.FECHA as fecha_salida,
                    a.HORA as hora_salida,
                    a.TARDANZA as tardanza_salida,
                    a.OBSERVACION as observacion_salida,
                    a.FOTO as foto_salida,
                    a.REGISTRO_MANUAL as registro_manual_salida
                FROM ASISTENCIA a
                WHERE a.ID_HORARIO = :id_horario
                AND a.ID_EMPLEADO = :id_empleado
                AND a.TIPO = 'SALIDA'
                AND CONCAT(a.FECHA, ' ', a.HORA) > CONCAT(:fecha_entrada, ' ', :hora_entrada)
                AND CONCAT(a.FECHA, ' ', a.HORA) <= DATE_ADD(CONCAT(:fecha_entrada, ' ', :hora_entrada), INTERVAL 20 HOUR)
                ORDER BY CONCAT(a.FECHA, ' ', a.HORA) ASC
                LIMIT 1
            ";

            $stmtSalida = $conn->prepare($salidaSql);
            if (!$stmtSalida) {
                throw new Exception('Error al preparar consulta de salida tradicional: ' . $conn->errorInfo()[2]);
            }

            $stmtSalida->bindValue(':id_horario', $entrada['ID_HORARIO']);
            $stmtSalida->bindValue(':id_empleado', $entrada['codigo_empleado']);
            $stmtSalida->bindValue(':fecha_entrada', $entrada['fecha_entrada']);
            $stmtSalida->bindValue(':hora_entrada', $entrada['hora_entrada']);

            if (!$stmtSalida->execute()) {
                throw new Exception('Error al ejecutar consulta de salida tradicional: ' . $stmtSalida->errorInfo()[2]);
            }

            $salida = $stmtSalida->fetch(PDO::FETCH_ASSOC);
        }

        // DEBUG: Log de salida encontrada
        error_log("Salida encontrada para entrada {$entrada['id_entrada']}: " . ($salida ? "ID {$salida['id_salida']} - {$salida['fecha_salida']} {$salida['hora_salida']}" : "NINGUNA"));

        // Crear registro combinado
        $registro = [
            'id' => $entrada['id_entrada'],
            'codigo_empleado' => $entrada['codigo_empleado'],
            'nombre_empleado' => $entrada['nombre_empleado'],
            'establecimiento' => $entrada['establecimiento'],
            'sede' => $entrada['sede'],
            'fecha' => $entrada['fecha_entrada'],
            'hora' => $entrada['hora_entrada'],
            'tipo' => 'ENTRADA',
            'tardanza' => $entrada['tardanza_entrada'],
            'observacion' => $entrada['observacion_entrada'],
            'foto' => $entrada['foto_entrada'],
            'registro_manual' => $entrada['registro_manual_entrada'],

            // Información del horario
            'ID_HORARIO' => $entrada['ID_HORARIO'],
            'HORARIO_NOMBRE' => $entrada['HORARIO_NOMBRE'] ?: 'Sin horario',
            'HORA_ENTRADA' => $entrada['HORA_ENTRADA'],
            'HORA_SALIDA' => $entrada['HORA_SALIDA'],
            'TOLERANCIA' => $entrada['TOLERANCIA'] ?: 0,

            // Información del horario personalizado
            'ID_EMPLEADO_HORARIO' => $entrada['ID_EMPLEADO_HORARIO'],
            'NOMBRE_TURNO' => $entrada['NOMBRE_TURNO'],
            'HORA_ENTRADA_PERSONALIZADO' => $entrada['HORA_ENTRADA_PERSONALIZADO'],
            'HORA_SALIDA_PERSONALIZADO' => $entrada['HORA_SALIDA_PERSONALIZADO'],
            'TOLERANCIA_PERSONALIZADA' => $entrada['TOLERANCIA_PERSONALIZADA'],
            'HORARIO_PERSONALIZADO_ACTIVO' => $entrada['HORARIO_PERSONALIZADO_ACTIVO'],
            'ES_TURNO_NOCTURNO' => $entrada['ES_TURNO_NOCTURNO'] ?: 'N',
            'HORA_CORTE_NOCTURNO' => $entrada['HORA_CORTE_NOCTURNO'],
            'DIA_NOMBRE' => $entrada['DIA_NOMBRE'],
            'ORDEN_TURNO' => $entrada['ORDEN_TURNO'],
            'FECHA_DESDE' => $entrada['FECHA_DESDE'],
            'FECHA_HASTA' => $entrada['FECHA_HASTA'],

            // Información de salida (si existe)
            'SALIDA_ID' => $salida ? $salida['id_salida'] : null,
            'SALIDA_FECHA' => $salida ? $salida['fecha_salida'] : null,
            'SALIDA_HORA' => $salida ? $salida['hora_salida'] : null,
            'SALIDA_TARDANZA' => $salida ? $salida['tardanza_salida'] : null,
            'SALIDA_OBSERVACION' => $salida ? $salida['observacion_salida'] : null,
            'SALIDA_FOTO' => $salida ? $salida['foto_salida'] : null,
            'SALIDA_REGISTRO_MANUAL' => $salida ? $salida['registro_manual_salida'] : null
        ];

        $result[] = $registro;
    }

    // Calcular total de registros (número de entradas)
    $totalRecords = count($entradas);

    // Procesar asistencias para calcular estados y normalizar información de horarios
    $asistenciasProcesadas = [];
    foreach ($result as $asistencia) {
        $estado = '--';
        $tolerancia = 0;
        $hora_entrada_programada = null;
        $hora_salida_programada = null;
        $nombre_horario = 'Sin horario';
        $tipo_horario = 'ninguno';
        $tolerancia_bruta = 0;
        $es_turno_nocturno = false;

        // Determinar tipo de horario y obtener información
        if ($asistencia['ID_EMPLEADO_HORARIO']) {
            // Horario personalizado
            $tipo_horario = 'personalizado';
            $hora_entrada_programada = $asistencia['HORA_ENTRADA_PERSONALIZADO'];
            $hora_salida_programada = $asistencia['HORA_SALIDA_PERSONALIZADO'];
            $nombre_horario = $asistencia['NOMBRE_TURNO'] ?? 'Turno Personalizado';
            $tolerancia_bruta = $asistencia['TOLERANCIA_PERSONALIZADA'] ?? $asistencia['TOLERANCIA'] ?? 0;
            $tolerancia = normalizarToleranciaMinutos($tolerancia_bruta);
        } elseif ($asistencia['ID_HORARIO']) {
            // Horario tradicional
            $tipo_horario = 'tradicional';
            $hora_entrada_programada = $asistencia['HORA_ENTRADA'];
            $hora_salida_programada = $asistencia['HORA_SALIDA'];
            $nombre_horario = $asistencia['HORARIO_NOMBRE'] ?? 'Horario Fijo';
            $tolerancia_bruta = $asistencia['TOLERANCIA'] ?? 0;
            $tolerancia = normalizarToleranciaMinutos($tolerancia_bruta);
        }

        // Calcular estado si hay horario asignado
        if ($tipo_horario !== 'ninguno') {
            $es_turno_nocturno = $hora_salida_programada && $hora_entrada_programada > $hora_salida_programada;
            if ($asistencia['tipo'] === 'ENTRADA' && $hora_entrada_programada) {

                // Para turnos nocturnos, la entrada programada podría ser del día anterior
                $fecha_entrada_programada = $asistencia['fecha'];
                if ($es_turno_nocturno) {
                    // Si es turno nocturno y la hora actual es menor que la hora de entrada programada,
                    // significa que la entrada programada era del día anterior
                    $hora_actual = date('H:i:s', strtotime($asistencia['hora']));
                    if ($hora_actual < $hora_entrada_programada) {
                        // La entrada real es al día siguiente del programado, ajustar fecha
                        $fecha_entrada_programada = date('Y-m-d', strtotime($asistencia['fecha'] . ' -1 day'));
                    }
                }

                // Calcular estado de entrada
                $ts_entrada_programada = strtotime($fecha_entrada_programada . ' ' . $hora_entrada_programada);
                $ts_entrada_real = strtotime($asistencia['fecha'] . ' ' . $asistencia['hora']);

                if ($ts_entrada_real < $ts_entrada_programada - $tolerancia * 60) {
                    $estado = 'Temprano';
                } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
                    $estado = 'Puntual';
                } else {
                    $estado = 'Tardanza';
                }
            } elseif ($asistencia['tipo'] === 'SALIDA' && $hora_salida_programada) {
                // Calcular estado de salida
                $es_turno_nocturno = $hora_salida_programada && $hora_entrada_programada > $hora_salida_programada;
                $fecha_salida_programada = $asistencia['SALIDA_FECHA'];
                if ($es_turno_nocturno) {
                    // Para turnos nocturnos, la salida programada es del día siguiente a la entrada
                    $fecha_salida_programada = date('Y-m-d', strtotime($asistencia['fecha'] . ' +1 day'));
                }
                $ts_salida_programada = strtotime($fecha_salida_programada . ' ' . $hora_salida_programada);
                $ts_salida_real = strtotime($asistencia['SALIDA_FECHA'] . ' ' . $asistencia['SALIDA_HORA']);

                if ($ts_salida_real < $ts_salida_programada - $tolerancia * 60) {
                    $estado = 'Temprano';
                } elseif ($ts_salida_real <= $ts_salida_programada + $tolerancia * 60) {
                    $estado = 'Puntual';
                } else {
                    $estado = 'Tardanza';
                }
            }
        }
        
        // Normalizar los datos para el frontend
        $asistencia['estado'] = $estado;
        $asistencia['tipo_horario'] = $tipo_horario;
        $asistencia['HORARIO_NOMBRE'] = $nombre_horario;
        $asistencia['HORA_ENTRADA_PROGRAMADA'] = $hora_entrada_programada;
        $asistencia['HORA_SALIDA_PROGRAMADA'] = $hora_salida_programada;
        $asistencia['TOLERANCIA'] = $tolerancia;
        $asistencia['ES_TURNO_NOCTURNO'] = $es_turno_nocturno ? 'S' : 'N';
        $asistencia['TOLERANCIA_ORIGINAL'] = $tolerancia_bruta;

        $asistenciasProcesadas[] = $asistencia;
    }

    // DEBUG: Log de asistencias retornadas
    error_log("API LIST: Retornando " . count($asistenciasProcesadas) . " asistencias");
    foreach ($asistenciasProcesadas as $asistencia) {
        error_log("Asistencia: {$asistencia['codigo_empleado']} - {$asistencia['tipo']} - {$asistencia['fecha']} {$asistencia['hora']}");
    }

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $asistenciasProcesadas,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'filters_applied' => array_filter($filtros)
    ]);

} catch (Exception $e) {
    // Asegurar que siempre se devuelva JSON válido
    ob_clean();
    error_log("Error en API attendance/list.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar asistencias: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}

// Asegurar que no se ejecute código después del catch
exit;
?>
