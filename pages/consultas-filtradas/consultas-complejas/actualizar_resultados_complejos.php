<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';

// Determinar qué consulta procesar
$consulta = $_GET['consulta'] ?? 'clientes_vehiculos';

// Procesar los datos POST y guardar en sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['filtros_complejos'][$consulta] = $_POST;
}

// Incluir el archivo de resultados correspondiente
include __DIR__ . "/resultados_$consulta.php";