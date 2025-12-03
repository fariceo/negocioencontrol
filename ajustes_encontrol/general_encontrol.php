<?php
include '../conexion_encontrol.php';

$mensaje = "";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_negocio'] ?? '';
    $correo = $_POST['correo_contacto'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    // Verificar si hay registro
    $res = $conexion_encontrol->query("SELECT id FROM ajustes_generales LIMIT 1");
    
    if ($res && $res->num_rows > 0) {
        // Actualizar
        $row = $res->fetch_assoc();
        $stmt = $conexion_encontrol->prepare("UPDATE ajustes_generales SET nombre=?, correo=?, telefono=? WHERE id=?");
        $stmt->bind_param("sssi", $nombre, $correo, $telefono, $row['id']);
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green'>✅ Datos actualizados correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red'>⚠️ Error al actualizar: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        // Insertar
        $stmt = $conexion_encontrol->prepare("INSERT INTO ajustes_generales (nombre, correo, telefono) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $correo, $telefono);
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green'>✅ Datos guardados correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red'>⚠️ Error al guardar: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Obtener datos existentes
$nombre = $correo = $telefono = "";
$res = $conexion_encontrol->query("SELECT * FROM ajustes_generales LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $nombre = $row['nombre'];
    $correo = $row['correo'];
    $telefono = $row['telefono'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar Datos del Negocio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <h3>📌 Datos del Negocio</h3>
    <?= $mensaje ?>

    <form method="POST">
        <div class="mb-3">
            <label>Nombre del negocio</label>
            <input type="text" class="form-control" name="nombre_negocio" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>
        <div class="mb-3">
            <label>Correo de contacto</label>
            <input type="email" class="form-control" name="correo_contacto" value="<?= htmlspecialchars($correo) ?>" required>
        </div>
        <div class="mb-3">
            <label>Teléfono</label>
            <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required>
        </div>
        <button class="btn btn-success">Guardar</button>
    </form>
</div>

</body>
</html>
