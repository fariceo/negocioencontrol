<?php

header('Content-Type: application/json; charset=UTF-8');
require_once "../core/conexion.php";

$texto = $_POST['texto'] ?? '';
$nombre_bd = $_POST['nombre_bd'] ?? '';

if ($nombre_bd == '') {
    echo json_encode([]);
    exit;
}

$conexionObj = new Conexion();
$conexion = $conexionObj->negocio($nombre_bd);

$texto = $conexion->real_escape_string($texto);

$sql = "
SELECT 
id_producto,
codigo_barra,
producto,
precio,
categoria,
stock_inicial,
imagen
FROM productos
WHERE codigo_barra LIKE '%$texto%'
OR producto LIKE '%$texto%'
LIMIT 20
";

$result = $conexion->query($sql);

$datos = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
}

echo json_encode($datos);

$conexion->close();