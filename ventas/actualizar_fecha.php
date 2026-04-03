<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$fecha = $data['fecha'] ?? null;

if (!$id || !$fecha) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);

$stmt = $conexion->prepare("UPDATE ventas SET fecha_hora=? WHERE id=? AND negocio=?");
$stmt->bind_param("sis", $fecha, $id, $negocio);

if($stmt->execute()){
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
