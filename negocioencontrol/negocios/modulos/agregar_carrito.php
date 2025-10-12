<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false,'mensaje'=>'Acceso no autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$producto = $data['producto'] ?? '';
$precio = $data['precio'] ?? 0;
$cantidad = $data['cantidad'] ?? 1;

if(!$producto || $cantidad < 1){
    echo json_encode(['ok'=>false,'mensaje'=>'Datos inválidos']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'] ?? 'default_user';
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);
$productoEsc = $conexion->real_escape_string($producto);

// Ver si ya existe el producto en carrito pendiente
$check = $conexion->query("SELECT id, cantidad FROM carrito WHERE negocio='$negocio' AND usuario='$usuario' AND producto='$productoEsc' AND estado='pendiente'");
if($check && $check->num_rows > 0){
    $row = $check->fetch_assoc();
    $nuevaCantidad = $row['cantidad'] + $cantidad;
    $conexion->query("UPDATE carrito SET cantidad=$nuevaCantidad WHERE id=".$row['id']);
}else{
    $stmt = $conexion->prepare("INSERT INTO carrito (negocio, usuario, producto, precio, cantidad, estado, fecha) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())");
    $stmt->bind_param("sssdi", $negocio, $usuario, $producto, $precio, $cantidad);
    $stmt->execute();
}

echo json_encode(['ok'=>true]);
