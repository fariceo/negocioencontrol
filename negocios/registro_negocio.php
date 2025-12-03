<?php
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Conexión a la BD central ---
    $host = "localhost";
    $db = "control_clientes";
    $user = "root";
    $pass = "clave";

    $conexion = new mysqli($host, $user, $pass, $db);
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // --- Datos del formulario ---
    $nombre_negocio = $_POST['nombre_negocio'] ?? '';
    $correo_admin = $_POST['correo_admin'] ?? '';
    $contraseña_admin = $_POST['contraseña_admin'] ?? '';

    if ($nombre_negocio && $correo_admin && $contraseña_admin) {

        // --- Generar nombre de BD del negocio ---
        $nombre_bd = strtolower(str_replace(' ', '_', $nombre_negocio));

        // --- Verificar y registrar negocio ---
        $result = $conexion->query("SELECT id_negocio FROM negocios WHERE nombre_negocio='$nombre_negocio'");
        if ($result->num_rows == 0) {
            $stmt = $conexion->prepare("INSERT INTO negocios (nombre_negocio, nombre_bd, plan, estado, creado_en) VALUES (?, ?, 'Básico', 'activo', NOW())");
            $stmt->bind_param("ss", $nombre_negocio, $nombre_bd);
            $stmt->execute();
            $id_negocio = $stmt->insert_id;
            if ($stmt->error) echo "Error negocio: " . $stmt->error;
        } else {
            $row = $result->fetch_assoc();
            $id_negocio = $row['id_negocio'];
        }

        // --- Verificar y registrar usuario admin ---
        $hash_pass = password_hash($contraseña_admin, PASSWORD_BCRYPT);
        $result2 = $conexion->query("SELECT id_usuario FROM usuarios_central WHERE correo='$correo_admin'");
        if ($result2->num_rows == 0) {
            $stmt2 = $conexion->prepare("INSERT INTO usuarios_central (correo, contraseña, id_negocio, rol, estado, creado_en) VALUES (?, ?, ?, 'admin', 'activo', NOW())");
            $stmt2->bind_param("ssi", $correo_admin, $hash_pass, $id_negocio);
            $stmt2->execute();
            if ($stmt2->error) echo "Error usuario: " . $stmt2->error;
        }

        // --- Crear la BD del negocio ---
        if ($conexion->query("CREATE DATABASE IF NOT EXISTS `$nombre_bd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === FALSE) {
            echo "Error al crear base de datos: " . $conexion->error;
        }
        $conexion->select_db($nombre_bd);

        // --- Crear tablas básicas del núcleo ---

        // Tabla usuarios
        $conexion->query("CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            correo VARCHAR(100) UNIQUE NOT NULL,
            contraseña VARCHAR(255) NOT NULL,
            rol ENUM('admin','empleado','cajero') DEFAULT 'empleado',
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        // Tabla ajustes
        $conexion->query("CREATE TABLE IF NOT EXISTS ajustes (
            id_ajuste INT AUTO_INCREMENT PRIMARY KEY,
            nombre_negocio VARCHAR(100) NOT NULL,
            logo VARCHAR(255),
            telefono VARCHAR(50),
            direccion VARCHAR(255),
            correo VARCHAR(100)
        ) ENGINE=InnoDB");

        // Tabla productos
        $conexion->query("CREATE TABLE IF NOT EXISTS productos (
            id_producto INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            categoria VARCHAR(50),
            stock_inicial INT DEFAULT 0
        ) ENGINE=InnoDB");

        // Tabla ventas (sin FK para evitar errores iniciales)
        $conexion->query("CREATE TABLE IF NOT EXISTS ventas (
            id_venta INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_producto INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(id_usuario),
            INDEX(id_producto)
        ) ENGINE=InnoDB");

        // Tabla clientes
        $conexion->query("CREATE TABLE IF NOT EXISTS clientes (
            id_cliente INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            telefono VARCHAR(50),
            correo VARCHAR(100)
        ) ENGINE=InnoDB");

        $mensaje = "Negocio y usuario admin registrados correctamente, y la base de datos del negocio ha sido creada.";

    } else {
        $mensaje = "Por favor completa todos los campos.";
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Negocio y Usuario</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        form { max-width: 400px; margin: auto; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
        .mensaje { text-align: center; color: green; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="mensaje"><?php echo $mensaje; ?></div>

<form id="registroForm" method="POST">
    <h2>Registrar Negocio</h2>
    <input type="text" name="nombre_negocio" placeholder="Nombre del negocio" required>
    <h2>Registrar Usuario Admin</h2>
    <input type="email" name="correo_admin" placeholder="Correo del admin" required>
    <input type="password" name="contraseña_admin" placeholder="Contraseña" required>
    <button type="submit">Registrar</button>
</form>

<script>
document.getElementById('registroForm').addEventListener('submit', function(e) {
    const form = e.target;
    if (!form.nombre_negocio.value || !form.correo_admin.value || !form.contraseña_admin.value) {
        alert("Completa todos los campos.");
        e.preventDefault();
    }
});
</script>

</body>
</html>
