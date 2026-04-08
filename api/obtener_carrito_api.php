<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../core/conexion.php";

// Recibir datos
$negocio = $_POST['negocio'] ?? '';
$usuario = $_POST['usuario'] ?? '';

// Validar negocio
if (empty($negocio)) {
    echo json_encode([
        "success" => false,
        "msg" => "Negocio incompleto"
    ]);
    exit;
}

// Conexión a la BD del negocio
$conexionObj = new Conexion();
$conexion = $conexionObj->negocio($negocio);

if (!$conexion) {
    echo json_encode([
        "success" => false,
        "msg" => "Error de conexión a la base de datos"
    ]);
    exit;
}

try {
    // Consulta básica
    $sql = "SELECT 
                c.id,
                c.id_producto,
                c.cantidad,
                p.producto,
                p.precio,
                p.imagen
            FROM carrito c
            INNER JOIN productos p 
                ON c.id_producto = p.id_producto
            WHERE c.negocio = ?
              AND c.estado = 'activo'";

    // Si usuario no está vacío, agregamos la condición
    if (!empty($usuario)) {
        $sql .= " AND c.usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $negocio, $usuario);
    } else {
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $negocio);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $carrito = [];

    while ($row = $result->fetch_assoc()) {
        $carrito[] = $row;
    }

    echo json_encode([
        "success" => true,
        "carrito" => $carrito
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => $e->getMessage()
    ]);
}

$conexion->close();
?>