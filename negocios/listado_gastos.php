<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if(!isset($_SESSION['nombre_bd_negocio'])){
    echo json_encode([]);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$res = $conexion->query("SELECT id, usuario, tipo, descripcion, cantidad, precio, fecha FROM gastos ORDER BY fecha DESC");

$gastos = [];
while($row = $res->fetch_assoc()){
    $gastos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($gastos);
