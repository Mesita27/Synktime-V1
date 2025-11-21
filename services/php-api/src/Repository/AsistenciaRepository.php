<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Repository for Asistencia (Attendance) data access
 * 
 * Handles all database operations related to attendance records
 */
class AsistenciaRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get attendance details by type, date and location filters
     * 
     * @param string $tipo Type: 'temprano', 'aTiempo', 'tarde', 'faltas'
     * @param string $fecha Date in Y-m-d format
     * @param int|null $establecimientoId Establishment ID filter
     * @param int|null $sedeId Sede ID filter
     * @param int $empresaId Company ID (fallback filter)
     * @return array Attendance records
     */
    public function getAttendanceByType(
        string $tipo,
        string $fecha,
        ?int $establecimientoId,
        ?int $sedeId,
        int $empresaId
    ): array {
        // Build WHERE conditions
        $whereConditions = [];
        $params = [':fecha' => $fecha];

        if ($establecimientoId) {
            $whereConditions[] = "E.ID_ESTABLECIMIENTO = :establecimiento_id";
            $params[':establecimiento_id'] = $establecimientoId;
        } elseif ($sedeId) {
            $whereConditions[] = "EST.ID_SEDE = :sede_id";
            $params[':sede_id'] = $sedeId;
        } else {
            $whereConditions[] = "S.ID_EMPRESA = :empresa_id";
            $params[':empresa_id'] = $empresaId;
        }

        $whereClause = implode(" AND ", $whereConditions);

        if ($tipo === 'faltas') {
            $query = $this->buildAbsenceQuery($whereClause);
        } else {
            $query = $this->buildAttendanceQuery($whereClause);
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get location name (empresa, sede or establecimiento)
     */
    public function getLocationName(?int $establecimientoId, ?int $sedeId, int $empresaId): string
    {
        $defaultName = "Todas las ubicaciones";

        if ($establecimientoId) {
            $query = "SELECT NOMBRE FROM ESTABLECIMIENTO WHERE ID_ESTABLECIMIENTO = :id";
            $id = $establecimientoId;
        } elseif ($sedeId) {
            $query = "SELECT NOMBRE FROM SEDE WHERE ID_SEDE = :id";
            $id = $sedeId;
        } else {
            $query = "SELECT NOMBRE FROM EMPRESA WHERE ID_EMPRESA = :id";
            $id = $empresaId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetchColumn() ?: $defaultName;
    }

    private function buildAbsenceQuery(string $whereClause): string
    {
        return "
            SELECT
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO
            FROM EMPLEADO E
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            LEFT JOIN ASISTENCIA A ON E.ID_EMPLEADO = A.ID_EMPLEADO AND A.FECHA = :fecha AND A.TIPO = 'ENTRADA'
            WHERE E.ACTIVO = 'S'
            AND $whereClause
            AND A.ID_ASISTENCIA IS NULL
            ORDER BY E.NOMBRE, E.APELLIDO
        ";
    }

    private function buildAttendanceQuery(string $whereClause): string
    {
        return "
            SELECT
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO,
                A.HORA AS ENTRADA_HORA,
                A.TIPO AS TIPO_REGISTRO,
                A.ID_ASISTENCIA,
                A.ID_EMPLEADO_HORARIO
            FROM ASISTENCIA A
            INNER JOIN EMPLEADO E ON A.ID_EMPLEADO = E.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            WHERE A.FECHA = :fecha
            AND A.TIPO = 'ENTRADA'
            AND $whereClause
            ORDER BY E.NOMBRE, E.APELLIDO, A.HORA
        ";
    }
}
