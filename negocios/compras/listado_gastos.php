<?php
session_start();
header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode([]);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$sql = "SELECT 
            id,
            usuario,
            tipo,
            descripcion,
            cantidad,
            precio,
            (cantidad * precio) AS total,
            fecha
        FROM gastos
        ORDER BY fecha DESC";

$result = $conexion->query($sql);

$gastos = [];
while ($row = $result->fetch_assoc()) {
    $gastos[] = $row;
}

echo json_encode($gastos);
