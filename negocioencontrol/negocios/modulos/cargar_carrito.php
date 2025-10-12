<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['carrito_inicializado'])) {
    $_SESSION['carrito_inicializado'] = true;
}

if (!isset($_SESSION['nombre_bd_negocio'])) { echo json_encode(['ok'=>false,'mensaje'=>'Acceso no autorizado']); exit; }

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'] ?? 'default_user';

$accion = $_GET['accion'] ?? 'cargar';
$data = json_decode(file_get_contents('php://input'), true);

switch($accion){
    case 'agregar':
        $producto = $data['producto'] ?? '';
        $precio = floatval($data['precio'] ?? 0);
        $cantidad = intval($data['cantidad'] ?? 1);
        if(!$producto || $cantidad < 1) { echo json_encode(['ok'=>false,'mensaje'=>'Datos inválidos']); exit; }

        $negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);
        $productoEsc = $conexion->real_escape_string($producto);

        $check = $conexion->query("SELECT id, cantidad FROM carrito WHERE negocio='$negocio' AND usuario='$usuario' AND producto='$productoEsc' AND estado='pendiente'");
        if($check && $check->num_rows>0){
            $row = $check->fetch_assoc();
            $nuevaCantidad = $row['cantidad'] + $cantidad;
            $conexion->query("UPDATE carrito SET cantidad=$nuevaCantidad WHERE id=".$row['id']);
        } else {
            $stmt = $conexion->prepare("INSERT INTO carrito (negocio, usuario, producto, precio, cantidad, estado, fecha) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())");
            $stmt->bind_param("sssdi",$negocio,$usuario,$producto,$precio,$cantidad);
            $stmt->execute();
        }
        echo json_encode(['ok'=>true]);
        break;

    case 'modificar':
        $id = intval($data['id'] ?? 0);
        $cantidad = intval($data['cantidad'] ?? 1);
        if(!$id || $cantidad < 1) { echo json_encode(['ok'=>false]); exit; }
        $conexion->query("UPDATE carrito SET cantidad=$cantidad WHERE id=$id AND estado='pendiente'");
        echo json_encode(['ok'=>true]);
        break;

    case 'eliminar':
        $id = intval($data['id'] ?? 0);
        if(!$id) { echo json_encode(['ok'=>false]); exit; }
        $conexion->query("UPDATE carrito SET estado=2 WHERE id=$id AND estado='pendiente'");
        echo json_encode(['ok'=>true]);
        break;

    case 'cargar':
    default:
        $sql = "SELECT id, producto, precio, cantidad 
                FROM carrito 
                WHERE negocio='{$conexion->real_escape_string($_SESSION['nombre_bd_negocio'])}'
                  AND usuario='{$conexion->real_escape_string($usuario)}'
                  AND estado='pendiente'
                ORDER BY id ASC";
        $result = $conexion->query($sql);
        $carrito = [];
        if($result){
            while($row = $result->fetch_assoc()){
                $carrito[] = [
                    'id' => $row['id'],
                    'producto' => $row['producto'],
                    'precio' => floatval($row['precio']),
                    'cantidad' => intval($row['cantidad'])
                ];
            }
        }
        echo json_encode($carrito);
        break;
}
