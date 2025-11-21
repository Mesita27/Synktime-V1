<?php
/**
 * Utilidades para el cálculo de estados de asistencia
 * Aplica la misma lógica que se usa en attendance.js
 */

/**
 * Normaliza un valor de tolerancia a minutos enteros
 *
 * @param mixed $tolerancia Valor de tolerancia a normalizar
 * @return int Tolerancia en minutos
 */
function normalizarToleranciaMinutos($tolerancia): int {
    if ($tolerancia === null) {
        return 0;
    }

    if (is_int($tolerancia)) {
        return $tolerancia;
    }

    if (is_float($tolerancia)) {
        return (int)round($tolerancia);
    }

    if (is_string($tolerancia)) {
        $texto = trim($tolerancia);
        if ($texto === '') {
            return 0;
        }

        if (is_numeric($texto)) {
            return (int)round((float)$texto);
        }

        if (strpos($texto, ':') !== false) {
            $partes = array_map('intval', explode(':', $texto));
            $horas = $partes[0] ?? 0;
            $minutos = $partes[1] ?? 0;
            $segundos = $partes[2] ?? 0;
            $total = ($horas * 60) + $minutos;
            if ($segundos >= 30) {
                $total++;
            }
            return $total;
        }

        if (preg_match('/\d+/', $texto, $coincidencias)) {
            return (int)$coincidencias[0];
        }
    }

    return 0;
}

/**
 * Alinea una hora programada (con o sin fecha) respecto a una referencia real,
 * ajustando el día para turnos que cruzan la medianoche.
 */
function construirDateTimeAligned($horaProgramada, DateTimeInterface $referencia, $esNocturno = false): ?DateTime {
    if (!$horaProgramada) {
        return null;
    }

    try {
        $timezone = $referencia->getTimezone();
        $soloHora = preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', trim((string)$horaProgramada)) === 1;

        if ($soloHora) {
            $horaNormalizada = normalizarHora($horaProgramada);
            if ($horaNormalizada === null) {
                return null;
            }

            [$hora, $minuto, $segundo] = array_map('intval', explode(':', $horaNormalizada));

            $fechaProgramada = new DateTime($referencia->format('Y-m-d H:i:s'), $timezone);
            $fechaProgramada->setTime($hora, $minuto, $segundo);
        } else {
            $fechaProgramada = new DateTime($horaProgramada, $timezone);
        }

        $diferencia = $fechaProgramada->getTimestamp() - $referencia->getTimestamp();
        $mitadDiaSegundos = 12 * 60 * 60;

        if (!$esNocturno) {
            if ($diferencia > $mitadDiaSegundos) {
                $fechaProgramada->modify('-1 day');
            } elseif ($diferencia < -$mitadDiaSegundos) {
                $fechaProgramada->modify('+1 day');
            }
        }

        return $fechaProgramada;
    } catch (Exception $e) {
        error_log("Error al construir DateTime alineado: " . $e->getMessage());
        return null;
    }
}

/**
 * Calcula el estado de entrada basado en hora programada, hora real y tolerancia
 *
 * @param string $horaEntradaProgramada Hora programada (HH:MM:SS)
 * @param string $horaEntradaReal Hora real (HH:MM:SS)
 * @param int|string|null $toleranciaMinutos Tolerancia en minutos o en formato HH:MM:SS
 * @return string Estado: 'Temprano', 'Puntual', 'Tardanza' o '--'
 */
function calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $toleranciaMinutos = 0) {
    if (!$horaEntradaProgramada || !$horaEntradaReal) {
        return '--';
    }

    try {
        $toleranciaNormalizada = normalizarToleranciaMinutos($toleranciaMinutos);

        // Crear objetos DateTime para comparación precisa
        $entradaReal = new DateTime($horaEntradaReal);
        $entradaProgramada = construirDateTimeAligned($horaEntradaProgramada, $entradaReal) ?? new DateTime($horaEntradaProgramada);

        $diferenciaSegundos = $entradaReal->getTimestamp() - $entradaProgramada->getTimestamp();

        if ($toleranciaNormalizada <= 0) {
            if ($diferenciaSegundos < 0) {
                return 'Temprano';
            }

            if ($diferenciaSegundos === 0) {
                return 'Puntual';
            }

            return 'Tardanza';
        }

        $diferenciaMinutosRedondeada = $diferenciaSegundos >= 0
            ? floor($diferenciaSegundos / 60)
            : ceil($diferenciaSegundos / 60);

        if ($diferenciaMinutosRedondeada < -$toleranciaNormalizada) {
            return 'Temprano';
        }

        if ($diferenciaMinutosRedondeada <= $toleranciaNormalizada) {
            return 'Puntual';
        }

        return 'Tardanza';
    } catch (Exception $e) {
        error_log("Error calculando estado de entrada: " . $e->getMessage());
        return '--';
    }
}

