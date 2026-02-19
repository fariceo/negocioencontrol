<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) exit("No autorizado");
if (empty($_GET['usuario'])) exit("Usuario no definido");

$usuario = $_GET['usuario'];

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario_esc = $conexion->real_escape_string($usuario);

/* ===================== SALDO REAL ===================== */
$resSaldoReal = $conexion->query("
    SELECT IFNULL(SUM(saldo),0) AS total
    FROM historial_credito
    WHERE usuario='$usuario_esc'
");
$saldoReal = floatval($resSaldoReal->fetch_assoc()['total']);

/* ===================== DATOS CLIENTE ===================== */
$resCliente = $conexion->query("
    SELECT * FROM saldo_pendiente
    WHERE usuario='$usuario_esc'
    LIMIT 1
");
$cliente = $resCliente->fetch_assoc() ?: [];

/* ===================== ESTADO ===================== */
$limite = floatval($cliente['limite_credito'] ?? 0);
$estado = 'NORMAL';
$alerta = '';

if ($saldoReal > 0 && $limite > 0 && $saldoReal > $limite) {
    $estado = 'MOROSO';
    $alerta = 'Límite de crédito superado';
}

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

<meta charset="UTF-8" name="viewport" content="width=device-width">

<button onclick="window.location.href='../../index.php'" class="btn-ira">Ir a Index</button>

<style>
.btn-ira {
    background-color:#2980b9;color:#fff;border:none;
    padding:8px 16px;border-radius:6px;cursor:pointer
}
.btn-ira:hover{background:#1c5980}
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

<form action="actualizar_datos.php" method="POST" class="form-cliente">
    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">

    <div class="form-group">
        <label for="cedula">Cédula</label>
        <input id="cedula"
               name="cedula"
               type="text"
               placeholder="Cédula"
               value="<?= $cliente['cedula'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label for="banco">Banco</label>
        <input id="banco"
               name="banco"
               type="text"
               placeholder="Banco"
               value="<?= $cliente['banco'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label for="telefono">Teléfono</label>
        <input id="telefono"
               name="telefono"
               type="tel"
               placeholder="Teléfono"
               value="<?= $cliente['telefono'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label for="limite_credito">Límite de crédito</label>
        <input id="limite_credito"
               name="limite_credito"
               type="number"
               step="0.01"
               placeholder="Límite"
               value="<?= $cliente['limite_credito'] ?? '' ?>">
    </div>

    <button type="submit">Guardar</button>
</form>
<style>
.form-cliente {
    max-width: 420px;
}

.form-group {
    margin-bottom: 10px;
}

.form-group label {
    display: block;
    font-size: 0.9em;
    font-weight: 600;
    margin-bottom: 3px;
}

.form-group input {
    width: 100%;
    padding: 7px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
</style>

<h3>💰 Movimiento de saldo</h3>
<form action="actualizar_datos.php" method="POST">
    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
    <input type="text"
           name="saldo"
           inputmode="text"
           pattern="-?[0-9]+([.,][0-9]+)?"
           placeholder="+ deuda / - abono"
           required>
    <input name="concepto" placeholder="Concepto" required>
    <button>Registrar</button>
</form>

<h3>📚 Historial contable</h3>
<table class="tabla-historial">
<tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Saldo</th></tr>
<?php $acum = 0; foreach ($historial as $h): $acum += $h['saldo']; ?>
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
.tabla-historial{width:100%;border-collapse:collapse}
.tabla-historial td,th{border-bottom:1px solid #ddd;padding:5px}
</style>


<h3>🛒 Historial de compras</h3>

<?php if (empty($ventasCredito)): ?>
    <p style="font-size:14px;color:#777;">
        Este cliente no registra compras a crédito.
    </p>
<?php else: ?>
<table class="tabla-historial">
    <tr>
        <th>Fecha</th>
        <th>Vendedor</th>
        <th>Productos</th>
        <th>Total</th>
        <th>Método</th>
    </tr>

    <?php foreach ($ventasCredito as $v): ?>
    <tr>
        <td><?= $v['fecha_hora'] ?></td>
        <td><?= htmlspecialchars($v['vendedor']) ?></td>
        <td class="productos-cell">
<?php
$lista = json_decode($v['productos'], true);

if (is_array($lista)):
    foreach ($lista as $p):
?>
    <div class="producto-item">
        <span class="producto-nombre"><?= htmlspecialchars($p['producto']) ?></span>
        <span class="producto-detalle">
            <?= $p['cantidad'] ?> × $<?= number_format($p['precio'],2) ?>
        </span>
    </div>
<?php
    endforeach;
else:
    echo htmlspecialchars($v['productos']);
endif;
?>
</td>

        <td>$<?= number_format($v['total'],2) ?></td>
        <td><?= strtoupper($v['metodo_pago']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<style>.productos-cell {
    font-size: 13px;
    line-height: 1.3em;
}

.producto-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    border-bottom: 1px dashed #ddd;
}

.producto-item:last-child {
    border-bottom: none;
}

.producto-nombre {
    font-weight: 600;
}

.producto-detalle {
    color: #555;
    white-space: nowrap;
}
</style>