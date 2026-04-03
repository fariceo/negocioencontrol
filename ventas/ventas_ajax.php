<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode([
        'html' => '<p>No autorizado</p>',
        'total_general' => 0,
        'rango_texto' => 'No autorizado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

date_default_timezone_set('America/Guayaquil');

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);

$filtro = $_GET['filtro'] ?? 'todos';
$fecha = $_GET['fecha'] ?? '';
$fecha_ini = $_GET['fecha_ini'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$rango_texto = 'Todos';

$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fin_semana = date('Y-m-d', strtotime('sunday this week'));
$inicio_mes = date('Y-m-01');
$fin_mes = date('Y-m-t');

switch ($filtro) {
    case 'hoy':
        $fecha_ini = $hoy;
        $fecha_fin = $hoy;
        $rango_texto = 'Hoy';
        break;

    case 'ayer':
        $fecha_ini = $ayer;
        $fecha_fin = $ayer;
        $rango_texto = 'Ayer';
        break;

    case 'semana':
        $fecha_ini = $inicio_semana;
        $fecha_fin = $fin_semana;
        $rango_texto = 'Esta semana';
        break;

    case 'mes':
        $fecha_ini = $inicio_mes;
        $fecha_fin = $fin_mes;
        $rango_texto = 'Este mes';
        break;

    case 'dia':
        if (!empty($fecha)) {
            $fecha_ini = $fecha;
            $fecha_fin = $fecha;
            $rango_texto = date('d/m/Y', strtotime($fecha));
        }
        break;

    case 'personalizado':
        if (!empty($fecha_ini) && !empty($fecha_fin)) {
            $rango_texto = date('d/m/Y', strtotime($fecha_ini)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));
        } else {
            $rango_texto = 'Rango personalizado';
        }
        break;

    case 'todos':
    default:
        $fecha_ini = '';
        $fecha_fin = '';
        $rango_texto = 'Todos';
        break;
}

$where = " WHERE negocio='$negocio' ";

if (!empty($fecha_ini) && !empty($fecha_fin)) {
    $where .= " AND fecha_hora BETWEEN '$fecha_ini 00:00:00' AND '$fecha_fin 23:59:59' ";
}

$sql = "SELECT * FROM ventas $where ORDER BY fecha_hora DESC";
$result = $conexion->query($sql);

$ventas = [];
$total_general = 0;
$html = "";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
        $total_general += floatval($row['total']);
    }
}

if (empty($ventas)) {
    $html = '<div class="sin-resultados">No hay ventas registradas en este lapso.</div>';
} else {
    foreach ($ventas as $venta) {
        $productos = json_decode($venta['productos'], true);
        $totalVenta = floatval($venta['total']);
        $metodo = strtolower(trim($venta['metodo_pago'] ?? 'otro'));

        if (!in_array($metodo, ['efectivo', 'transferencia', 'credito'])) {
            $metodo = 'otro';
        }

        $html .= '
        <div class="venta-card">
            <div class="venta-header">
                <span><strong>Cliente / Código:</strong> '.htmlspecialchars($venta['cliente'] ?? '').'</span>
                <span><strong>Vendedor:</strong> '.htmlspecialchars($venta['vendedor'] ?? '').'</span>
            </div>

            <div class="venta-header">
                <span><strong>Fecha:</strong> 
                    <span class="fecha-venta" 
                          id="fecha-venta-'.$venta['id'].'"
                          data-id="'.$venta['id'].'"
                          data-hora="'.date('H:i:s', strtotime($venta['fecha_hora'])).'">
                        '.htmlspecialchars($venta['fecha_hora']).'
                    </span>
                </span>
            </div>

            <div class="venta-productos">';
        
        if (is_array($productos)) {
            foreach ($productos as $p) {
                $precio = isset($p['precio']) ? (float)$p['precio'] : 0;
                $cantidad = isset($p['cantidad']) ? (int)$p['cantidad'] : 0;
                $nombreProducto = $p['producto'] ?? 'Producto';
                $subtotal = $precio * $cantidad;

                $html .= '<div class="producto-item">'
                    .$cantidad.' x '.htmlspecialchars($nombreProducto)
                    .' x $'.number_format($precio,2)
                    .' = <strong>$'.number_format($subtotal,2).'</strong></div>';
            }
        } else {
            $html .= '<div class="producto-item">No se pudieron leer los productos</div>';
        }

        $html .= '
                <div class="venta-total">TOTAL: $'.number_format($totalVenta,2).'</div>
            </div>

            <div class="venta-footer">';
        
        if ($metodo === 'credito') {
            $html .= '<a href="/negocioencontrol/negocios/modulos/credito/ficha_credito.php?usuario='.urlencode($venta['cliente'] ?? '').'" class="badge badge-credito" style="text-decoration:none;">CRÉDITO</a>';
        } else {
            $html .= '<span class="badge badge-'.$metodo.'">'.strtoupper($metodo).'</span>';
        }

        $html .= '</div></div>';
    }
}

echo json_encode([
    'html' => $html,
    'total_general' => $total_general,
    'rango_texto' => $rango_texto
], JSON_UNESCAPED_UNICODE);