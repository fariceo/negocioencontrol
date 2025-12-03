<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false,'mensaje'=>'Sesión expirada. Inicia sesión nuevamente']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$usuario = $_SESSION['usuario'] ?? 'default_user';
$negocio = $_SESSION['nombre_bd_negocio'];

// Recibir datos del cliente
$nombreCliente = trim($_POST['nombreCliente'] ?? '');
$correoCliente  = trim($_POST['correo'] ?? '');
$metodo_pago    = $_POST['metodo_pago'] ?? 'efectivo';

// Validaciones
if(!$nombreCliente){
    echo json_encode(['ok'=>false,'mensaje'=>'Ingrese nombre del cliente']);
    exit;
}

// Obtener carrito del vendedor
$carritoRes = $conexion->query("
    SELECT producto, precio, cantidad 
    FROM carrito 
    WHERE negocio='$negocio' 
      AND usuario='$usuario' 
      AND estado='pendiente'
");

$carrito = [];
$total = 0;

while($row = $carritoRes->fetch_assoc()){
    $precio = floatval($row['precio']);
    $cantidad = intval($row['cantidad']);
    $subtotal = $precio * $cantidad;

    $carrito[] = [
        'producto' => $row['producto'],
        'precio' => $precio,
        'cantidad' => $cantidad,
        'subtotal' => $subtotal
    ];

    $total += $subtotal;
}

if(empty($carrito)){
    echo json_encode(['ok'=>false,'mensaje'=>'El carrito está vacío']);
    exit;
}

// Convertir productos a JSON
$productos_json = json_encode($carrito, JSON_UNESCAPED_UNICODE);

// Insertar en tabla ventas
$stmt = $conexion->prepare("
    INSERT INTO ventas (negocio, vendedor, cliente, productos, total, metodo_pago, fecha_hora)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("ssssds", $negocio, $usuario, $nombreCliente, $productos_json, $total, $metodo_pago);

if(!$stmt->execute()){
    echo json_encode(['ok'=>false,'mensaje'=>'Error al registrar la venta: '.$stmt->error]);
    exit;
}

// Marcar carrito como completado
$conexion->query("
    UPDATE carrito 
    SET estado='completado' 
    WHERE negocio='$negocio' 
      AND usuario='$usuario' 
      AND estado='pendiente'
");

// Eliminar del carrito después de registrar la venta
$conexion->query("
    DELETE FROM carrito
    WHERE negocio='$negocio'
      AND usuario='$usuario'
      AND estado='completado'
");
// Respuesta OK con ID de la venta
echo json_encode(['ok'=>true,'codigo_compra'=>$conexion->insert_id]);
