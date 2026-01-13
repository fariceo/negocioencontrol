<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) exit("No autorizado");
if (empty($_GET['usuario'])) exit("Usuario no definido");

$usuario = $_GET['usuario'];

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario_esc = $conexion->real_escape_string($usuario);

/* ===================== SALDO REAL DESDE HISTORIAL ===================== */
$resSaldoReal = $conexion->query("
    SELECT IFNULL(SUM(saldo),0) AS total
    FROM historial_credito
    WHERE usuario='$usuario_esc'
");
$saldoReal = floatval($resSaldoReal->fetch_assoc()['total']);

/* ===================== DATOS CLIENTE ===================== */
$resCliente = $conexion->query("
    SELECT * FROM saldo_pendiente WHERE usuario='$usuario_esc' LIMIT 1
");
$cliente = $resCliente->fetch_assoc();

/* ===================== ESTADO / ALERTA ===================== */
$limite = floatval($cliente['limite_credito'] ?? 0);
$estado = 'NORMAL';
$alerta = '';

if ($saldoReal > 0 && $limite > 0 && $saldoReal > $limite) {
    $estado = 'MOROSO';
    $alerta = 'Límite de crédito superado';
}

/* ===================== ACTUALIZAR SALDO_PENDIENTE ===================== */
$conexion->query("
    INSERT INTO saldo_pendiente 
    (usuario, saldo_pendiente, cedula, banco, limite_credito, estado, alerta, fecha, hora)
    VALUES (
        '$usuario_esc',
        $saldoReal,
        '".($cliente['cedula'] ?? '')."',
        '".($cliente['banco'] ?? '')."',
        $limite,
        '$estado',
        '$alerta',
        CURDATE(),
        CURTIME()
    )
    ON DUPLICATE KEY UPDATE
        saldo_pendiente = $saldoReal,
        estado = '$estado',
        alerta = '$alerta',
        fecha = CURDATE(),
        hora = CURTIME()
");

/* ===================== HISTORIAL ===================== */
$historial = [];
$resHistorial = $conexion->query("
    SELECT * FROM historial_credito
    WHERE usuario='$usuario_esc'
    ORDER BY fecha ASC
");
while ($row = $resHistorial->fetch_assoc()) {
    $historial[] = $row;
}

/* ===================== VENTAS A CRÉDITO ===================== */
$ventasCredito = [];
$resVentas = $conexion->query("
    SELECT * FROM ventas
    WHERE cliente='$usuario_esc'
    AND metodo_pago='credito'
    ORDER BY fecha_hora DESC
");
while ($row = $resVentas->fetch_assoc()) {
    $ventasCredito[] = $row;
}
?>

<button onclick="window.location.href='../../index.php'" class="btn-ira">Ir a Index</button>

<style>
.btn-ira {
    background-color: #2980b9;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 1em;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-ira:hover {
    background-color: #1c5980;
}
</style>




<div class="credito-container">
<h2>📄 Ficha de crédito</h2>

<div class="credito-resumen">
    <div>
        <strong>Cliente</strong><br><?= htmlspecialchars($usuario) ?>
    </div>
    <div class="saldo <?= $saldoReal > 0 ? 'moroso' : '' ?>">
        <strong>Saldo pendiente</strong><br>
        $<?= number_format($saldoReal,2) ?><br>
        <small><?= $estado ?></small>
    </div>
</div>

<h3>🪪 Datos del cliente</h3>
<form action="actualizar_datos.php" method="POST">
    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
    <input name="cedula" placeholder="Cédula" value="<?= $cliente['cedula'] ?? '' ?>">
    <input name="banco" placeholder="Banco" value="<?= $cliente['banco'] ?? '' ?>">
    <input name="limite_credito" type="number" step="0.01" placeholder="Límite"
           value="<?= $cliente['limite_credito'] ?? '' ?>">
    <button>Guardar</button>
</form>

<h3>💰 Movimiento de saldo</h3>
<form action="actualizar_datos.php" method="POST">
    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
    <input type="number" step="0.01" name="saldo" placeholder="+ deuda / - abono" required>
    <input name="concepto" placeholder="Concepto" required>
    <button>Registrar</button>
</form>

<h3>🧾 Ventas a crédito</h3>
<?php foreach ($ventasCredito as $v): ?>
    <?php $productos = json_decode($v['productos'], true); ?>
    <div class="venta-card">
        <small><?= $v['fecha_hora'] ?></small>
        <?php foreach ($productos as $p): ?>
            <div>
                <?= $p['cantidad'] ?> x <?= htmlspecialchars($p['producto']) ?> =
                $<?= number_format($p['cantidad']*$p['precio'],2) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<h3>📚 Historial contable</h3>
<table class="tabla-historial">
<tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Saldo</th></tr>
<?php
$acum = 0;
foreach ($historial as $h):
    $acum += $h['saldo'];
?>
<tr class="<?= $h['saldo'] < 0 ? 'negativo':'positivo' ?>">
    <td><?= $h['fecha'] ?></td>
    <td><?= htmlspecialchars($h['concepto']) ?></td>
    <td>$<?= number_format($h['saldo'],2) ?></td>
    <td>$<?= number_format($acum,2) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<style>
.credito-container{padding:15px}
.saldo{font-size:1.3em;color:#27ae60}
.saldo.moroso{color:#c0392b}
.negativo{color:#c0392b}
.positivo{color:#27ae60}
.venta-card{background:#fff;padding:8px;margin:6px 0}
.tabla-historial{width:100%;border-collapse:collapse}
.tabla-historial td,th{border-bottom:1px solid #ddd;padding:5px}
</style>
