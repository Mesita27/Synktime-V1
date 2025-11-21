<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AsistenciaRepository;
use PDO;

/**
 * Service for Asistencia (Attendance) business logic
 * 
 * Orchestrates attendance operations and applies business rules
 */
class AsistenciaService
{
    private AsistenciaRepository $repository;
    private PDO $conn;

    public function __construct(AsistenciaRepository $repository, PDO $conn)
    {
        $this->repository = $repository;
        $this->conn = $conn;
    }

    /**
     * Get attendance details with filtering and status calculation
     * 
     * @param string $tipo Type: 'temprano', 'aTiempo', 'tarde', 'faltas'
     * @param string $fecha Date in Y-m-d format
     * @param int|null $establecimientoId
     * @param int|null $sedeId
     * @param int $empresaId
     * @return array Response with success, data, metadata
     */
    public function getAttendanceDetails(
        string $tipo,
        string $fecha,
        ?int $establecimientoId,
        ?int $sedeId,
        int $empresaId
    ): array {
        // Validate type
        $tiposValidos = ['temprano', 'aTiempo', 'tarde', 'faltas'];
        if (!in_array($tipo, $tiposValidos)) {
            return [
                'success' => false,
                'error' => 'Tipo de asistencia inválido'
            ];
        }

        try {
            // Get records from repository
            $registros = $this->repository->getAttendanceByType(
                $tipo,
                $fecha,
                $establecimientoId,
                $sedeId,
                $empresaId
            );

            // Get location name
            $locationName = $this->repository->getLocationName(
                $establecimientoId,
                $sedeId,
                $empresaId
            );

            // Process records based on type
            $data = $this->processRecords($registros, $tipo, $fecha);

            // Build response
            return [
                'success' => true,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'ubicacion' => $locationName,
                'total' => count($data),
                'data' => $data
            ];
        } catch (\Exception $e) {
            error_log("AsistenciaService error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al obtener detalles de asistencia'
            ];
        }
    }

    /**
     * Process records based on attendance type
     */
    private function processRecords(array $registros, string $tipo, string $fecha): array
    {
        if ($tipo === 'faltas') {
            return $this->processAbsences($registros, $fecha);
        } else {
            return $this->processAttendanceByStatus($registros, $tipo, $fecha);
        }
    }

    /**
     * Process absence records (add schedule info)
     */
    private function processAbsences(array $registros, string $fecha): array
    {
        // Load horario utils for compatibility
        $horarioUtilsPath = dirname(__DIR__) . '/Utils/horario_utils.php';
        if (file_exists($horarioUtilsPath)) {
            require_once $horarioUtilsPath;
        }

        foreach ($registros as &$registro) {
            // Use existing utility function if available
            if (function_exists('obtenerHorarioEmpleadoSimplificado')) {
                $horarioInfo = obtenerHorarioEmpleadoSimplificado(
                    $registro['CODIGO'],
                    $fecha,
                    $this->conn
                );
                $registro['HORARIO_NOMBRE'] = $horarioInfo['horario_nombre'] ?? null;
                $registro['HORA_ENTRADA'] = $horarioInfo['HORA_ENTRADA'] ?? null;
            }
        }

        return $registros;
    }

    /**
     * Process attendance records and filter by status
     */
    private function processAttendanceByStatus(array $registros, string $tipo, string $fecha): array
    {
        // Load attendance status utils for compatibility
        $statusUtilsPath = dirname(__DIR__) . '/Utils/attendance_status_utils.php';
        $horarioUtilsPath = dirname(__DIR__) . '/Utils/horario_utils.php';

        if (file_exists($statusUtilsPath)) {
            require_once $statusUtilsPath;
        }
        if (file_exists($horarioUtilsPath)) {
            require_once $horarioUtilsPath;
        }

        $data = [];

        foreach ($registros as $registro) {
            // Get schedule info
            $horarioInfo = null;
            if (function_exists('obtenerHorarioDeAsistencia')) {
                $horarioInfo = obtenerHorarioDeAsistencia(
                    $registro['ID_ASISTENCIA'],
                    $registro['CODIGO'],
                    $fecha,
                    $this->conn
                );
            }

            if (!$horarioInfo) {
                continue; // Skip if no schedule found
            }

            // Calculate attendance status
            $estado = null;
            if (function_exists('calcularEstadoAsistencia')) {
                $estado = calcularEstadoAsistencia(
                    $registro['ENTRADA_HORA'],
                    $horarioInfo['hora_entrada'],
                    $horarioInfo['minutos_tolerancia'] ?? 0
                );
            }

            // Filter by requested type
            if ($estado === $tipo) {
                $data[] = [
                    'CODIGO' => $registro['CODIGO'],
                    'NOMBRE' => $registro['NOMBRE'],
                    'APELLIDO' => $registro['APELLIDO'],
                    'SEDE' => $registro['SEDE'],
                    'ESTABLECIMIENTO' => $registro['ESTABLECIMIENTO'],
                    'ENTRADA_HORA' => $registro['ENTRADA_HORA'],
                    'HORARIO_NOMBRE' => $horarioInfo['horario_nombre'] ?? null,
                    'HORA_ENTRADA_ESPERADA' => $horarioInfo['hora_entrada'] ?? null,
                    'ESTADO' => $estado,
                ];
            }
        }

        return $data;
    }
}
