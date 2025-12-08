<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
require '/var/www/elpollovolantuso/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

header('Content-Type: application/json');
$response = ['ok'=>false,'msg'=>'Error desconocido'];

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sesión no válida']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];

$defaultImage = "/negocioencontrol/negocios/modulos/assets/default.png";

/* ============================================================
   FUNCIONES PARA PROCESAR IMÁGENES (HEIC, WEBP, ORIENTACIÓN)
   ============================================================ */

function procesarArchivoSubido($fileTmp, $fileName) {

    $mime = mime_content_type($fileTmp);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    /* HEIC → JPG (iPhone) */
    if ($mime === "image/heic" || $mime === "image/heif" || $ext === "heic") {
        $imagick = new Imagick();
        $imagick->readImage($fileTmp);
        $imagick->setImageFormat('jpeg');
        $newPath = "/tmp/img_" . uniqid() . ".jpg";
        $imagick->writeImage($newPath);
        $fileTmp = $newPath;
        $ext = "jpg";
    }

    /* WEBP → JPG */
    if ($mime === "image/webp" || $ext === "webp") {
        $img = imagecreatefromwebp($fileTmp);
        $newPath = "/tmp/img_" . uniqid() . ".jpg";
        imagejpeg($img, $newPath, 90);
        $fileTmp = $newPath;
        $ext = "jpg";
    }

    /* Crear imagen GD */
    $img = @imagecreatefromstring(file_get_contents($fileTmp));
    if (!$img) return false;

    /* Corregir orientación EXIF */
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($fileTmp);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $img = imagerotate($img, 180, 0); break;
                case 6: $img = imagerotate($img, -90, 0); break;
                case 8: $img = imagerotate($img, 90, 0); break;
            }
        }
    }

    /* Redimensionar a máx 800px */
    $w = imagesx($img);
    $h = imagesy($img);
    $maxW = 800;

    if ($w > $maxW) {
        $newW = $maxW;
        $newH = intval($h * ($newW / $w));
        $tmp = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        $img = $tmp;
    }

    /* Guardar JPG final comprimido */
    $final = "/tmp/final_" . uniqid() . ".jpg";
    imagejpeg($img, $final, 80);

    return [$final, uniqid() . ".jpg"];
}

/* ============================================================
                      INICIO LÓGICA
   ============================================================ */

try {

    $input = $_POST;

    // Configuración Google Cloud Storage
    $bucketName = 'negocioencontrol';
    $storage = new StorageClient([
        'keyFilePath' => '/var/www/elpollovolantuso/negocioencontrol/negocios/modulos/gcs-key.json'
    ]);
    $bucket = $storage->bucket($bucketName);

    /* ==============================
       ELIMINAR PRODUCTO
       ============================== */
    if (!empty($input['eliminar']) && !empty($input['id'])) {

        $id = intval($input['id']);

        $stmt = $conexion->prepare("SELECT producto FROM bodega WHERE id=? AND negocio=?");
        $stmt->bind_param("is", $id, $negocio);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($prod) {
            $nombreProducto = $prod['producto'];

            // Buscar imagen en tabla productos
            $stmt = $conexion->prepare("SELECT imagen FROM productos WHERE producto=? LIMIT 1");
            $stmt->bind_param("s", $nombreProducto);
            $stmt->execute();
            $imgRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Solo borrar si existe y es de GCS
            if (!empty($imgRow['imagen']) && strpos($imgRow['imagen'], 'storage.googleapis.com') !== false) {
                $urlPath = parse_url($imgRow['imagen'], PHP_URL_PATH);
                $object = $bucket->object(ltrim($urlPath, '/'));
                if ($object->exists()) {
                    $object->delete();
                }
            }

            // Eliminar BD
            $conexion->query("DELETE FROM bodega WHERE id=$id AND negocio='$negocio'");
            $conexion->query("DELETE FROM productos WHERE producto='$nombreProducto'");
        }

        echo json_encode(['ok'=>true, 'msg'=>'Producto eliminado correctamente']);
        exit;
    }

    /* ==============================
       AGREGAR / EDITAR PRODUCTO
       ============================== */

    $id = intval($input['id'] ?? 0);
    $nombre = $conexion->real_escape_string($input['producto'] ?? '');
    $descripcion = $conexion->real_escape_string($input['descripcion'] ?? '');
    $cantidad = floatval($input['cantidad'] ?? 0);
    $precio  = floatval($input['precio'] ?? 0);

    $imagen_url = null;

    // PROCESAR IMAGEN SUBIDA
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {

        $fileTmp  = $_FILES['imagen']['tmp_name'];
        $fileName = $_FILES['imagen']['name'];

        $res = procesarArchivoSubido($fileTmp, $fileName);
        if (!$res) throw new Exception("Formato de imagen no válido");

        [$tmpCompressed, $fileNameFinal] = $res;

        // SUBIR A GCS
        $bucket->upload(fopen($tmpCompressed, 'r'), [
            'name' => "$negocio/productos/$fileNameFinal"
        ]);

        $imagen_url = "https://storage.googleapis.com/$bucketName/$negocio/productos/$fileNameFinal?v=" . time();

        unlink($tmpCompressed);
    }

    $conexion->begin_transaction();

    /* Insertar o actualizar en bodega */
    if ($id > 0) {
        $stmt = $conexion->prepare(
            "UPDATE bodega SET producto=?, descripcion=?, cantidad=?, precio=? 
             WHERE id=? AND negocio=?"
        );
        $stmt->bind_param("ssdisi", $nombre, $descripcion, $cantidad, $precio, $id, $negocio);

        $msg = "Producto actualizado";

    } else {
        $stmt = $conexion->prepare(
            "INSERT INTO bodega (negocio, producto, descripcion, cantidad, precio, fecha_registro)
             VALUES (?,?,?,?,?,NOW())"
        );
        $stmt->bind_param("sssdd", $negocio, $nombre, $descripcion, $cantidad, $precio);

        $msg = "Producto agregado";
    }

    $stmt->execute();
    $stmt->close();

    /* SINCRONIZAR TABLA productos */
    $stmt = $conexion->prepare("SELECT id_producto FROM productos WHERE producto=? LIMIT 1");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $idp = $result->fetch_assoc()['id_producto'];

        if ($imagen_url) {
            $stmt = $conexion->prepare("UPDATE productos SET precio=?, imagen=? WHERE id_producto=?");
            $stmt->bind_param("dsi", $precio, $imagen_url, $idp);
        } else {
            $stmt = $conexion->prepare("UPDATE productos SET precio=? WHERE id_producto=?");
            $stmt->bind_param("di", $precio, $idp);
        }

    } else {
        $imgFinal = $imagen_url ?? $defaultImage;
        $stmt = $conexion->prepare("INSERT INTO productos (producto, precio, imagen) VALUES (?,?,?)");
        $stmt->bind_param("sds", $nombre, $precio, $imgFinal);
    }

    $stmt->execute();
    $stmt->close();

    $conexion->commit();

    echo json_encode(['ok'=>true, 'msg'=>$msg]);
    exit;

} catch (Exception $e) {
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
    exit;
}

?>
