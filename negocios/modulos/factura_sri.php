<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// ============================
// DATOS RECIBIDOS
// ============================
$id_venta               = intval($_POST['id_venta'] ?? 0);
$cliente_nombre         = trim($_POST['cliente_nombre'] ?? '');
$cliente_identificacion = trim($_POST['cliente_identificacion'] ?? '');
$tipo_identificacion    = trim($_POST['tipo_identificacion'] ?? '07'); // 07 consumidor final
$correo                 = trim($_POST['correo'] ?? '');
$direccion              = trim($_POST['direccion'] ?? '');
$telefono               = trim($_POST['telefono'] ?? '');
$metodo_pago            = trim($_POST['metodo_pago'] ?? 'efectivo');

if ($id_venta <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de venta inválido']);
    exit;
}

if ($cliente_nombre === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Nombre del cliente requerido']);
    exit;
}

if ($tipo_identificacion !== '07' && $cliente_identificacion === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar la identificación del cliente']);
    exit;
}

if ($tipo_identificacion === '07' && $cliente_identificacion === '') {
    $cliente_identificacion = '9999999999999';
}

try {
    // ============================
    // 1) VERIFICAR SI YA EXISTE FACTURA PARA ESTA VENTA
    // ============================
    $stmt = $conexion->prepare("
        SELECT id_factura
        FROM facturacion_ventas
        WHERE id_venta = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $resExiste = $stmt->get_result();

    if ($resExiste->num_rows > 0) {
        $stmt->close();
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Esta venta ya tiene una factura registrada'
        ]);
        exit;
    }
    $stmt->close();

    // ============================
    // 2) BUSCAR LA VENTA EN TABLA ventas
    // ============================
    $stmt = $conexion->prepare("
        SELECT id, cliente, productos, total, metodo_pago, fecha_hora, correo, telefono, direccion
        FROM ventas
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $resVenta = $stmt->get_result();

    if ($resVenta->num_rows === 0) {
        $stmt->close();
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró la venta para facturar'
        ]);
        exit;
    }

    $venta = $resVenta->fetch_assoc();
    $stmt->close();

    $productos_json = $venta['productos'] ?? '[]';
    $productos = json_decode($productos_json, true);

    if (!is_array($productos) || empty($productos)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La venta no tiene productos válidos para facturar'
        ]);
        exit;
    }

    // ============================
    // 3) GENERAR CÓDIGO DE FACTURA
    // ============================
    $codigo_compra = 'FAC-' . date('YmdHis') . '-' . $id_venta;

    // ============================
    // 4) CALCULAR TOTALES TRIBUTARIOS
    // ============================
    $subtotal_0 = 0.00;
    $subtotal_iva = 0.00;
    $iva_total = 0.00;

    // IVA Ecuador actual
    $ivaPorDefecto = 15.00;
    $factorIva = 1 + ($ivaPorDefecto / 100);

    foreach ($productos as $item) {
        $precio = isset($item['precio']) ? (float)$item['precio'] : 0;
        $cantidad = isset($item['cantidad']) ? (float)$item['cantidad'] : 0;
        $subtotalLinea = $precio * $cantidad;

        // Asumimos que el precio YA incluye IVA
        $base = $subtotalLinea / $factorIva;
        $iva = $subtotalLinea - $base;

        $subtotal_iva += $base;
        $iva_total += $iva;
    }

    // Total real cobrado
    $total = (float)($venta['total'] ?? 0);

    // Si el usuario no mandó datos, usar los de la venta
    if ($correo === '') $correo = $venta['correo'] ?? '';
    if ($telefono === '') $telefono = $venta['telefono'] ?? '';
    if ($direccion === '') $direccion = $venta['direccion'] ?? '';

    // Normalizar método de pago SRI
    $mapaMetodoPago = [
        'efectivo' => '01',
        'transferencia' => '20',
        'credito' => '19'
    ];

    $metodo_pago_sri = $mapaMetodoPago[strtolower($metodo_pago)] ?? '01';

    // ============================
    // 5) INSERTAR EN facturacion_ventas
    // ============================
    $stmt = $conexion->prepare("
        INSERT INTO facturacion_ventas (
            id_venta,
            codigo_compra,
            cliente_nombre,
            cliente_identificacion,
            tipo_identificacion,
            correo,
            direccion,
            telefono,
            metodo_pago,
            subtotal_0,
            subtotal_iva,
            iva_total,
            total,
            estado_sri,
            ambiente,
            clave_acceso,
            numero_autorizacion,
            xml_generado,
            fecha_emision
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', 'pruebas', NULL, NULL, NULL, NOW())
    ");

    $stmt->bind_param(
        "issssssssdddd",
        $id_venta,
        $codigo_compra,
        $cliente_nombre,
        $cliente_identificacion,
        $tipo_identificacion,
        $correo,
        $direccion,
        $telefono,
        $metodo_pago_sri,
        $subtotal_0,
        $subtotal_iva,
        $iva_total,
        $total
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar factura: " . $stmt->error);
    }

    $id_factura = $stmt->insert_id;
    $stmt->close();

    $stmt = $conexion->prepare("
    UPDATE ventas
    SET id_factura = ?
    WHERE id = ?
");
$stmt->bind_param("ii", $id_factura, $id_venta);
$stmt->execute();
$stmt->close();

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Factura fiscal registrada correctamente',
        'id_factura' => $id_factura,
        'codigo_compra' => $codigo_compra,
        'subtotal_iva' => number_format($subtotal_iva, 2, '.', ''),
        'iva_total' => number_format($iva_total, 2, '.', ''),
        'total' => number_format($total, 2, '.', '')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al registrar factura',
        'error' => $e->getMessage()
    ]);
}
?>