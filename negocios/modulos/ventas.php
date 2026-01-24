<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p>No autorizado</p>";
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);

$result = $conexion->query("SELECT * FROM ventas WHERE negocio='$negocio' ORDER BY fecha_hora DESC");

$ventas = [];
$total_general = 0;
if($result){
    while($row = $result->fetch_assoc()){
        $ventas[] = $row;
        $total_general += floatval($row['total']);
    }
}
?>

<div class="ventas-header" style="text-align:center; margin-bottom:15px;">
    <h2>📊 Ventas realizadas</h2>
    <p><strong>Total general de ventas:</strong> $<?= number_format($total_general,2) ?></p>
</div>

<div class="ventas-container">
<?php if(empty($ventas)): ?>
    <p style="text-align:center; color:#888; margin-top:20px;">No hay ventas registradas</p>
<?php else: ?>
<?php foreach($ventas as $venta): ?>
<?php $productos = json_decode($venta['productos'], true); ?>

<div class="venta-card">

    <div class="venta-header">
        <span><strong>Cliente / Código:</strong> <?= htmlspecialchars($venta['cliente']) ?></span>
        <span><strong>Vendedor : </strong> <?= htmlspecialchars($venta['vendedor']) ?></span>
    </div>

    <div class="venta-productos">
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

    <div class="venta-footer">
        <?php if (strtolower($venta['metodo_pago']) === 'credito'): ?>
            <a 
                href="/negocioencontrol/negocios/modulos/credito/ficha_credito.php?usuario=<?= urlencode($venta['cliente']) ?>"
                class="badge badge-credito"
                style="text-decoration:none;"
            >
                CREDITO
            </a>
        <?php else: ?>
            <span class="badge badge-<?= strtolower($venta['metodo_pago']) ?>">
                <?= strtoupper($venta['metodo_pago']) ?>
            </span>
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<style>
.ventas-container {
    display:flex;
    flex-direction:column;
    gap:15px;
    padding:10px;
}
.venta-card {
    background:#fff;
    border-radius:10px;
    padding:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    display:flex;
    flex-direction:column;
    gap:10px;
}
.venta-header, .venta-footer {
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    font-size:0.95em;
    color:#333;
}
.venta-productos {
    display:flex;
    flex-direction:column;
    gap:4px;
    padding:5px 0;
    border-top:1px solid #eee;
    border-bottom:1px solid #eee;
}
.producto-item {
    font-size:0.9em;
    color:#555;
}
.badge {
    padding:4px 8px;
    border-radius:5px;
    color:#fff;
    font-size:0.8em;
}
.badge-efectivo { background:#27ae60; }
.badge-transferencia { background:#2980b9; }
.badge-credito { background:#f39c12; }

.venta-total {
    margin-top:8px;
    font-weight:bold;
    text-align:right;
    color:#16a085;
}

@media(max-width:768px){
    .venta-header, .venta-footer {
        flex-direction:column;
        gap:5px;
    }
}
@media(max-width:480px){
    .venta-card {
        padding:10px;
    }
}
</style>

<script>
function initModulo(){
    console.log("Módulo Ventas cargado");
}
</script>
