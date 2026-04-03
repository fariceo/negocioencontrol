<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) exit("No autorizado");

$usuario = $_POST['usuario'] ?? '';
if (!$usuario) exit("Usuario inválido");

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $conexion->real_escape_string($usuario);

/* ===================== DATOS CLIENTE ===================== */
if (
    isset($_POST['cedula'], $_POST['banco'], $_POST['telefono'], $_POST['limite_credito'])
) {
    $cedula   = $conexion->real_escape_string($_POST['cedula']);
    $banco    = $conexion->real_escape_string($_POST['banco']);
    $telefono = trim($_POST['telefono']);
    $limite   = floatval($_POST['limite_credito']);

    if ($telefono === '') {
        $telefono = NULL;
    }


    $totalPedido = $total; // total del pedido actual

$stmt = $conexion->prepare("
    SELECT saldo_pendiente, limite_credito
    FROM saldo_pendiente
    WHERE usuario = ?
");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$saldo = $data['saldo_pendiente'];
$limite = $data['limite_credito'];

if (($saldo + $totalPedido) > $limite) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "⚠️ Límite de crédito superado"
    ]);
    exit;
}

    // 1️⃣ INTENTAR UPDATE
    $conexion->query("
        UPDATE saldo_pendiente SET
            telefono = ".($telefono === NULL ? "NULL" : "'$telefono'").",
            cedula = '$cedula',
            banco = '$banco',
            limite_credito = $limite
        WHERE usuario = '$usuario'
    ");

    // 2️⃣ SI NO EXISTE, INSERTAR CON CAMPOS OBLIGATORIOS
    if ($conexion->affected_rows === 0) {
        $conexion->query("
            INSERT INTO saldo_pendiente
            (usuario, telefono, cedula, banco, limite_credito,
             saldo_pendiente, accion, fecha, hora, estado, alerta)
            VALUES (
                '$usuario',
                ".($telefono === NULL ? "NULL" : "'$telefono'").",
                '$cedula',
                '$banco',
                $limite,
                0,
                'INICIO',
                CURDATE(),
                CURTIME(),
                'NORMAL',
                0
            )
        ");
    }

}



/* ===================== MOVIMIENTO DE SALDO ===================== */
if (isset($_POST['saldo'], $_POST['concepto'])) {

    $saldo_raw = str_replace(',', '.', $_POST['saldo']);

    if (!preg_match('/^-?\d+(\.\d+)?$/', $saldo_raw)) {
        exit("Formato de monto inválido");
    }

    $saldo = floatval($saldo_raw);
    $concepto = $conexion->real_escape_string($_POST['concepto']);

    $conexion->query("
        INSERT INTO historial_credito
        (usuario, saldo, saldo_contable, concepto, fecha)
        VALUES
        ('$usuario', $saldo, $saldo, '$concepto', NOW())
    ");
}

header("Location: ficha_credito.php?usuario=".urlencode($usuario));
exit;
