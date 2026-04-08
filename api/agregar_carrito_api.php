<?php
header("Content-Type: application/json; charset=UTF-8");
file_put_contents("debug_post.txt", json_encode($_POST));

require_once "../core/conexion.php";

// recibir datos
$id_producto = $_POST['id_producto'] ?? '';
$negocio     = $_POST['negocio'] ?? '';
$usuario     = $_POST['usuario'] ?? ''; // ahora obligatorio: correo del login
$cantidad    = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
$estado      = 'activo';
$fecha       = date("Y-m-d H:i:s");

// validar
if (empty($id_producto) || empty($negocio) || empty($usuario)) {
    echo json_encode([
        "success" => false,
        "msg" => "Faltan parámetros"
    ]);
    exit;
}

// conexión
$conexionObj = new Conexion();
$conexion = $conexionObj->negocio($negocio);

if (!$conexion) {
    echo json_encode([
        "success" => false,
        "msg" => "Error de conexión"
    ]);
    exit;
}

// verificar si producto ya existe para este usuario
$stmt = $conexion->prepare("
    SELECT id, cantidad 
    FROM carrito 
    WHERE id_producto = ? 
    AND usuario = ? 
    AND estado = 'activo'
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "msg" => $conexion->error
    ]);
    exit;
}

$stmt->bind_param("ss", $id_producto, $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // actualizar cantidad
    $row = $result->fetch_assoc();
    $nuevaCantidad = $row['cantidad'] + $cantidad;

    $update = $conexion->prepare("
        UPDATE carrito
        SET cantidad = ?
        WHERE id = ?
    ");

    $update->bind_param("ii", $nuevaCantidad, $row['id']);

    if ($update->execute()) {
        echo json_encode([
            "success" => true,
            "msg" => "Cantidad actualizada",
            "usuario" => $usuario
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "msg" => $update->error
        ]);
    }
} else {
    // insertar producto
    $insert = $conexion->prepare("
        INSERT INTO carrito
        (negocio, usuario, id_producto, cantidad, estado, fecha)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$insert) {
        echo json_encode([
            "success" => false,
            "msg" => $conexion->error
        ]);
        exit;
    }

    $insert->bind_param(
        "sssiss",
        $negocio,
        $usuario,
        $id_producto,
        $cantidad,
        $estado,
        $fecha
    );

    if ($insert->execute()) {
        echo json_encode([
            "success" => true,
            "msg" => "Producto agregado al carrito",
            "usuario" => $usuario
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "msg" => $insert->error
        ]);
    }
}

$conexion->close();
?>