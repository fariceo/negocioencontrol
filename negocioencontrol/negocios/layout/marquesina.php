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

// Ventas: solo columna total
$sqlVentas = "SELECT SUM(total) AS totalVentas 
              FROM ventas 
              WHERE fecha_hora BETWEEN '$lunes 00:00:00' AND '$domingo 23:59:59'";
$resVentas = $conexion->query($sqlVentas);
$ventas = $resVentas ? (float)($resVentas->fetch_assoc()['totalVentas'] ?? 0) : 0;

// Compras: solo columna total
$sqlCompras = "SELECT IFNULL(SUM(total),0) AS totalCompras 
               FROM gastos 
               WHERE fecha >= '$lunes 00:00:00' 
                 AND fecha <= '$domingo 23:59:59'";
$resCompras = $conexion->query($sqlCompras);
$compras = $resCompras ? (float)$resCompras->fetch_assoc()['totalCompras'] : 0;


// Balance
$balance = $ventas - $compras;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <style>
    body {
      margin:0;
      font-family: Arial, sans-serif;
      background:#f4f6f9;
    }

    /* Barra contenedora */
    .info-bar {
      position: relative;
      width: 100%;
      background: #111;
      color: white;
      overflow: hidden;
      display: flex;
      align-items: center;
      height: 50px;
    }

    /* Texto deslizante */
    .marquee {
      display: inline-block;
      position: absolute;              /* posición absoluta dentro de la barra */
      left: 100%; 
      white-space: nowrap;
      animation: scroll 20s linear infinite;
      gap: 40px;
      font-size: 1.1em;
      font-weight: bold;
    }

    .ventas { color: #2ecc71; }   /* Verde */
    .compras { color: #e74c3c; }  /* Rojo */
    .balance { color: #f1c40f; }  /* Amarillo */

    @keyframes scroll {
      0% { transform: translateX(100%); }
      100% { transform: translateX(-100%); }
    }

    /* Banner lateral */
    .banner {
      position: absolute;
      right: 20px;
      background: #f39c12;
      color: #111;
      padding: 5px 12px;
      border-radius: 6px;
      font-weight: bold;
      white-space: nowrap;
      animation: blink 1.5s infinite alternate;
    }

    @keyframes blink {
      from { opacity: 1; }
      to { opacity: 0.5; }
    }

    /* Contenido principal */
    .contenido {
      padding: 20px;
    }
  </style>
</head>
<body>
  
  <!-- Barra de información -->
  <div class="info-bar">
    <div class="marquee">
      <span class="ventas">Ventas: $<?= number_format($ventas, 2) ?></span>
      <span class="compras">Compras: $<?= number_format($compras, 2) ?></span>
      <span class="balance">Balance: $<?= number_format($balance, 2) ?></span>
    </div>

    <!-- Banner lateral opcional -->
    <!--
    <div class="banner">
      🔥 Promoción Especial 🔥
    </div>
    -->
  </div>
<!--
  <div class="contenido">
    <h1>Bienvenido al sistema</h1>
    <p>Tu información semanal se muestra en la parte superior</p>
  </div>-->
</body>
</html>
