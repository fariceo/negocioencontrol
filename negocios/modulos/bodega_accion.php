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

// Función para comprimir imágenes
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
            imagesavealpha($img,true);
            return imagepng($img, $dest, 8);
        default: return false;
    }
}

function redimensionarImagen($source, $dest, $maxWidth = 800, $maxHeight = 800) {
    list($width, $height, $type) = getimagesize($source);

    // Si la imagen ya es pequeña, no hacemos nada
    if ($width <= $maxWidth && $height <= $maxHeight) {
        copy($source, $dest);
        return true;
    }

    // Cálculo de escala manteniendo proporción
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = intval($width * $ratio);
    $newHeight = intval($height * $ratio);

    // Crear imagen base
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            imagesavealpha($srcImage, true);
            break;
        default:
            return false;
    }

    // Redimensionar
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($type == IMAGETYPE_PNG) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }

    imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, 
        $newWidth, $newHeight, $width, $height);

    // Guardar
    if ($type == IMAGETYPE_PNG) {
        imagepng($newImage, $dest, 8);
    } else {
        imagejpeg($newImage, $dest, 80);
    }

    return true;
}


// Función para procesar imágenes subidas

function procesarArchivoSubido($fileTmp, $fileName) {

    // 1. Detectar MIME real
    $mime = mime_content_type($fileTmp);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // 2. Convertir HEIC/HEIF → JPG (iPhone)
    if ($mime === "image/heic" || $mime === "image/heif" || $ext === "heic") {
        $imagick = new Imagick();
        $imagick->readImage($fileTmp);
        $imagick->setImageFormat('jpeg');
        $newPath = "/tmp/img_" . uniqid() . ".jpg";
        $imagick->writeImage($newPath);
        $fileTmp = $newPath;
        $ext = "jpg";
    }

    // 3. Convertir WebP → JPG
    if ($mime === "image/webp" || $ext === "webp") {
        $img = imagecreatefromwebp($fileTmp);
        $newPath = "/tmp/img_" . uniqid() . ".jpg";
        imagejpeg($img, $newPath, 90);
        $fileTmp = $newPath;
        $ext = "jpg";
    }

    // 4. Cargar imagen con GD
    $img = @imagecreatefromstring(file_get_contents($fileTmp));
    if (!$img) return false;

    // 5. Corregir orientación EXIF (iPhone)
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

    // 6. Redimensionar si es muy grande
    $maxWidth = 800;
    $w = imagesx($img);
    $h = imagesy($img);

    if ($w > $maxWidth) {
        $newW = $maxWidth;
        $newH = intval($h * ($newW / $w));
        $tmp = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        $img = $tmp;
    }

    // 7. Guardar como JPG comprimido
    $finalPath = "/tmp/final_" . uniqid() . ".jpg";
    imagejpeg($img, $finalPath, 80);

    return [$finalPath, uniqid() . ".jpg"];
}

//

try {
    $input = $_POST;

    // Configuración GCS
    $bucketName = 'negocioencontrol';
    $storage = new StorageClient([
        'keyFilePath'=>'/var/www/elpollovolantuso/negocioencontrol/negocios/modulos/gcs-key.json'
    ]);
    $bucket = $storage->bucket($bucketName);

    // ELIMINAR PRODUCTO
    if(!empty($input['eliminar']) && !empty($input['id'])){
        $id = intval($input['id']);
        $stmt = $conexion->prepare("SELECT producto FROM bodega WHERE id=? AND negocio=?");
        $stmt->bind_param("is",$id,$negocio);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($prod){
            $nombreProducto = $prod['producto'];

            // Eliminar imagen en GCS
            $stmt = $conexion->prepare("SELECT imagen FROM productos WHERE producto=? LIMIT 1");
            $stmt->bind_param("s",$nombreProducto);
            $stmt->execute();
            $imgRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if(!empty($imgRow['imagen']) && strpos($imgRow['imagen'],'storage.googleapis.com')!==false){
                $urlPath = parse_url($imgRow['imagen'],PHP_URL_PATH);
                $object = $bucket->object(ltrim($urlPath,'/'));
                if($object->exists()) $object->delete();
            }

            // Eliminar de base de datos
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

    if(isset($_FILES['imagen']) && $_FILES['imagen']['error']===UPLOAD_ERR_OK){
        $fileTmp = $_FILES['imagen']['tmp_name'];
        $fileName = $_FILES['imagen']['name'];
        $res = procesarArchivoSubido($fileTmp,$fileName);
        if(!$res) throw new Exception("Formato de imagen no válido");
        [$tmpCompressed, $fileNameFinal] = $res;

        $bucket->upload(fopen($tmpCompressed,'r'),['name'=>"$negocio/productos/$fileNameFinal"]);
        $imagen_url = "https://storage.googleapis.com/$bucketName/$negocio/productos/$fileNameFinal?v=".time();
        unlink($tmpCompressed);
    }

    $conexion->begin_transaction();
    try {
        if($id>0){
            $stmt = $conexion->prepare("UPDATE bodega SET producto=?, descripcion=?, cantidad=?, precio=? WHERE id=? AND negocio=?");
            $stmt->bind_param("ssdisi",$nombre,$descripcion,$cantidad,$precio,$id,$negocio);
            $stmt->execute();
            $stmt->close();
            $msg = "Producto actualizado";
        } else {
            $stmt = $conexion->prepare("INSERT INTO bodega (negocio,producto,descripcion,cantidad,precio,fecha_registro) VALUES (?,?,?,?,?,NOW())");
            $stmt->bind_param("sssdd",$negocio,$nombre,$descripcion,$cantidad,$precio);
            $stmt->execute();
            $stmt->close();
            $msg = "Producto agregado";
        }

        // Tabla productos
        $stmt = $conexion->prepare("SELECT id_producto FROM productos WHERE producto=? LIMIT 1");
        $stmt->bind_param("s",$nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if($result->num_rows>0){
            $id_producto = $result->fetch_assoc()['id_producto'];
            if($imagen_url){
                $stmt = $conexion->prepare("UPDATE productos SET precio=?, imagen=? WHERE id_producto=?");
                $stmt->bind_param("dsi",$precio,$imagen_url,$id_producto);
            } else {
                $stmt = $conexion->prepare("UPDATE productos SET precio=? WHERE id_producto=?");
                $stmt->bind_param("di",$precio,$id_producto);
            }
        } else {
            $imgFinal = $imagen_url ?? $defaultImage;
            $stmt = $conexion->prepare("INSERT INTO productos (producto, precio, imagen) VALUES (?,?,?)");
            $stmt->bind_param("sds",$nombre,$precio,$imgFinal);
        }
        $stmt->execute();
        $stmt->close();
        $conexion->commit();

        $response = ['ok'=>true,'msg'=>$msg];

    } catch(Exception $e){
        $conexion->rollback();
        throw $e;
    }

}catch(Exception $e){
    $response = ['ok'=>false,'msg'=>$e->getMessage()];
}

echo json_encode($response);
exit();
?>