/**
 * Calcula el estado de salida basado en hora programada, hora real y tolerancia
 *
 * @param string $horaSalidaProgramada Hora programada (HH:MM:SS)
 * @param string $horaSalidaReal Hora real (HH:MM:SS)
 * @param int|string|null $toleranciaMinutos Tolerancia en minutos o en formato HH:MM:SS
 * @param bool $esNocturno Si es turno nocturno
 * @return string Estado: 'Temprano', 'Normal', 'Tardanza' o '--'
 */
function calcularEstadoSalida($horaSalidaProgramada, $horaSalidaReal, $toleranciaMinutos = 0, $esNocturno = false) {
    if (!$horaSalidaProgramada || !$horaSalidaReal) {
        return '--';
    }

    try {
        $toleranciaNormalizada = normalizarToleranciaMinutos($toleranciaMinutos);

        // Crear objetos DateTime para comparación precisa
        $salidaReal = new DateTime($horaSalidaReal);
        $salidaProgramada = construirDateTimeAligned($horaSalidaProgramada, $salidaReal, $esNocturno) ?? new DateTime($horaSalidaProgramada);

        $diferenciaSegundos = $salidaReal->getTimestamp() - $salidaProgramada->getTimestamp();

        if ($toleranciaNormalizada <= 0) {
            if ($diferenciaSegundos < 0) {
                return 'Temprano';
            }

            if ($diferenciaSegundos === 0) {
                return 'Puntual';
            }

            return 'Tardanza';
        }

        $diferenciaMinutosRedondeada = $diferenciaSegundos >= 0
            ? floor($diferenciaSegundos / 60)
            : ceil($diferenciaSegundos / 60);

        if ($diferenciaMinutosRedondeada < -$toleranciaNormalizada) {
            return 'Temprano';
        }

        if ($diferenciaMinutosRedondeada <= $toleranciaNormalizada) {
            return 'Puntual';
        }

        return 'Tardanza';
    } catch (Exception $e) {
        error_log("Error calculando estado de salida: " . $e->getMessage());
        return '--';
    }
}

/**
 * Normaliza una hora al formato HH:MM:SS
 *
 * @param string $hora Hora en cualquier formato
 * @return string Hora normalizada o null si es inválida
 */
function normalizarHora($hora) {
    if (!$hora) {
        return null;
    }

    try {
        // Si ya está en formato HH:MM:SS, devolver tal cual
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            return $hora;
        }

        // Si está en formato HH:MM, agregar segundos
        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        // Intentar parsear otros formatos
        $dateTime = new DateTime($hora);
        return $dateTime->format('H:i:s');
    } catch (Exception $e) {
        error_log("Error normalizando hora '$hora': " . $e->getMessage());
        return null;
    }
}

/**
 * Calcula estadísticas de asistencia aplicando la lógica consistente
 *
 * @param array $asistencias Array de asistencias con datos de horario
 * @param int $totalEmpleados Total de empleados activos
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasAsistenciaConsistentes($asistencias, $totalEmpleados) {
    $tempranos = 0;
    $puntuales = 0;
    $tardanzas = 0;
    $empleadosAsistieron = [];

    foreach ($asistencias as $asistencia) {
        $idEmpleado = $asistencia['ID_EMPLEADO'] ?? $asistencia['id_empleado'];
        $empleadosAsistieron[] = $idEmpleado;

        $horaEntradaProgramada = $asistencia['HORA_ENTRADA'] ?? $asistencia['hora_entrada_programada'] ?? null;
        $horaEntradaReal = $asistencia['hora_entrada'] ?? $asistencia['hora_entrada_real'] ?? null;
    $tolerancia = normalizarToleranciaMinutos($asistencia['TOLERANCIA'] ?? $asistencia['tolerancia'] ?? 0);

        // Normalizar horas
        $horaEntradaProgramada = normalizarHora($horaEntradaProgramada);
        $horaEntradaReal = normalizarHora($horaEntradaReal);

    $estado = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

        switch ($estado) {
            case 'Temprano':
                $tempranos++;
                break;
            case 'Puntual':
                $puntuales++;
                break;
            case 'Tardanza':
                $tardanzas++;
                break;
        }
    }

    $empleadosAsistieronUnicos = array_unique($empleadosAsistieron);
    $faltas = $totalEmpleados - count($empleadosAsistieronUnicos);

    return [
        'total_empleados' => $totalEmpleados,
        'llegadas_temprano' => $tempranos,
        'llegadas_tiempo' => $puntuales,
        'llegadas_tarde' => $tardanzas,
        'faltas' => $faltas,
        'total_asistencias' => count($empleadosAsistieronUnicos)
    ];
}
?>
