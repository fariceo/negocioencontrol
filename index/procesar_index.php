<?php
header("Content-Type: application/json");
include("../conexion.php");

// LEER JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    exit(json_encode([
        'success' => false,
        'mensaje' => 'No se recibieron datos'
    ]));
}

// Sanitizar
$usuario   = trim($data['usuario'] ?? '');
$producto  = trim($data['producto'] ?? '');
$precio    = floatval($data['precio'] ?? 0);
$cantidad  = intval($data['cantidad'] ?? 0);
$total     = $precio * $cantidad;

// Valores por defecto
$estado       = 0;                  // Pendiente
$delivery     = 0;                  // Para llevar por defecto
$metodo_pago  = "pendiente";
$fecha        = date("Y-m-d");
$hora         = date("H:i:s");

// VALIDACIONES
if ($usuario == "" || strtolower($usuario) == "invitado" || $usuario == "0") {
    exit(json_encode([
        'success' => false,
        'mensaje' => 'Debe ingresar un ID válido para continuar'
    ]));
}

if ($producto == "" || $precio <= 0 || $cantidad < 1) {
    exit(json_encode([
        'success' => false,
        'mensaje' => 'Datos inválidos'
    ]));
}

// INSERTAR PEDIDO
$sql = "INSERT INTO pedidos(usuario,producto,cantidad,precio,total,estado,delivery,metodo_pago,fecha,hora)
        VALUES (?,?,?,?,?,?,?,?,?,?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssiddissss",
    $usuario,
    $producto,
    $cantidad,
    $precio,
    $total,
    $estado,
    $delivery,
    $metodo_pago,
    $fecha,
    $hora
);

if (!$stmt->execute()) {
    exit(json_encode([
        'success' => false,
        'mensaje' => 'Error al guardar: '.$stmt->error
    ]));
}

// ================================
//  CONTAR PRODUCTOS DEL CARRITO
// ================================

$consulta = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM pedidos 
    WHERE usuario=? AND estado!=2
");
$consulta->bind_param("s", $usuario);
$consulta->execute();
$result = $consulta->get_result()->fetch_assoc();
$totalPedidos = intval($result['total']);

// ================================
//  RESPUESTA FINAL ÚNICA
// ================================

echo json_encode([
    'success' => true,
    'mensaje' => 'Producto agregado',
    'totalPedidos' => $totalPedidos  // ← ESTE SE USA EN index.js
]);

?>
