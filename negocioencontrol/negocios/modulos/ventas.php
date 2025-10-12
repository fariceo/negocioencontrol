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
                    <span><strong>Vendedor:</strong> <?= htmlspecialchars($venta['vendedor']) ?></span>
                </div>
                <div class="venta-productos">
                    <?php foreach($productos as $p): ?>
                        <div class="producto-item">
                            <?= $p['cantidad'] ?> x <?= htmlspecialchars($p['producto']) ?> - $<?= number_format($p['subtotal'],2) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="venta-footer">
                    <span class="badge <?= $venta['metodo_pago']=='efectivo'?'badge-efectivo':'badge-transferencia' ?>">
                        <?= ucfirst($venta['metodo_pago']) ?>
                    </span>
                    <span class="venta-total">Total: $<?= number_format($venta['total'],2) ?></span>
                    <span class="venta-fecha"><?= $venta['fecha_hora'] ?></span>
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
.badge-efectivo {background:#27ae60;}
.badge-transferencia {background:#2980b9;}
.venta-total {
    font-weight:bold;
    color:#16a085;
}
.venta-fecha {
    font-size:0.8em;
    color:#888;
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
