<?php
/**
 * IMPLEMENTACIÓN PRÁCTICA - SISTEMA DE TURNOS NOCTURNOS
 * Ejemplo de código para manejar registros de asistencia en turnos que cruzan medianoche
 */

require_once 'config/database.php';

class TurnosNocturnos {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registrar entrada de empleado
     * Crea una nueva jornada de trabajo si es turno nocturno
     */
    public function registrarEntrada($idEmpleado, $fecha, $hora, $idEmpleadoHorario) {
        try {
            // Obtener información del horario
            $horario = $this->obtenerHorario($idEmpleadoHorario);
            $esTurnoNocturno = $this->esTurnoNocturno($horario);
            
            // Crear jornada de trabajo
            $idJornada = $this->crearJornadaTrabajo($idEmpleado, $fecha, $hora, $horario, $esTurnoNocturno);
            
            // Registrar asistencia vinculada a la jornada
            $sql = "INSERT INTO asistencia (
                ID_EMPLEADO, FECHA, TIPO, HORA, ID_EMPLEADO_HORARIO,
                ID_JORNADA_TRABAJO, FECHA_JORNADA_LABORAL, ES_TURNO_NOCTURNO,
                CREATED_AT
            ) VALUES (?, ?, 'ENTRADA', ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $idEmpleado, $fecha, $hora, $idEmpleadoHorario,
                $idJornada, $fecha, $esTurnoNocturno ? 'S' : 'N'
            ]);
            
