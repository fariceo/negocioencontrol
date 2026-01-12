<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
require '/var/www/elpollovolantuso/vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>");
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];
$defaultImage = "/negocioencontrol/negocios/modulos/assets/default.png";

// FUNCIONES

function comprimirImagen($source, $dest) {
    $info = getimagesize($source);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = imagecreatefromjpeg($source);
            return imagejpeg($img, $dest, 75);
        case 'image/png':
            $img = imagecreatefrompng($source);
            imagesavealpha($img, true);
            return imagepng($img, $dest, 8);
        default:
            return false;
    }
}

function procesarArchivoSubido($fileTmp, $fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mime = mime_content_type($fileTmp);

    // Convertir HEIC/HEIF → JPG
    if($mime === "image/heic" || $mime === "image/heif") {
        $img = imagecreatefromstring(file_get_contents($fileTmp));
        $nuevo = $fileTmp . "_conv.jpg";
        imagejpeg($img, $nuevo, 90);
        $fileTmp = $nuevo;
        $ext = "jpg";
        $fileName = pathinfo($fileName, PATHINFO_FILENAME) . ".jpg";
    }

    // Convertir WEBP → JPG
    if($mime === "image/webp") {
        $img = imagecreatefromwebp($fileTmp);
        $nuevo = $fileTmp . "_conv.jpg";
        imagejpeg($img, $nuevo, 90);
        $fileTmp = $nuevo;
        $ext = "jpg";
        $fileName = pathinfo($fileName, PATHINFO_FILENAME) . ".jpg";
    }

    $tmpCompressed = '/tmp/comprimida_' . uniqid() . '.' . $ext;
    if(!comprimirImagen($fileTmp, $tmpCompressed)) return false;

    return [$tmpCompressed, $fileName];
}

// INICIO DE LÓGICA PRINCIPAL
$response = ['ok'=>false,'msg'=>'Error desconocido'];

try {
    $input = $_POST;

    // Google Cloud Storage
    $bucketName = 'negocioencontrol';
    $storage = new StorageClient([
        'keyFilePath' => '/var/www/elpollovolantuso/negocioencontrol/negocios/modulos/gcs-key.json'
    ]);
    $bucket = $storage->bucket($bucketName);

    // ELIMINAR PRODUCTO
    if (!empty($input['eliminar']) && !empty($input['id'])) {
        $id = intval($input['id']);
        $prod = $conexion->query("SELECT producto FROM bodega WHERE id=$id AND negocio='$negocio'")->fetch_assoc();
        if($prod) {
            $nombreProducto = $prod['producto'];

            // Eliminar imagen en GCS si existe
            $imgRow = $conexion->query("SELECT imagen FROM productos WHERE producto='$nombreProducto' LIMIT 1")->fetch_assoc();
            if(!empty($imgRow['imagen'])) {
                $urlPath = parse_url($imgRow['imagen'], PHP_URL_PATH);
                $objectName = ltrim($urlPath, '/');
                $obj = $bucket->object($objectName);
                if($obj->exists()) $obj->delete();
            }

            $conexion->query("DELETE FROM bodega WHERE id=$id AND negocio='$negocio'");
            $conexion->query("DELETE FROM productos WHERE producto='$nombreProducto'");
        }

        echo json_encode(['ok'=>true,'msg'=>'Producto eliminado correctamente']);
        exit;
    }

    // AGREGAR / EDITAR PRODUCTO
    $id = intval($input['id'] ?? 0);
    $nombre = $conexion->real_escape_string($input['producto'] ?? '');
    $descripcion = $conexion->real_escape_string($input['descripcion'] ?? '');
    $cantidad = floatval($input['cantidad'] ?? 0);
    $precio = floatval($input['precio'] ?? 0);

    $imagen_url = null;

    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['imagen']['tmp_name'];
        $fileName = $_FILES['imagen']['name'];

        $res = procesarArchivoSubido($fileTmp, $fileName);
        if(!$res) throw new Exception("Formato de imagen no válido");

        [$tmpCompressed, $fileNameFinal] = $res;

        // Eliminar imagen anterior si existía
        $old = $bucket->object("$negocio/productos/$fileNameFinal");
        if($old->exists()) $old->delete();

        // Subir nueva imagen
        $bucket->upload(fopen($tmpCompressed, 'r'), ['name' => "$negocio/productos/$fileNameFinal"]);

        $imagen_url = "https://storage.googleapis.com/$bucketName/$negocio/productos/$fileNameFinal?v=".time();
        unlink($tmpCompressed);
    }

    // Insertar o actualizar bodega
    if($id > 0){
        $sql = "UPDATE bodega SET producto='$nombre', descripcion='$descripcion', cantidad=$cantidad, precio=$precio WHERE id=$id AND negocio='$negocio'";
        if(!$conexion->query($sql)) throw new Exception("Error actualizando bodega: ".$conexion->error);
        $msg = "Producto actualizado";
    } else {
        $sql = "INSERT INTO bodega (negocio, producto, descripcion, cantidad, precio, fecha_registro) VALUES ('$negocio','$nombre','$descripcion',$cantidad,$precio,NOW())";
        if(!$conexion->query($sql)) throw new Exception("Error insertando en bodega: ".$conexion->error);
        $msg = "Producto agregado";
    }

    // Insertar / actualizar tabla productos
    $res = $conexion->query("SELECT id_producto FROM productos WHERE producto='$nombre' LIMIT 1");
    if($res && $res->num_rows>0){
        $row = $res->fetch_assoc();
        $id_producto = $row['id_producto'];

        $sqlProd  = "UPDATE productos SET precio=$precio";
        if($imagen_url) $sqlProd .= ", imagen='$imagen_url'";
        $sqlProd .= " WHERE id_producto=$id_producto";
        if(!$conexion->query($sqlProd)) throw new Exception("Error actualizando producto: ".$conexion->error);
    } else {
        $imgFinal = $imagen_url ? "'$imagen_url'" : "'$defaultImage'";
        $sqlProd = "INSERT INTO productos (producto, precio, imagen) VALUES ('$nombre',$precio,$imgFinal)";
        if(!$conexion->query($sqlProd)) throw new Exception("Error insertando producto: ".$conexion->error);
    }

    $response = ['ok'=>true,'msg'=>$msg];

} catch(Exception $e){
    $response = ['ok'=>false,'msg'=>$e->getMessage()];
}

echo json_encode($response);
exit();
?>
