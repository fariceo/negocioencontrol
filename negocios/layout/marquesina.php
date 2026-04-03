<?php

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("Acceso no autorizado");
}

// Conexión a la BD del negocio
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// Fechas de esta semana (lunes a domingo)
$hoy = date("Y-m-d");
$lunes = date("Y-m-d", strtotime("monday this week", strtotime($hoy)));
$domingo = date("Y-m-d", strtotime("sunday this week", strtotime($hoy)));

// Ventas
$sqlVentas = "SELECT SUM(total) AS totalVentas 
              FROM ventas 
              WHERE fecha_hora BETWEEN '$lunes 00:00:00' AND '$domingo 23:59:59'";
$resVentas = $conexion->query($sqlVentas);
$ventas = $resVentas ? (float)($resVentas->fetch_assoc()['totalVentas'] ?? 0) : 0;

// Compras
$sqlCompras = "SELECT IFNULL(SUM(total),0) AS totalCompras 
               FROM gastos 
               WHERE fecha >= '$lunes 00:00:00' 
                 AND fecha <= '$domingo 23:59:59'";
$resCompras = $conexion->query($sqlCompras);
$compras = $resCompras ? (float)$resCompras->fetch_assoc()['totalCompras'] : 0;

// Balance
$balance = $ventas - $compras;
?>

<style>
/* =========================
   MARQUESINA SUPERIOR
========================= */
.info-bar {
    position: relative;
    width: 100%;
    background: linear-gradient(135deg, #ff4fa3, #ff1493) !important;
    color: white;
    overflow: hidden;
    display: flex;
    align-items: center;
    height: 52px;
    box-shadow: 0 4px 12px rgba(255, 20, 147, 0.25);
    z-index: 1500;
}

/* TEXTO EN MOVIMIENTO */
.marquee {
    display: inline-block;
    position: absolute;
    left: 100%;
    white-space: nowrap;
    animation: scroll 20s linear infinite;
    font-size: 1rem;
    font-weight: bold;
}

/* COLORES DE TEXTO */
.ventas {
    color: #ffffff;
    margin-right: 40px;
}

.compras {
    color: #ffe4f1;
    margin-right: 40px;
}

.balance {
    color: #fff8dc;
}

/* ANIMACIÓN */
@keyframes scroll {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .info-bar {
        height: 48px;
        padding: 0 10px;
    }

    .marquee {
        font-size: 0.92rem;
    }
}
</style>

<div class="info-bar">
    <div class="marquee">
        <span class="ventas">💰 Ventas: $<?= number_format($ventas, 2) ?></span>
        <span class="compras">🛒 Compras: $<?= number_format($compras, 2) ?></span>
        <span class="balance">📊 Balance: $<?= number_format($balance, 2) ?></span>
    </div>
</div>