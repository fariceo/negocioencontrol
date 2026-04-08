<?php
header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? '';
//$nombre_bd = $_POST['nombre_bd'] ?? '';
$nombre_bd = "refu";

if ($codigo == '' || $nombre_bd == '') {
    echo json_encode(["success" => false, "msg" => "Faltan parámetros"]);
    exit;
}

// Conexión a la BD del negocio
$conexion = new mysqli("localhost", "root", "clave", $nombre_bd);
if ($conexion->connect_error) {
    echo json_encode(["success" => false, "msg" => "Error de conexión"]);
    exit;
}

// Buscar producto
$stmt = $conexion->prepare("SELECT id_producto, codigo_barra, producto, precio, categoria, stock_inicial, imagen FROM productos WHERE codigo_barra = ? LIMIT 1");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "producto" => $row]);
} else {
    echo json_encode(["success" => false, "msg" => "Producto no encontrado"]);
}
?>
