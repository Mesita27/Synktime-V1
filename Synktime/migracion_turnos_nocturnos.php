<?php
/**
 * SCRIPT DE MIGRACIÓN PARA TURNOS NOCTURNOS
 * Sistema de Asistencia Synktime
 * 
 * Este script migra los datos existentes al nuevo sistema de jornadas
 */

require_once 'db_connection.php';

class MigracionTurnosNocturnos {
    private $conn;
    private $errores = [];
    private $migrados = 0;
    private $omitidos = 0;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Ejecuta la migración completa
     */
    public function ejecutarMigracion($fecha_desde = null, $fecha_hasta = null) {
        echo "🚀 INICIANDO MIGRACIÓN DE TURNOS NOCTURNOS\n";
        echo "==========================================\n\n";
        
        $fecha_desde = $fecha_desde ?? '2025-01-01';
        $fecha_hasta = $fecha_hasta ?? date('Y-m-d');
        
        echo "📅 Período de migración: $fecha_desde a $fecha_hasta\n\n";
        
        try {
            // 1. Verificar estructura de base de datos
            $this->verificarEstructura();
            
            // 2. Migrar registros de asistencia existentes
            $this->migrarRegistrosAsistencia($fecha_desde, $fecha_hasta);
            
            // 3. Crear jornadas faltantes
            $this->crearJornadasFaltantes($fecha_desde, $fecha_hasta);
            
            // 4. Calcular horas trabajadas
            $this->calcularHorasTrabajadas();
            
            // 5. Mostrar resumen
            $this->mostrarResumen();
            
        } catch (Exception $e) {
            echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica que la estructura de BD esté lista
     */
    private function verificarEstructura() {
        echo "🔍 Verificando estructura de base de datos...\n";
        
        // Verificar tabla jornadas_trabajo
        $result = $this->conn->query("SHOW TABLES LIKE 'jornadas_trabajo'");
        if ($result->num_rows == 0) {
            throw new Exception("Tabla jornadas_trabajo no existe. Ejecute sql_turnos_nocturnos.sql primero.");
        }
        
        // Verificar columnas en asistencia
        $result = $this->conn->query("SHOW COLUMNS FROM asistencia LIKE 'ID_JORNADA_TRABAJO'");
        if ($result->num_rows == 0) {
            echo "⚠️  Agregando columnas faltantes a tabla asistencia...\n";
            $this->conn->query("ALTER TABLE asistencia ADD COLUMN ID_JORNADA_TRABAJO INT(11) NULL");
            $this->conn->query("ALTER TABLE asistencia ADD COLUMN FECHA_JORNADA_LABORAL DATE NULL");
            $this->conn->query("ALTER TABLE asistencia ADD COLUMN ES_TURNO_NOCTURNO CHAR(1) DEFAULT 'N'");
        }
        
        echo "✅ Estructura verificada\n\n";
    }
    
    /**
     * Migra registros existentes de asistencia
     */
    private function migrarRegistrosAsistencia($fecha_desde, $fecha_hasta) {
        echo "📦 Migrando registros de asistencia existentes...\n";
        
        // Obtener registros de entrada sin jornada asignada
        $stmt = $this->conn->prepare("
            SELECT 
                a.ID_ASISTENCIA,
                a.ID_EMPLEADO,
                a.FECHA,
                a.HORA,
                a.TIPO,
                a.ID_EMPLEADO_HORARIO,
                ehp.ES_TURNO_NOCTURNO,
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA
            FROM asistencia a
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
            WHERE a.FECHA BETWEEN ? AND ?
            AND a.ID_JORNADA_TRABAJO IS NULL
            ORDER BY a.ID_EMPLEADO, a.FECHA, a.HORA, a.TIPO
        ");
        $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $registros_por_empleado = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['ID_EMPLEADO'] . '_' . $row['FECHA'];
            if (!isset($registros_por_empleado[$key])) {
                $registros_por_empleado[$key] = [];
            }
            $registros_por_empleado[$key][] = $row;
        }
        
        echo "📊 Encontrados " . count($registros_por_empleado) . " grupos de empleado-fecha\n";
        
        foreach ($registros_por_empleado as $key => $registros) {
            $this->procesarGrupoRegistros($registros);
        }
        
        echo "✅ Migración de registros completada\n\n";
    }
    
    /**
     * Procesa un grupo de registros de un empleado en una fecha
     */
    private function procesarGrupoRegistros($registros) {
        $entradas = array_filter($registros, function($r) { return $r['TIPO'] == 'ENTRADA'; });
        $salidas = array_filter($registros, function($r) { return $r['TIPO'] == 'SALIDA'; });
        
        // Ordenar por hora
        usort($entradas, function($a, $b) { return strcmp($a['HORA'], $b['HORA']); });
        usort($salidas, function($a, $b) { return strcmp($a['HORA'], $b['HORA']); });
        
        // Emparejar entradas y salidas
        $jornadas_creadas = 0;
        
        foreach ($entradas as $entrada) {
            try {
                // Buscar salida correspondiente
                $salida_encontrada = null;
                $es_nocturno = $entrada['ES_TURNO_NOCTURNO'] == 'S';
                
                if ($es_nocturno) {
                    // Para turnos nocturnos, buscar salida en el día siguiente
                    $fecha_salida_esperada = date('Y-m-d', strtotime($entrada['FECHA'] . ' +1 day'));
                    $salida_encontrada = $this->buscarSalidaNocturna($entrada, $salidas, $fecha_salida_esperada);
                } else {
                    // Para turnos diurnos, buscar salida el mismo día
                    $salida_encontrada = $this->buscarSalidaDiurna($entrada, $salidas);
                }
                
                // Crear jornada
                $id_jornada = $this->crearJornada($entrada, $salida_encontrada);
                
                if ($id_jornada) {
                    // Actualizar registros de asistencia
                    $this->actualizarRegistroAsistencia($entrada['ID_ASISTENCIA'], $id_jornada, $entrada['FECHA'], $es_nocturno);
                    
                    if ($salida_encontrada) {
                        $fecha_salida = $es_nocturno ? date('Y-m-d', strtotime($entrada['FECHA'] . ' +1 day')) : $entrada['FECHA'];
                        $this->actualizarRegistroAsistencia($salida_encontrada['ID_ASISTENCIA'], $id_jornada, $entrada['FECHA'], $es_nocturno);
                    }
                    
                    $jornadas_creadas++;
                    $this->migrados++;
                }
                
            } catch (Exception $e) {
                $this->errores[] = "Error procesando entrada ID {$entrada['ID_ASISTENCIA']}: " . $e->getMessage();
            }
        }
        
        if ($jornadas_creadas > 0) {
            echo "  ✓ Empleado {$registros[0]['ID_EMPLEADO']} - Fecha {$registros[0]['FECHA']}: {$jornadas_creadas} jornadas creadas\n";
        }
    }
    
    /**
     * Busca salida correspondiente para turno nocturno
     */
    private function buscarSalidaNocturna($entrada, $salidas, $fecha_salida_esperada) {
        // Buscar salidas que podrían corresponder a esta entrada nocturna
        $stmt = $this->conn->prepare("
            SELECT * FROM asistencia 
            WHERE ID_EMPLEADO = ? 
            AND FECHA = ? 
            AND TIPO = 'SALIDA'
            AND HORA > '00:00:00' AND HORA < '12:00:00'
            AND ID_JORNADA_TRABAJO IS NULL
            ORDER BY HORA
            LIMIT 1
        ");
        $stmt->bind_param("is", $entrada['ID_EMPLEADO'], $fecha_salida_esperada);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Busca salida correspondiente para turno diurno
     */
    private function buscarSalidaDiurna($entrada, $salidas) {
        foreach ($salidas as $salida) {
            if ($salida['HORA'] > $entrada['HORA']) {
                return $salida;
            }
        }
        return null;
    }
    
    /**
     * Crea una jornada de trabajo
     */
    private function crearJornada($entrada, $salida = null) {
        $es_nocturno = $entrada['ES_TURNO_NOCTURNO'] == 'S';
        $fecha_inicio = $entrada['FECHA'];
        $hora_entrada = $entrada['HORA'];
        $hora_salida_programada = $entrada['HORA_SALIDA'];
        
        // Calcular fecha de salida programada
        $fecha_salida_programada = $es_nocturno ? 
            date('Y-m-d', strtotime($fecha_inicio . ' +1 day')) : 
            $fecha_inicio;
        
        $estado = $salida ? 'COMPLETADA' : 'INCOMPLETA';
        $fecha_salida_real = $salida ? ($es_nocturno ? $fecha_salida_programada : $fecha_inicio) : null;
        $hora_salida_real = $salida ? $salida['HORA'] : null;
        
        // Calcular horas trabajadas si hay salida
        $horas_trabajadas = null;
        if ($salida) {
            $inicio = new DateTime($fecha_inicio . ' ' . $hora_entrada);
            $fin = new DateTime($fecha_salida_real . ' ' . $hora_salida_real);
            $diferencia = $inicio->diff($fin);
            $horas_trabajadas = $diferencia->h + ($diferencia->i / 60) + ($diferencia->days * 24);
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO jornadas_trabajo (
                ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA_INICIO, HORA_ENTRADA,
                HORA_SALIDA_PROGRAMADA, FECHA_SALIDA_PROGRAMADA,
                FECHA_SALIDA_REAL, HORA_SALIDA_REAL, HORAS_TRABAJADAS,
                ES_TURNO_NOCTURNO, ESTADO, OBSERVACIONES
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $observaciones = "Migrado desde registros existentes";
        $es_nocturno_char = $es_nocturno ? 'S' : 'N';
        
        $stmt->bind_param("iissssssdsss", 
            $entrada['ID_EMPLEADO'], 
            $entrada['ID_EMPLEADO_HORARIO'],
            $fecha_inicio,
            $hora_entrada,
            $hora_salida_programada,
            $fecha_salida_programada,
            $fecha_salida_real,
            $hora_salida_real,
            $horas_trabajadas,
            $es_nocturno_char,
            $estado,
            $observaciones
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            throw new Exception("Error creando jornada: " . $stmt->error);
        }
    }
    
    /**
     * Actualiza registro de asistencia con ID de jornada
     */
    private function actualizarRegistroAsistencia($id_asistencia, $id_jornada, $fecha_jornada, $es_nocturno) {
        $stmt = $this->conn->prepare("
            UPDATE asistencia SET 
                ID_JORNADA_TRABAJO = ?,
                FECHA_JORNADA_LABORAL = ?,
                ES_TURNO_NOCTURNO = ?
            WHERE ID_ASISTENCIA = ?
        ");
        
        $es_nocturno_char = $es_nocturno ? 'S' : 'N';
        $stmt->bind_param("issi", $id_jornada, $fecha_jornada, $es_nocturno_char, $id_asistencia);
        $stmt->execute();
    }
    
    /**
     * Crea jornadas faltantes para entradas sin salida
     */
    private function crearJornadasFaltantes($fecha_desde, $fecha_hasta) {
        echo "🔧 Verificando jornadas incompletas...\n";
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as incompletas 
            FROM jornadas_trabajo 
            WHERE ESTADO = 'INCOMPLETA' 
            AND FECHA_INICIO BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo "📊 Jornadas incompletas encontradas: " . $row['incompletas'] . "\n\n";
    }
    
    /**
     * Recalcula horas trabajadas para jornadas completadas
     */
    private function calcularHorasTrabajadas() {
        echo "⏱️  Recalculando horas trabajadas...\n";
        
        $stmt = $this->conn->query("
            SELECT j.ID_JORNADA, j.FECHA_INICIO, j.HORA_ENTRADA, 
                   j.FECHA_SALIDA_REAL, j.HORA_SALIDA_REAL
            FROM jornadas_trabajo j
            WHERE j.ESTADO = 'COMPLETADA' 
            AND j.HORAS_TRABAJADAS IS NULL
        ");
        
        $recalculadas = 0;
        while ($row = $stmt->fetch_assoc()) {
            $inicio = new DateTime($row['FECHA_INICIO'] . ' ' . $row['HORA_ENTRADA']);
            $fin = new DateTime($row['FECHA_SALIDA_REAL'] . ' ' . $row['HORA_SALIDA_REAL']);
            $diferencia = $inicio->diff($fin);
            $horas = $diferencia->h + ($diferencia->i / 60) + ($diferencia->days * 24);
            
            $update = $this->conn->prepare("
                UPDATE jornadas_trabajo 
                SET HORAS_TRABAJADAS = ?, HORAS_EXTRAS = GREATEST(0, ? - 8)
                WHERE ID_JORNADA = ?
            ");
            $update->bind_param("ddi", $horas, $horas, $row['ID_JORNADA']);
            $update->execute();
            
            $recalculadas++;
        }
        
        echo "✅ Horas recalculadas: $recalculadas jornadas\n\n";
    }
    
    /**
     * Muestra resumen de la migración
     */
    private function mostrarResumen() {
        echo "📈 RESUMEN DE MIGRACIÓN\n";
        echo "=====================\n";
        echo "✅ Jornadas migradas: {$this->migrados}\n";
        echo "⏭️  Registros omitidos: {$this->omitidos}\n";
        echo "❌ Errores encontrados: " . count($this->errores) . "\n\n";
        
        if (!empty($this->errores)) {
            echo "🔍 DETALLE DE ERRORES:\n";
            foreach ($this->errores as $error) {
                echo "  • $error\n";
            }
            echo "\n";
        }
        
        // Estadísticas finales
        $stats = $this->conn->query("
            SELECT 
                COUNT(*) as total_jornadas,
                SUM(CASE WHEN ESTADO = 'COMPLETADA' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN ESTADO = 'INCOMPLETA' THEN 1 ELSE 0 END) as incompletas,
                SUM(CASE WHEN ES_TURNO_NOCTURNO = 'S' THEN 1 ELSE 0 END) as nocturnas,
                SUM(HORAS_TRABAJADAS) as total_horas
            FROM jornadas_trabajo
        ")->fetch_assoc();
        
        echo "📊 ESTADÍSTICAS FINALES:\n";
        echo "  • Total de jornadas: {$stats['total_jornadas']}\n";
        echo "  • Jornadas completadas: {$stats['completadas']}\n";
        echo "  • Jornadas incompletas: {$stats['incompletas']}\n";
        echo "  • Jornadas nocturnas: {$stats['nocturnas']}\n";
        echo "  • Total horas trabajadas: " . round($stats['total_horas'], 2) . "h\n\n";
        
        echo "🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    }
}

// Ejecutar migración si se llama directamente
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    try {
        $migrador = new MigracionTurnosNocturnos($conn);
        
        // Obtener parámetros de línea de comandos o usar valores por defecto
        $fecha_desde = $argv[1] ?? '2025-01-01';
        $fecha_hasta = $argv[2] ?? date('Y-m-d');
        
        $migrador->ejecutarMigracion($fecha_desde, $fecha_hasta);
        
    } catch (Exception $e) {
        echo "💥 ERROR FATAL: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>