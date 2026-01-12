<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sesión no válida']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// Función para subir imagen
function subirImagen($archivo){
    $targetDir = $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/negocios/modulos/assets/";
    if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid("img_").".".$ext;
    $destino = $targetDir.$nombreArchivo;

    if(move_uploaded_file($archivo['tmp_name'], $destino)){
        return "/negocioencontrol/negocios/modulos/assets/".$nombreArchivo;
    }
    return '';
}

// ELIMINAR
if(isset($_POST['eliminar']) && $_POST['eliminar']==1 && isset($_POST['id'])){
    $id = (int)$_POST['id'];
    $res = $conexion->query("DELETE FROM bodega WHERE id=$id");
    if($res) echo json_encode(['ok'=>true,'msg'=>'Producto eliminado']);
    else echo json_encode(['ok'=>false,'msg'=>'Error al eliminar']);
    exit;
}

// INSERTAR / ACTUALIZAR
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$producto = $conexion->real_escape_string($_POST['producto'] ?? '');
$descripcion = $conexion->real_escape_string($_POST['descripcion'] ?? '');
$cantidad = (float)($_POST['cantidad'] ?? 0);
$precio = (float)($_POST['precio'] ?? 0);
$categoria = $conexion->real_escape_string($_POST['categoria'] ?? '');
$stock_inicial = (float)($_POST['stock_inicial'] ?? 0);

// Imagen
$imagen = '';
if(isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name']!=''){
    $imagen = subirImagen($_FILES['imagen']);
}

// Si es nuevo
if($id==0){
    $sql = "INSERT INTO bodega (producto, descripcion, cantidad, precio, categoria, stock_inicial, negocio)
            VALUES ('$producto','$descripcion',$cantidad,$precio,'$categoria',$stock_inicial,'".$_SESSION['nombre_bd_negocio']."')";
    $res = $conexion->query($sql);
    if($res){
        $lastId = $conexion->insert_id;
        if($imagen!=''){
            $conexion->query("UPDATE bodega SET imagen='$imagen' WHERE id=$lastId");
        }
        echo json_encode(['ok'=>true,'msg'=>'Producto agregado']);
    } else echo json_encode(['ok'=>false,'msg'=>'Error al agregar']);
    exit;
}

// Si es actualización
$sql = "UPDATE bodega SET
        producto='$producto',
        descripcion='$descripcion',
        cantidad=$cantidad,
        precio=$precio,
        categoria='$categoria',
        stock_inicial=$stock_inicial
        ".($imagen!='' ? ", imagen='$imagen'" : "")."
        WHERE id=$id";
$res = $conexion->query($sql);

if($res) echo json_encode(['ok'=>true,'msg'=>'Producto actualizado']);
else echo json_encode(['ok'=>false,'msg'=>'Error al actualizar']);
?>
