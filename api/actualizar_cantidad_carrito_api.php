<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../core/conexion.php";

// Recibir datos
$negocio     = $_POST['negocio'] ?? '';
$usuario     = $_POST['usuario'] ?? '';
$id_producto = $_POST['id_producto'] ?? '';
$cantidad    = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;

if ($negocio === "" || $usuario === "" || $id_producto === "" || $cantidad < 0) {
    echo json_encode(["success" => false, "msg" => "Datos incompletos o cantidad inválida"]);
    exit;
}

// Conectar a la BD del negocio
$conexionObj = new Conexion();
$conexion = $conexionObj->negocio($negocio); // ⚡ Aquí apunta a la BD del negocio

if (!$conexion) {
    echo json_encode(["success" => false, "msg" => "No se pudo conectar a la BD del negocio"]);
    exit;
}

// Si la cantidad es 0, eliminar registro
if ($cantidad === 0) {
    $sql = "DELETE FROM carrito WHERE usuario=? AND id_producto=? AND estado='activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $usuario, $id_producto);
} else {
    // UPDATE normal
    $sql = "UPDATE carrito SET cantidad=? WHERE usuario=? AND id_producto=? AND estado='activo'";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success"=>false,"msg"=>"Error al preparar la consulta: ".$conexion->error]);
        exit;
    }
    $stmt->bind_param("iss", $cantidad, $usuario, $id_producto);
}

if ($stmt->execute()) {
    echo json_encode(["success"=>true,"msg"=>"Cantidad actualizada correctamente"]);
} else {
    echo json_encode(["success"=>false,"msg"=>"Error al actualizar: ".$stmt->error]);
}

$stmt->close();
$conexion->close();
?>