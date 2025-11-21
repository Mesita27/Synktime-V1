<?php
require_once '../../config/database.php';

// Simulate GET request
$_GET['sede_id'] = '';
$_GET['establecimiento_id'] = '';

include 'get-empleados-con-horas-extras.php';
?>