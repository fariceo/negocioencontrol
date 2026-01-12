<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio']) || !isset($_SESSION['usuario'])) {
    echo json_encode(['ok'=>false,'mensaje'=>'Sesión expirada o usuario no definido']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'];
$negocio = $_SESSION['nombre_bd_negocio'];

$accion = $_GET['accion'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch($accion){
    case 'cargar':
        $res = $conexion->query("
            SELECT c.id, p.producto, p.precio, c.cantidad 
            FROM carrito c 
            JOIN productos p ON c.id_producto = p.id_producto 
            WHERE c.usuario='{$usuario}' AND c.estado='activo'
        ");
        $carrito = [];
        while($row = $res->fetch_assoc()) $carrito[] = $row;
        echo json_encode($carrito);
        break;

    case 'agregar':
        $id_producto = intval($input['id_producto']);
        $cantidad = intval($input['cantidad']);
        if($cantidad < 1){
            echo json_encode(['ok'=>false,'mensaje'=>'Cantidad inválida']);
            exit;
        }

        // Revisar si ya existe en carrito
        $res = $conexion->query("SELECT id, cantidad FROM carrito WHERE usuario='{$usuario}' AND id_producto={$id_producto} AND estado='activo'");
        if($res->num_rows > 0){
            $row = $res->fetch_assoc();
            $nueva_cantidad = $row['cantidad'] + $cantidad;
            $stmt = $conexion->prepare("UPDATE carrito SET cantidad=? WHERE id=? AND usuario=?");
            $stmt->bind_param("iis",$nueva_cantidad,$row['id'],$usuario);
            $stmt->execute();
            $stmt->close();
        } else {
            $estado = 'activo';
            $stmt = $conexion->prepare("INSERT INTO carrito (negocio, usuario, id_producto, cantidad, estado, fecha) VALUES (?,?,?,?,?,NOW())");
            $stmt->bind_param("sssis",$negocio,$usuario,$id_producto,$cantidad,$estado);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['ok'=>true]);
        break;

    case 'modificar':
        $id = intval($input['id']);
        $cantidad = intval($input['cantidad']);
        if($cantidad < 1){
            echo json_encode(['ok'=>false,'mensaje'=>'Cantidad inválida']);
            exit;
        }
        $stmt = $conexion->prepare("UPDATE carrito SET cantidad=? WHERE id=? AND usuario=? AND estado='activo'");
        $stmt->bind_param("iis",$cantidad,$id,$usuario);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok'=>true]);
        break;

    case 'eliminar':
        $id = intval($input['id']);
        $stmt = $conexion->prepare("DELETE FROM carrito WHERE id=? AND usuario=? AND estado='activo'");
        $stmt->bind_param("is",$id,$usuario);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok'=>true]);
        break;

    default:
        echo json_encode(['ok'=>false,'mensaje'=>'Acción no válida']);



        case 'confirmar_credito':

    $cliente = trim($input['cliente'] ?? '');
    $cedula  = trim($input['cedula'] ?? '');
    $banco   = trim($input['banco'] ?? '');

    if(!$cliente || !$cedula || !$banco){
        echo json_encode(['ok'=>false,'mensaje'=>'Datos de crédito incompletos']);
        exit;
    }

    // 1️⃣ Obtener carrito activo
    $res = $conexion->query("
        SELECT p.producto, p.precio, c.cantidad
        FROM carrito c
        JOIN productos p ON c.id_producto = p.id_producto
        WHERE c.usuario='{$usuario}' AND c.estado='activo'
    ");

    if($res->num_rows === 0){
        echo json_encode(['ok'=>false,'mensaje'=>'Carrito vacío']);
        exit;
    }

    $total = 0;
    $detalle = [];

    while($row = $res->fetch_assoc()){
        $subtotal = $row['precio'] * $row['cantidad'];
        $total += $subtotal;
        $detalle[] = $row['producto']." x".$row['cantidad'];
    }

    // 2️⃣ Ver si el cliente ya tiene saldo pendiente
    $stmt = $conexion->prepare("
        SELECT saldo_pendiente 
        FROM saldo_pendiente 
        WHERE usuario=?
    ");
    $stmt->bind_param("s",$cliente);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        // Cliente existente → sumar deuda
        $row = $result->fetch_assoc();
        $nuevoSaldo = $row['saldo_pendiente'] + $total;

        $stmt = $conexion->prepare("
            UPDATE saldo_pendiente
            SET saldo_pendiente=?, banco=?, accion='venta credito',
                fecha=CURDATE(), hora=CURTIME()
            WHERE usuario=?
        ");
        $stmt->bind_param("dss",$nuevoSaldo,$banco,$cliente);
        $stmt->execute();

    } else {
        // Cliente nuevo → crear cuenta
        $nuevoSaldo = $total;

        $stmt = $conexion->prepare("
            INSERT INTO saldo_pendiente
            (usuario, cedula, banco, saldo_pendiente, accion, fecha, hora)
            VALUES (?,?,?,?, 'venta credito', CURDATE(), CURTIME())
        ");
        $stmt->bind_param("sssd",$cliente,$cedula,$banco,$nuevoSaldo);
        $stmt->execute();
    }

    // 3️⃣ Insertar historial de crédito
    $concepto = "Compra a crédito: ".implode(', ',$detalle);

    $stmt = $conexion->prepare("
        INSERT INTO historial_credito
        (usuario, saldo, saldo_contable, concepto, fecha)
        VALUES (?,?,?,?,CURDATE())
    ");
    $stmt->bind_param("sdds",$cliente,$total,$nuevoSaldo,$concepto);
    $stmt->execute();

    // 4️⃣ Cerrar carrito
    $conexion->query("
        UPDATE carrito
        SET estado='cerrado'
        WHERE usuario='{$usuario}' AND estado='activo'
    ");

    echo json_encode([
        'ok'=>true,
        'mensaje'=>'Venta a crédito registrada',
        'saldo_actual'=>$nuevoSaldo
    ]);
    break;

}
