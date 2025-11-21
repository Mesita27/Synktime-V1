<?php
require_once 'config/database.php';

// Simular una llamada POST a la API get-horas.php para regenerar horas extras
$_POST['empleados'] = [100];
$_POST['fechaDesde'] = '2025-09-28';
$_POST['fechaHasta'] = '2025-09-28';

// Incluir el archivo de la API
echo "REGENERANDO HORAS EXTRAS CON TIPOS CORRECTOS...\n";
include 'api/horas-trabajadas/get-horas.php';

echo "Proceso completado. Verifica las horas extras generadas.\n";
?>