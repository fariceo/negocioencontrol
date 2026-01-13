<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p>No autorizado</p>";
    exit;
}

if (!isset($_GET['usuario']) || empty($_GET['usuario'])) {
    echo "<p>Usuario no definido</p>";
    exit;
}

$usuario = $_GET['usuario'];

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario_esc = $conexion->real_escape_string($usuario);

/* ===================== SALDO PENDIENTE ===================== */
$saldoPendiente = 0;
$resSaldo = $conexion->query("
    SELECT saldo_pendiente 
    FROM saldo_pendiente 
    WHERE usuario='$usuario_esc'
    ORDER BY fecha DESC, hora DESC
    LIMIT 1
");

if ($resSaldo && $resSaldo->num_rows > 0) {
    $saldoPendiente = floatval($resSaldo->fetch_assoc()['saldo_pendiente']);
}

/* ===================== HISTORIAL ===================== */
$historial = [];
$resHistorial = $conexion->query("
    SELECT * FROM historial_credito
    WHERE usuario='$usuario_esc'
    ORDER BY fecha DESC
");

if ($resHistorial) {
    while ($row = $resHistorial->fetch_assoc()) {
        $historial[] = $row;
    }
}

/* ===================== VENTAS A CRÉDITO ===================== */
$ventasCredito = [];
$resVentas = $conexion->query("
    SELECT * FROM ventas
    WHERE cliente='$usuario_esc'
    AND metodo_pago='credito'
    ORDER BY fecha_hora DESC
");

if ($resVentas) {
    while ($row = $resVentas->fetch_assoc()) {
        $ventasCredito[] = $row;
    }
}
?>

<div class="credito-container">

<h2>📄 Ficha de crédito</h2>

<div class="credito-resumen">
    <div>
        <strong>Cliente</strong><br>
        <?= htmlspecialchars($usuario) ?>
    </div>
    <div class="saldo <?= $saldoPendiente > 0 ? 'moroso' : '' ?>">
        <strong>Saldo pendiente</strong><br>
        $<?= number_format($saldoPendiente,2) ?>
    </div>
</div>

<!-- ===================== REGISTRAR ABONO ===================== -->
<h3>💰 Registrar abono</h3>

<form action="registrar_abono.php" method="POST" class="form-abono">
    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">

    <input type="number"
           name="monto"
           step="0.01"
           min="0.01"
           placeholder="Monto del abono"
           required>

    <input type="text"
           name="concepto"
           placeholder="Ej: Pago en efectivo">

    <button type="submit">Aplicar abono</button>
</form>

<!-- ===================== VENTAS A CRÉDITO ===================== -->
<h3>🧾 Ventas a crédito</h3>

<?php if (empty($ventasCredito)): ?>
    <p style="color:#777;">No hay ventas a crédito</p>
<?php else: ?>
    <?php foreach ($ventasCredito as $venta): ?>
        <?php $productos = json_decode($venta['productos'], true); ?>
        <div class="venta-card">
            <div class="venta-fecha">
                <?= $venta['fecha_hora'] ?>
            </div>

            <?php
            $totalVenta = 0;
            foreach ($productos as $p):
                $precio   = (float)$p['precio'];
                $cantidad = (int)$p['cantidad'];
                $subtotal = $precio * $cantidad;
                $totalVenta += $subtotal;
            ?>
            <div class="producto-item">
                <?= $cantidad ?> x <?= htmlspecialchars($p['producto']) ?>
                x $<?= number_format($precio,2) ?>
                = <strong>$<?= number_format($subtotal,2) ?></strong>
            </div>
            <?php endforeach; ?>

            <div class="venta-total">
                TOTAL: $<?= number_format($totalVenta,2) ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ===================== HISTORIAL ===================== -->
<h3>📚 Historial de movimientos</h3>

<?php if (empty($historial)): ?>
    <p style="color:#777;">Sin movimientos registrados</p>
<?php else: ?>
<table class="tabla-historial">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Saldo</th>
            <th>Saldo contable</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($historial as $h): ?>
        <tr>
            <td><?= $h['fecha'] ?></td>
            <td><?= htmlspecialchars($h['concepto']) ?></td>
            <td>$<?= number_format($h['saldo'],2) ?></td>
            <td>$<?= number_format($h['saldo_contable'],2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</div>

<style>
.credito-container{
    padding:15px;
    display:flex;
    flex-direction:column;
    gap:15px
}
.credito-resumen{
    display:flex;
    justify-content:space-between;
    background:#f4f6f7;
    padding:15px;
    border-radius:10px
}
.saldo{
    font-size:1.3em;
    color:#27ae60
}
.saldo.moroso{
    color:#c0392b
}
.form-abono{
    display:flex;
    gap:8px;
    flex-wrap:wrap
}
.form-abono input{
    padding:6px;
    border-radius:6px;
    border:1px solid #ccc
}
.form-abono button{
    background:#27ae60;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:6px;
    cursor:pointer
}
.venta-card{
    background:#fff;
    padding:10px;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,.08)
}
.venta-fecha{
    font-size:.8em;
    color:#888;
    margin-bottom:6px
}
.producto-item{
    font-size:.9em;
    color:#555
}
.venta-total{
    text-align:right;
    font-weight:bold;
    color:#16a085;
    margin-top:6px
}
.tabla-historial{
    width:100%;
    border-collapse:collapse
}
.tabla-historial th,
.tabla-historial td{
    border-bottom:1px solid #ddd;
    padding:6px;
    font-size:.85em
}
.tabla-historial th{
    background:#ecf0f1
}
</style>

<script>
function initModulo(){
    console.log("Ficha crédito cargada correctamente");
}
</script>
