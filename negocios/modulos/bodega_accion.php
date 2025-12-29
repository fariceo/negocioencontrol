<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
require '/var/www/elpollovolantuso/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

header('Content-Type: application/json');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sesión no válida']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];

$bucketName = 'negocioencontrol';
$storage = new StorageClient([
    'keyFilePath' => '/var/www/elpollovolantuso/negocioencontrol/negocios/modulos/gcs-key.json'
]);
$bucket = $storage->bucket($bucketName);

/* ================== ELIMINAR ================== */
if (!empty($_POST['eliminar'])) {
    $id = intval($_POST['id']);
    $stmt = $conexion->prepare("DELETE FROM bodega WHERE id=? AND negocio=?");
    $stmt->bind_param("is", $id, $negocio);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok'=>true,'msg'=>'Producto eliminado']);
    exit;
}

/* ================== GUARDAR ================== */
$id           = intval($_POST['id'] ?? 0);
$id_producto  = intval($_POST['id_producto'] ?? 0);
$producto     = trim($_POST['producto'] ?? '');
$codigo_barra = trim($_POST['codigo_barra'] ?? '');
$descripcion  = $_POST['descripcion'] ?? '';
$cantidad     = floatval($_POST['cantidad']);
$precio       = floatval($_POST['precio']);
$categoria    = $_POST['categoria'] ?? '';
$stockInicial = floatval($_POST['stock_inicial']);

$conexion->begin_transaction();

try {
    // 1️⃣ Crear producto nuevo si id_producto=0
    if ($id_producto == 0 && $producto !== '') {
        if (empty($codigo_barra)) {
            $codigo_barra = (string)time();
        }

        $stmt = $conexion->prepare("
            INSERT INTO productos (codigo_barra, producto, precio, categoria, stock_inicial)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssdi", $codigo_barra, $producto, $precio, $categoria, $stockInicial);
        $stmt->execute();
        $id_producto = $stmt->insert_id;
        $stmt->close();
    }

    // 2️⃣ Editar producto existente (actualiza todo)
    if ($id_producto > 0) {
        $stmt = $conexion->prepare("
            UPDATE productos SET
            codigo_barra=?, producto=?, precio=?, categoria=?, stock_inicial=?
            WHERE id_producto=?
        ");
        $stmt->bind_param("ssdssi", $codigo_barra, $producto, $precio, $categoria, $stockInicial, $id_producto);
        $stmt->execute();
        $stmt->close();
    }

  // 3️⃣ Subir imagen SOLO si realmente hay archivo válido
if (
    isset($_FILES['imagen']) &&
    is_uploaded_file($_FILES['imagen']['tmp_name'])
) {
    $fileName = "prod_$id_producto.jpg";

    $bucket->upload(
        fopen($_FILES['imagen']['tmp_name'], 'r'),
        ['name' => "$negocio/productos/$fileName"]
    );

    $imgUrl = "https://storage.googleapis.com/$bucketName/$negocio/productos/$fileName?v=" . time();
    $conexion->query("
        UPDATE productos 
        SET imagen='$imgUrl' 
        WHERE id_producto=$id_producto
    ");
}


    // 4️⃣ Insertar o actualizar bodega
    if ($id > 0) {
        $stmt = $conexion->prepare("
            UPDATE bodega SET
            descripcion=?, cantidad=?, precio=?, categoria=?, stock_inicial=?
            WHERE id=? AND negocio=?
        ");
        $stmt->bind_param("sdddis",$descripcion,$cantidad,$precio,$categoria,$stockInicial,$id,$negocio);
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO bodega
            (negocio, id_producto, descripcion, cantidad, precio, categoria, stock_inicial, fecha_registro)
            VALUES (?,?,?,?,?,?,?,NOW())
        ");
        $stmt->bind_param("sisddds",$negocio,$id_producto,$descripcion,$cantidad,$precio,$categoria,$stockInicial);
    }

    $stmt->execute();
    $stmt->close();
    $conexion->commit();

    echo json_encode(['ok'=>true,'msg'=>'Inventario guardado']);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
}
exit;