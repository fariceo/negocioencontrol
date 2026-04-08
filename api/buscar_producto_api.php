<?php
header('Content-Type: application/json; charset=UTF-8');
require_once "../core/conexion.php";

$codigo = $_POST['codigo'] ?? '';
$nombre_bd = $_POST['nombre_bd'] ?? '';

if ($codigo == '' || $nombre_bd == '') {
    echo json_encode(["success" => false, "msg" => "Faltan parámetros"]);
    exit;
}

$conexionObj = new Conexion();
$conexion = $conexionObj->negocio($nombre_bd);

$stmt = $conexion->prepare("
    SELECT id_producto, codigo_barra, producto, precio, categoria, stock_inicial, imagen 
    FROM productos 
    WHERE codigo_barra = ? 
    LIMIT 1
");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "producto" => $row]);
} else {
    echo json_encode(["success" => false, "msg" => "Producto no encontrado"]);
}

$stmt->close();
$conexion->close();
?>