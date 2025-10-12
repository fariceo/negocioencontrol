<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
require '/var/www/elpollovolantuso/vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

header('Content-Type: application/json');
$response = ['ok'=>false,'msg'=>'Error desconocido'];

try {
    if (!isset($_SESSION['nombre_bd_negocio'])) throw new Exception('Sesión no válida');

    $db = new Conexion();
    $conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
    $negocio = $_SESSION['nombre_bd_negocio'];

    $bucketName = 'negocioencontrol';
    $storage = new StorageClient([
        'keyFilePath' => '/var/www/elpollovolantuso/negocioencontrol/negocios/modulos/gcs-key.json'
    ]);
    $bucket = $storage->bucket($bucketName);

    $input = $_POST;

    // ===================== BORRAR PRODUCTO =====================
if (!empty($input['eliminar']) && !empty($input['id'])) {
    $id = intval($input['id']);
    $prod = $conexion->query("SELECT producto FROM bodega WHERE id=$id AND negocio='$negocio'")->fetch_assoc();

    if ($prod) {
        $nombreProducto = $prod['producto'];

        // Buscar imagen asociada en la tabla productos
        $imgRow = $conexion->query("SELECT imagen FROM productos WHERE producto='$nombreProducto' LIMIT 1")->fetch_assoc();
        if (!empty($imgRow['imagen'])) {
            $urlPath = parse_url($imgRow['imagen'], PHP_URL_PATH); // ej: /byrito/productos/pollo.jpg
            $objectName = ltrim($urlPath, '/'); // elimina la barra inicial

            try {
                $object = $bucket->object($objectName);
                if ($object->exists()) {
                    $object->delete();
                    error_log("✅ Imagen eliminada correctamente de GCS: $objectName");
                } else {
                    error_log("⚠️ Imagen no encontrada en GCS: $objectName");
                }
            } catch (Exception $e) {
                error_log("❌ Error al eliminar imagen GCS ($objectName): ".$e->getMessage());
            }
        }

        // Eliminar registros en MySQL
        $conexion->query("DELETE FROM bodega WHERE id=$id AND negocio='$negocio'");
        $conexion->query("DELETE FROM productos WHERE producto='$nombreProducto'");
    }

    echo json_encode(['ok' => true, 'msg' => 'Producto eliminado correctamente']);
    exit;
}


    // ===================== AGREGAR / EDITAR PRODUCTO =====================
    $id = intval($input['id'] ?? 0);
    $nombre = $conexion->real_escape_string($input['producto'] ?? '');
    $descripcion = $conexion->real_escape_string($input['descripcion'] ?? '');
    $cantidad = floatval($input['cantidad'] ?? 0);
    $precio = floatval($input['precio'] ?? 0);

    $imagen_url = null;

    // Subida de imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
        $fileTmp = $_FILES['imagen']['tmp_name'];
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $fileName = $nombre . '.' . $ext; // usar nombre del producto como nombre de archivo

        // Si hay edición, eliminar imagen anterior
        if($id > 0){
            $old = $conexion->query("SELECT producto FROM bodega WHERE id=$id")->fetch_assoc();
            if($old){
                $imgRow = $conexion->query("SELECT imagen FROM productos WHERE producto='".$old['producto']."'")->fetch_assoc();
                if(!empty($imgRow['imagen'])){
                    $urlPath = parse_url($imgRow['imagen'], PHP_URL_PATH);
                    $oldObject = $bucket->object(ltrim($urlPath,'/'));
                    if($oldObject->exists()) $oldObject->delete();
                }
            }
        } else {
            // Si ya existe producto en productos, eliminar imagen existente
            $imgRow = $conexion->query("SELECT imagen FROM productos WHERE producto='$nombre'")->fetch_assoc();
            if(!empty($imgRow['imagen'])){
                $urlPath = parse_url($imgRow['imagen'], PHP_URL_PATH);
                $oldObject = $bucket->object(ltrim($urlPath,'/'));
                if($oldObject->exists()) $oldObject->delete();
            }
        }

        $object = $bucket->upload(fopen($fileTmp,'r'), [
            'name'=> "$negocio/productos/$fileName"
        ]);
        $imagen_url = "https://storage.googleapis.com/$bucketName/$negocio/productos/$fileName";
    }

    // ===================== INSERCIÓN / ACTUALIZACIÓN =====================
    if($id > 0){
        $sql = "UPDATE bodega 
                SET producto='$nombre', descripcion='$descripcion', cantidad=$cantidad, precio=$precio 
                WHERE id=$id AND negocio='$negocio'";
        if(!$conexion->query($sql)) throw new Exception("Error actualizando bodega: ".$conexion->error);
        $msg = "Producto actualizado en bodega";
    } else {
        $sql = "INSERT INTO bodega (negocio, producto, descripcion, cantidad, precio, fecha_registro) 
                VALUES ('$negocio','$nombre','$descripcion',$cantidad,$precio,NOW())";
        if(!$conexion->query($sql)) throw new Exception("Error insertando en bodega: ".$conexion->error);
        $msg = "Producto agregado a bodega";
    }

    // ===================== ACTUALIZAR / INSERTAR EN PRODUCTOS =====================
    $res = $conexion->query("SELECT id_producto FROM productos WHERE producto='$nombre' LIMIT 1");
    if($res && $res->num_rows>0){
        $row = $res->fetch_assoc();
        $id_producto = $row['id_producto'];
        $sqlProd = "UPDATE productos SET precio=$precio".($imagen_url?", imagen='$imagen_url'":"")." WHERE id_producto=$id_producto";
        if(!$conexion->query($sqlProd)) throw new Exception("Error actualizando producto: ".$conexion->error);
    } else {
        $sqlProd = "INSERT INTO productos (producto, precio, imagen) 
                    VALUES ('$nombre',$precio,".($imagen_url?"'$imagen_url'":"'/negocioencontrol/negocios/modulos/assets/default.png'").")";
        if(!$conexion->query($sqlProd)) throw new Exception("Error insertando producto: ".$conexion->error);
    }

    $response = ['ok'=>true,'msg'=>$msg];

} catch(Exception $e){
    $response = ['ok'=>false,'msg'=>$e->getMessage()];
}

echo json_encode($response);
exit();
?>