            return [
                'success' => true,
                'id_jornada' => $idJornada,
                'es_turno_nocturno' => $esTurnoNocturno,
                'message' => 'Entrada registrada correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar entrada: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar salida de empleado
     * Busca la jornada activa y la completa
     */
    public function registrarSalida($idEmpleado, $fecha, $hora) {
        try {
            // Buscar jornada activa del empleado
            $jornada = $this->buscarJornadaActiva($idEmpleado, $fecha);
            
            if (!$jornada) {
                return [
                    'success' => false,
                    'message' => 'No se encontró una entrada activa para este empleado'
                ];
            }
            
            // Determinar fecha de jornada (puede ser día anterior para turnos nocturnos)
            $fechaJornada = $jornada['FECHA_JORNADA_LABORAL'];
            
            // Registrar salida vinculada a la jornada
            $sql = "INSERT INTO asistencia (
                ID_EMPLEADO, FECHA, TIPO, HORA, ID_EMPLEADO_HORARIO,
                ID_JORNADA_TRABAJO, FECHA_JORNADA_LABORAL, ES_TURNO_NOCTURNO,
                CREATED_AT
            ) VALUES (?, ?, 'SALIDA', ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $idEmpleado, $fecha, $hora, $jornada['ID_EMPLEADO_HORARIO'],
                $jornada['ID_JORNADA'], $fechaJornada, $jornada['ES_TURNO_NOCTURNO']
            ]);
            
            // Marcar jornada como completada y calcular horas
            $this->completarJornada($jornada['ID_JORNADA'], $fecha, $hora);
            
            return [
                'success' => true,
                'id_jornada' => $jornada['ID_JORNADA'],
                'fecha_jornada' => $fechaJornada,
                'message' => 'Salida registrada correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar salida: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Determinar si un horario es turno nocturno
     */
    private function esTurnoNocturno($horario) {
        $horaEntrada = strtotime($horario['HORA_ENTRADA']);
        $horaSalida = strtotime($horario['HORA_SALIDA']);
        
        // Si la hora de salida es menor que la de entrada, cruza medianoche
        return $horaSalida < $horaEntrada;
    }
    
    /**
     * Crear nueva jornada de trabajo
     */
    private function crearJornadaTrabajo($idEmpleado, $fecha, $hora, $horario, $esTurnoNocturno) {
        // Calcular fecha de salida programada
        $fechaSalidaProgramada = $fecha;
        if ($esTurnoNocturno) {
            $fechaSalidaProgramada = date('Y-m-d', strtotime($fecha . ' +1 day'));
        }
        
        $sql = "INSERT INTO jornadas_trabajo (
            ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA_INICIO, HORA_ENTRADA,
            HORA_SALIDA_PROGRAMADA, FECHA_SALIDA_PROGRAMADA, ES_TURNO_NOCTURNO,
            ESTADO, CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'INICIADA', NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $idEmpleado, $horario['ID_EMPLEADO_HORARIO'], $fecha, $hora,
            $horario['HORA_SALIDA'], $fechaSalidaProgramada, $esTurnoNocturno ? 'S' : 'N'
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Buscar jornada activa de un empleado
     */
    private function buscarJornadaActiva($idEmpleado, $fechaSalida) {
        // Buscar primero en el mismo día
        $sql = "SELECT j.*, ehp.HORA_ENTRADA, ehp.HORA_SALIDA 
                FROM jornadas_trabajo j
                JOIN empleado_horario_personalizado ehp ON j.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                WHERE j.ID_EMPLEADO = ? 
                AND j.ESTADO = 'INICIADA'
                AND (j.FECHA_INICIO = ? OR j.FECHA_SALIDA_PROGRAMADA = ?)
                ORDER BY j.CREATED_AT DESC
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idEmpleado, $fechaSalida, $fechaSalida]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        // Si no se encuentra, buscar turno nocturno del día anterior
        $fechaAnterior = date('Y-m-d', strtotime($fechaSalida . ' -1 day'));
        $stmt->execute([$idEmpleado, $fechaAnterior, $fechaSalida]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Completar jornada y calcular horas trabajadas
     */
    private function completarJornada($idJornada, $fechaSalida, $horaSalida) {
        // Obtener datos de la jornada
        $sql = "SELECT j.*, a_entrada.HORA as HORA_ENTRADA_REAL, a_entrada.FECHA as FECHA_ENTRADA_REAL
                FROM jornadas_trabajo j
                JOIN asistencia a_entrada ON j.ID_JORNADA = a_entrada.ID_JORNADA_TRABAJO AND a_entrada.TIPO = 'ENTRADA'
                WHERE j.ID_JORNADA = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idJornada]);
        $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular horas trabajadas
        $horasTrabajadas = $this->calcularHorasTrabajadas(
            $jornada['FECHA_ENTRADA_REAL'], $jornada['HORA_ENTRADA_REAL'],
            $fechaSalida, $horaSalida
        );
        
        // Actualizar jornada
        $sqlUpdate = "UPDATE jornadas_trabajo SET 
                      ESTADO = 'COMPLETADA',
                      FECHA_SALIDA_REAL = ?,
                      HORA_SALIDA_REAL = ?,
                      HORAS_TRABAJADAS = ?,
                      HORAS_EXTRAS = ?
                      WHERE ID_JORNADA = ?";
        
        $stmt = $this->pdo->prepare($sqlUpdate);
        $stmt->execute([
            $fechaSalida, $horaSalida,
            $horasTrabajadas['total'], $horasTrabajadas['extras'],
            $idJornada
        ]);
        
        return $horasTrabajadas;
    }
    
    /**
     * Calcular horas trabajadas considerando cruces de medianoche
     */
    private function calcularHorasTrabajadas($fechaEntrada, $horaEntrada, $fechaSalida, $horaSalida) {
        $timestampEntrada = strtotime("$fechaEntrada $horaEntrada");
        $timestampSalida = strtotime("$fechaSalida $horaSalida");
        
        $segundosTrabajados = $timestampSalida - $timestampEntrada;
        $horasTotales = $segundosTrabajados / 3600;
        
        // Calcular horas extras (más de 8 horas)
        $horasNormales = min($horasTotales, 8);
        $horasExtras = max(0, $horasTotales - 8);
        
        return [
            'total' => round($horasTotales, 2),
            'normales' => round($horasNormales, 2),
            'extras' => round($horasExtras, 2)
        ];
    }
    
    /**
     * Obtener información de horario personalizado
     */
    private function obtenerHorario($idEmpleadoHorario) {
        $sql = "SELECT * FROM empleado_horario_personalizado WHERE ID_EMPLEADO_HORARIO = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idEmpleadoHorario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener resumen de jornadas de un empleado
     */
    public function obtenerJornadasEmpleado($idEmpleado, $fechaDesde, $fechaHasta) {
        $sql = "SELECT j.*, 
                       a_entrada.HORA as HORA_ENTRADA_REAL,
                       a_salida.HORA as HORA_SALIDA_REAL,
                       ehp.NOMBRE_TURNO
                FROM jornadas_trabajo j
                LEFT JOIN asistencia a_entrada ON j.ID_JORNADA = a_entrada.ID_JORNADA_TRABAJO AND a_entrada.TIPO = 'ENTRADA'
                LEFT JOIN asistencia a_salida ON j.ID_JORNADA = a_salida.ID_JORNADA_TRABAJO AND a_salida.TIPO = 'SALIDA'
                LEFT JOIN empleado_horario_personalizado ehp ON j.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                WHERE j.ID_EMPLEADO = ? 
                AND j.FECHA_INICIO BETWEEN ? AND ?
                ORDER BY j.FECHA_INICIO DESC, j.CREATED_AT DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idEmpleado, $fechaDesde, $fechaHasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Ejemplo de uso
try {
    $turnosNocturnos = new TurnosNocturnos($pdo);
    
    echo "=== EJEMPLO DE USO - TURNOS NOCTURNOS ===\n";
    
    // Caso: Empleado entra lunes 18:00, sale martes 00:30
    echo "\n1. Registrar entrada lunes 18:00:\n";
    $resultado = $turnosNocturnos->registrarEntrada(100, '2025-09-16', '18:00:00', 1);
    echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n2. Registrar salida martes 00:30:\n";
    $resultado = $turnosNocturnos->registrarSalida(100, '2025-09-17', '00:30:00');
    echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>