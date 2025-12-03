<?php
include '../conexion_encontrol.php'; // Ajusta la ruta si es necesario

$carpeta = __DIR__ . "/uploads/";
$mensaje = "";

// Crear carpeta si no existe
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Obtener imágenes de la base de datos
$imagenes = [];
$res = $conexion_encontrol->query("SELECT id, logo FROM ajustes_generales ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $imagenes[] = $row;
    }
}

// Procesar subida
if (isset($_POST['subir'])) {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = "logo_" . time() . "." . $ext;
        $destino = $carpeta . $nombreArchivo;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {

            // Insertar nuevo registro en DB
            $sql = "INSERT INTO ajustes_generales (nombre, logo, color) VALUES (?, ?, ?)";
            $stmt = $conexion_encontrol->prepare($sql);
            $nombreDefault = "Logo " . date('Y-m-d H:i:s');
            $colorDefault = "#000000";
            $stmt->bind_param("sss", $nombreDefault, $nombreArchivo, $colorDefault);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green'>✅ Imagen subida y registrada en BD.</p>";
            } else {
                $mensaje = "<p style='color:red'>⚠️ Error al guardar en BD: " . $stmt->error . "</p>";
            }
            $stmt->close();

            // Limitar máximo 3 registros, borrar el más antiguo si hay más
            $resCount = $conexion_encontrol->query("SELECT COUNT(*) as total FROM ajustes_generales");
            $total = $resCount->fetch_assoc()['total'];
            if ($total > 3) {
                $resOld = $conexion_encontrol->query("SELECT id, logo FROM ajustes_generales ORDER BY id ASC LIMIT 1");
                if ($rowOld = $resOld->fetch_assoc()) {
                    $rutaOld = $carpeta . $rowOld['logo'];
                    if (file_exists($rutaOld)) unlink($rutaOld);
                    $conexion_encontrol->query("DELETE FROM ajustes_generales WHERE id=" . $rowOld['id']);
                }
            }
        } else {
            $mensaje = "<p style='color:red'>❌ Error al mover la imagen.</p>";
        }
    } else {
        $mensaje = "<p style='color:red'>❌ No se recibió archivo válido.</p>";
    }
}

// Borrar imagen
if (isset($_POST['borrar'])) {
    $id = intval($_POST['borrar']);
    $resImg = $conexion_encontrol->query("SELECT logo FROM ajustes_generales WHERE id=$id");
    if ($row = $resImg->fetch_assoc()) {
        $ruta = $carpeta . $row['logo'];
        if (file_exists($ruta)) unlink($ruta);
        $conexion_encontrol->query("DELETE FROM ajustes_generales WHERE id=$id");
        $mensaje = "<p style='color:orange'>🗑️ Imagen eliminada.</p>";
    }
}

// Seleccionar imagen activa (opcional: marcar como principal)
if (isset($_POST['seleccionar'])) {
    $id = intval($_POST['seleccionar']);
    // Resetear todos como no activos
    $conexion_encontrol->query("UPDATE ajustes_generales SET color='#000000'");
    // Marcar seleccionado (ejemplo: usamos color verde para indicar activo)
    $conexion_encontrol->query("UPDATE ajustes_generales SET color='#00FF00' WHERE id=$id");
    $mensaje = "<p style='color:green'>🌟 Imagen seleccionada como activa.</p>";
}

// Volver a cargar imágenes
$imagenes = [];
$res = $conexion_encontrol->query("SELECT id, logo, color FROM ajustes_generales ORDER BY id ASC");
if ($res) while ($row = $res->fetch_assoc()) $imagenes[] = $row;
?>

<h3>🖼️ Gestión de Logos</h3>
<?= $mensaje ?>

<!-- Formulario para subir nueva -->
<?php if(count($imagenes) < 3): ?>
<form method="POST" enctype="multipart/form-data" action="imagen.php">
    <input type="file" name="logo" accept="image/*" required>
    <button type="submit" name="subir">Subir</button>
</form>
<?php else: ?>
<p style="color:red">⚠️ Tienes 3 imágenes, elimina una para subir otra.</p>
<?php endif; ?>

<hr>
<h4>📂 Imágenes registradas</h4>
<?php if ($imagenes): ?>
    <div style="display:flex; gap:15px; flex-wrap:wrap;">
    <?php foreach ($imagenes as $img): ?>
        <div style="border:1px solid #ccc; padding:10px; text-align:center;">
            <img src="uploads/<?= $img['logo'] ?>" style="max-width:150px; display:block; margin:auto;">
            <form method="POST" style="margin-top:5px;">
                <button type="submit" name="borrar" value="<?= $img['id'] ?>">🗑️ Borrar</button>
            </form>
            <form method="POST" style="margin-top:5px;">
                <button type="submit" name="seleccionar" value="<?= $img['id'] ?>" 
                    <?= ($img['color']=='#00FF00') ? "disabled style='background:green;color:white'" : "" ?>>
                    <?= ($img['color']=='#00FF00') ? "✔️ Activa" : "🌟 Seleccionar" ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
    </div>
<?php else: ?>
<p>No hay imágenes subidas aún.</p>
<?php endif; ?>
