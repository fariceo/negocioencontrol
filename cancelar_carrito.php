<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false, 'mensaje'=>'No autorizado']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$input = json_decode(file_get_contents('php://input'), true);

$producto = $conexion->real_escape_string($input['producto'] ?? '');
$usuario = $_SESSION['usuario'] ?? 'default_user';

if(!$producto){
    echo json_encode(['ok'=>false,'mensaje'=>'Producto inválido']);
    exit;
}

// Actualizamos el estado a cancelado
$sql = "UPDATE carrito 
        SET estado='cancelado' 
        WHERE negocio='{$_SESSION['nombre_bd_negocio']}' 
        AND usuario='$usuario' 
        AND producto='$producto' 
        AND estado='pendiente' 
        LIMIT 1";

if($conexion->query($sql)){
    echo json_encode(['ok'=>true,'mensaje'=>'Producto cancelado']);
} else {
    echo json_encode(['ok'=>false,'mensaje'=>'Error: '.$conexion->error]);
}
