<?php
include("conexion_encontrol.php");

// Verificar conexión
if ($conexion_encontrol->connect_error) {
    die("Error de conexión: " . $conexion_encontrol->connect_error);
}

// Consulta: sumar ventas por día
$sql = "SELECT fecha, SUM(cantidad * precio) AS total 
        FROM ventas 
        GROUP BY fecha 
        ORDER BY fecha ASC";
$result = $conexion_encontrol->query($sql);

$fechas = [];
$totales = [];

while ($fila = $result->fetch_assoc()) {
    $fechas[] = $fila['fecha'];
    $totales[] = $fila['total'];
}

$conexion_encontrol->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Ventas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff; /* Fondo blanco */
            color: #2ecc71;   /* Texto verde */
            text-align: center;
            padding: 30px;
            min-height: 100vh;
            margin: 0;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #27ae60;
        }

        .card {
            background: #f9f9f9;
            border: 2px solid #2ecc71;
            border-radius: 20px;
            padding: 30px;
            margin: auto;
            max-width: 900px;
            box-shadow: 0px 8px 20px rgba(0,0,0,0.1);
        }

        .menu {
            text-align: center;
            background-color: #27ae60; /* Verde principal */
            padding: 10px 0;
            margin-bottom: 30px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 15px;
            background-color: #2ecc71;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .menu a:hover {
            background-color: #1e8449;
        }

        .menu i {
            font-size: 1.2rem;
        }

        canvas {
            max-width: 100%;
            height: 400px;
        }
    </style>
</head>
<body>
    <div class="menu">
        <a href="#" class="menu-toggle" aria-label="Abrir menú">
          <i class="fas fa-bars"></i>
        </a>
        <a href="negocioencontrol.php" class="producto-link">
          <i class="fas fa-store"></i>
        </a>
        <a href="carrito_encontrol.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="analisis_ventas.php"><i class="fas fa-chart-bar"></i></a>
        <a href="ajustes.php" class="ajustes-link">
          <i class="fas fa-cog"></i>
        </a>
    </div>

    <h1><i class="fas fa-chart-line"></i> Análisis de Ventas</h1>

    <div class="card">
        <canvas id="graficoVentas"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('graficoVentas').getContext('2d');
        const grafico = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($fechas); ?>,
                datasets: [{
                    label: 'Ventas por día ($)',
                    data: <?php echo json_encode($totales); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(46,204,113,0.2)',
                    borderWidth: 3,
                    tension: 0.3,
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#27ae60',
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    fill: true
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#27ae60',
                            font: { size: 14, weight: 'bold' }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#27ae60' },
                        grid: { color: 'rgba(39,174,96,0.1)' }
                    },
                    y: {
                        ticks: { color: '#27ae60' },
                        grid: { color: 'rgba(39,174,96,0.1)' }
                    }
                }
            }
        });
    </script>
</body>
</html>
