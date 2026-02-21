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

/* =========================
   BUSCAR
   ========================= */
if (isset($_GET['buscar'])) {

    $buscar = $conexion->real_escape_string($_GET['buscar'])."%";

    $stmt = $conexion->prepare("
        SELECT 
            b.id,
            b.descripcion,
            b.cantidad,
            b.precio,
            p.producto,
            p.codigo_barra,
            COALESCE(p.imagen,'') AS img,
            p.id_producto
        FROM bodega b
        INNER JOIN productos p ON p.id_producto = b.id_producto
        WHERE b.negocio=? 
        AND (
            p.producto LIKE ?
            OR p.codigo_barra = ?
        )
        ORDER BY b.id DESC
        LIMIT 100
    ");

    $stmt->bind_param("sss", $negocio, $buscar, $_GET['buscar']);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['ok'=>true,'data'=>$data]);
    exit;
}

/* =========================
   ELIMINAR
   ========================= */
if (!empty($_POST['eliminar'])) {

    $id = intval($_POST['id']);

    $stmt = $conexion->prepare("
        SELECT p.imagen, b.id_producto
        FROM bodega b
        INNER JOIN productos p ON p.id_producto = b.id_producto
        WHERE b.id=? AND b.negocio=?
    ");
    $stmt->bind_param("is", $id, $negocio);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if ($data && !empty($data['imagen'])) {
        $urlLimpia = strtok($data['imagen'], '?');
        $partes = parse_url($urlLimpia);
        $path = ltrim($partes['path'], '/');
        $objectPath = preg_replace('#^'.$bucketName.'/#', '', $path);
        $object = $bucket->object($objectPath);
        if ($object->exists()) {
            $object->delete();
        }
    }

    $stmt = $conexion->prepare("DELETE FROM bodega WHERE id=? AND negocio=?");
    $stmt->bind_param("is", $id, $negocio);
    $stmt->execute();
    $stmt->close();

    if ($data) {
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto=?");
        $stmt->bind_param("i", $data['id_producto']);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['ok'=>true,'msg'=>'Producto e imagen eliminados']);
    exit;
}

/* =========================
   GUARDAR / EDITAR
   ========================= */

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

    /* ========= IMAGEN ========= */
    if (isset($_FILES['imagen']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {

        $fileName = "prod_$id_producto.jpg";
        $objectPath = "$negocio/productos/$fileName";

        $object = $bucket->object($objectPath);
        if ($object->exists()) {
            $object->delete();
        }

        $bucket->upload(
            fopen($_FILES['imagen']['tmp_name'], 'r'),
            [
                'name' => $objectPath,
                'metadata' => [
                    'cacheControl' => 'no-store, no-cache, must-revalidate, max-age=0'
                ]
            ]
        );

        $imgUrl = "https://storage.googleapis.com/$bucketName/$objectPath?v=" . time();

        $stmt = $conexion->prepare("UPDATE productos SET imagen=? WHERE id_producto=?");
        $stmt->bind_param("si", $imgUrl, $id_producto);
        $stmt->execute();
        $stmt->close();
    }

    if ($id > 0) {

        $stmt = $conexion->prepare("
            UPDATE bodega SET
            descripcion=?, cantidad=?, precio=?, categoria=?, stock_inicial=?
            WHERE id=? AND negocio=?
        ");

        $stmt->bind_param("sdddsis",
            $descripcion,
            $cantidad,
            $precio,
            $categoria,
            $stockInicial,
            $id,
            $negocio
        );

    } else {

        $stmt = $conexion->prepare("
            INSERT INTO bodega
            (negocio, id_producto, descripcion, cantidad, precio, categoria, stock_inicial, fecha_registro)
            VALUES (?,?,?,?,?,?,?,NOW())
        ");

        $stmt->bind_param("sisddds",
            $negocio,
            $id_producto,
            $descripcion,
            $cantidad,
            $precio,
            $categoria,
            $stockInicial
        );
    }

    $stmt->execute();
    $stmt->close();
    $conexion->commit();

    $stmt = $conexion->prepare("SELECT imagen FROM productos WHERE id_producto=?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $resImg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'ok'=>true,
        'msg'=>'Inventario guardado',
        'img'=>$resImg['imagen'] ?? ''
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
}
exit;