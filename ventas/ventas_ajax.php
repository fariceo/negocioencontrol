<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['html' => '<p>No autorizado</p>', 'total_general' => 0]);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);

$filtro = $_GET['filtro'] ?? 'todos';
$fecha_ini = $_GET['fecha_ini'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

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
        break;
    case 'ayer':
        $fecha_ini = $ayer;
        $fecha_fin = $ayer;
        break;
    case 'semana':
        $fecha_ini = $inicio_semana;
        $fecha_fin = $fin_semana;
        break;
    case 'mes':
        $fecha_ini = $inicio_mes;
        $fecha_fin = $fin_mes;
        break;
    case 'personalizado':
        break;
    default:
        $fecha_ini = '';
        $fecha_fin = '';
}

$where = " WHERE negocio='$negocio' ";
if (!empty($fecha_ini) && !empty($fecha_fin)) {
    $where .= " AND fecha_hora BETWEEN '$fecha_ini 00:00:00' AND '$fecha_fin 23:59:59' ";
}

$result = $conexion->query("SELECT * FROM ventas $where ORDER BY fecha_hora DESC");

$ventas = [];
$total_general = 0;
$html = "";

if($result){
    while($row = $result->fetch_assoc()){
        $ventas[] = $row;
        $total_general += floatval($row['total']);
    }
}

if(empty($ventas)){
    $html = '<p style="text-align:center; color:#888; margin-top:20px;">No hay ventas registradas</p>';
} else {
    foreach($ventas as $venta){
        $productos = json_decode($venta['productos'], true);
        $totalVenta = floatval($venta['total']);

        $html .= '
        <div class="venta-card">
            <div class="venta-header">
                <span><strong>Cliente / Código:</strong> '.htmlspecialchars($venta['cliente']).'</span>
                <span><strong>Vendedor:</strong> '.htmlspecialchars($venta['vendedor']).'</span>
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
        
        if(is_array($productos)){
            foreach($productos as $p){
                $precio = (float)$p['precio'];
                $cantidad = (int)$p['cantidad'];
                $subtotal = $precio * $cantidad;
                $html .= '<div class="producto-item">'.$cantidad.' x '.htmlspecialchars($p['producto']).' x $'.number_format($precio,2).' = <strong>$'.number_format($subtotal,2).'</strong></div>';
            }
        }

        $html .= '
                <div class="venta-total">TOTAL: $'.number_format($totalVenta,2).'</div>
            </div>

            <div class="venta-footer">';
        
        if(strtolower($venta['metodo_pago']) === 'credito'){
            $html .= '<a href="/negocioencontrol/negocios/modulos/credito/ficha_credito.php?usuario='.urlencode($venta['cliente']).'" class="badge badge-credito" style="text-decoration:none;">CREDITO</a>';
        } else {
            $html .= '<span class="badge badge-'.strtolower($venta['metodo_pago']).'">'.strtoupper($venta['metodo_pago']).'</span>';
        }

        $html .= '</div></div>';
    }
}

echo json_encode([
    'html' => $html,
    'total_general' => $total_general
]);
