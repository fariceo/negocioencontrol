<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../core/conexion.php";

$conexionObj = new Conexion();
$conexion = $conexionObj->central();

$negocio     = $_POST['negocio'] ?? '';
$usuario     = $_POST['usuario'] ?? '';
$id_producto = $_POST['id_producto'] ?? '';

if ($negocio == "" || $usuario == "" || $id_producto == "") {

    echo json_encode([
        "success" => false,
        "msg" => "Datos incompletos"
    ]);
    exit;
}

$sql = "DELETE FROM carrito 
        WHERE negocio = ? 
        AND usuario = ? 
        AND id_producto = ?
        AND estado = 'activo'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sss", $negocio, $usuario, $id_producto);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "msg" => "Producto eliminado del carrito"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "msg" => "Error al eliminar producto"
    ]);
}

$stmt->close();
$conexion->close();