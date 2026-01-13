<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) exit("No autorizado");

$usuario = $_POST['usuario'] ?? '';
if (!$usuario) exit("Usuario inválido");

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $conexion->real_escape_string($usuario);

/* ===================== ACTUALIZAR DATOS CLIENTE ===================== */
if (isset($_POST['cedula']) || isset($_POST['banco']) || isset($_POST['limite_credito'])) {

    $cedula = $conexion->real_escape_string($_POST['cedula'] ?? '');
    $banco  = $conexion->real_escape_string($_POST['banco'] ?? '');
    $limite = floatval($_POST['limite_credito'] ?? 0);

    $conexion->query("
        UPDATE saldo_pendiente SET
            cedula='$cedula',
            banco='$banco',
            limite_credito=$limite
        WHERE usuario='$usuario'
    ");
}

/* ===================== MOVIMIENTO DE SALDO ===================== */
if (isset($_POST['saldo']) && isset($_POST['concepto'])) {

    $saldo = floatval($_POST['saldo']); // + deuda / - abono
    $concepto = $conexion->real_escape_string($_POST['concepto']);

    // 👇 INSERT CORRECTO SEGÚN TU TABLA REAL
    $conexion->query("
        INSERT INTO historial_credito
        (usuario, saldo, saldo_contable, concepto, fecha)
        VALUES
        ('$usuario', $saldo, $saldo, '$concepto', NOW())
    ");
}

/* ===================== REDIRECCIÓN ===================== */
header("Location: ficha_credito.php?usuario=".urlencode($usuario));
exit;
