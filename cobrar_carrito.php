<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'] ?? 'default_user';

$nombreCliente = trim($_POST['nombreCliente'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$metodo_pago = trim($_POST['metodo_pago'] ?? '');

if (!$nombreCliente) {
    echo json_encode(['ok' => false, 'mensaje' => 'Nombre del cliente requerido']);
    exit;
}

// Traer carrito activo del usuario
$res = $conexion->query("
    SELECT c.id, c.id_producto, p.producto, p.precio, c.cantidad 
    FROM carrito c 
    JOIN productos p ON c.id_producto = p.id_producto 
    WHERE c.usuario = '{$usuario}' AND c.estado = 'activo'
");

$carrito = [];
$total = 0;

while ($row = $res->fetch_assoc()) {
    $carrito[] = $row;
    $total += (float)$row['precio'] * (int)$row['cantidad'];
}

if (empty($carrito)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Carrito vacío']);
    exit;
}

// Crear código de venta
$codigo_compra = 'VENTA' . time();
$productos_json = json_encode($carrito, JSON_UNESCAPED_UNICODE);

// Calcular total directamente desde MySQL
$resTotal = $conexion->query("
    SELECT SUM(p.precio * c.cantidad) AS total
    FROM carrito c
    JOIN productos p ON c.id_producto = p.id_producto
    WHERE c.usuario = '{$usuario}'
      AND c.estado = 'activo'
");

$rowTotal = $resTotal->fetch_assoc();
$total = (float)($rowTotal['total'] ?? 0);

// Insertar en ventas
$stmt = $conexion->prepare("
    INSERT INTO ventas (
        negocio,
        vendedor,
        cliente,
        productos,
        total,
        metodo_pago,
        fecha_hora,
        correo,
        telefono,
        direccion
    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
");

$stmt->bind_param(
    "ssssdssss",
    $_SESSION['nombre_bd_negocio'], // negocio
    $usuario,                       // vendedor
    $nombreCliente,                 // cliente
    $productos_json,                // productos
    $total,                         // total
    $metodo_pago,                   // metodo_pago
    $correo,                        // correo
    $telefono,                      // telefono
    $direccion                      // direccion
);

if (!$stmt->execute()) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al registrar la venta: ' . $stmt->error
    ]);
    $stmt->close();
    exit;
}

$id_venta = $conexion->insert_id;
$stmt->close();

// Marcar carrito como procesado
$stmt = $conexion->prepare("
    UPDATE carrito 
    SET estado = 'procesado' 
    WHERE usuario = ? AND estado = 'activo'
");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->close();

echo json_encode([
    'ok' => true,
    'codigo_compra' => $codigo_compra,
    'id_venta' => $id_venta
]);
?>