<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

// Verificar sesión activa

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtener datos del formulario
    $nombre_negocio = trim($_POST['nombre_negocio']);
    $plan = $_POST['plan'];

    if (empty($nombre_negocio)) {
        die("❌ Debes ingresar un nombre para el negocio.");
    }

    // Crear nombre de base de datos (sin espacios ni caracteres especiales)
    $nombre_bd = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $nombre_negocio));

    // Conexión al sistema principal
    $db = new Conexion();
    $conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

    if (!$conexion) {
        die("Error de conexión al sistema principal.");
    }

    // Verificar si el negocio ya existe
    $verificar = $conexion->prepare("SELECT id_negocio FROM control_clientes WHERE nombre_bd = ?");
    $verificar->bind_param("s", $nombre_bd);
    $verificar->execute();
    $resultado = $verificar->get_result();

    if ($resultado->num_rows > 0) {
        die("⚠️ Ya existe un negocio con ese nombre.");
    }

    // Insertar en tabla control_clientes
    $insertar = $conexion->prepare("INSERT INTO control_clientes (nombre_negocio, nombre_bd, plan, estado, creado_en) VALUES (?, ?, ?, 'Activo', NOW())");
    $insertar->bind_param("sss", $nombre_negocio, $nombre_bd, $plan);
    if (!$insertar->execute()) {
        die("Error al registrar el negocio: " . $insertar->error);
    }

    // Crear base de datos del nuevo negocio
    $conexion->query("CREATE DATABASE `$nombre_bd` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

    // Copiar estructura desde la base de datos plantilla (negocioencontrol)
    $conexion->select_db($nombre_bd);

    // Conexión temporal para copiar tablas
    $conexion_plantilla = new mysqli("localhost", "root", "", "negocioencontrol");

    if ($conexion_plantilla->connect_error) {
        die("Error al conectar con la base de datos plantilla: " . $conexion_plantilla->connect_error);
    }

    $tablas = $conexion_plantilla->query("SHOW TABLES");
    while ($fila = $tablas->fetch_array()) {
        $tabla = $fila[0];
        $conexion_plantilla->query("CREATE TABLE `$nombre_bd`.`$tabla` LIKE `negocioencontrol`.`$tabla`");
    }

    echo "<p style='color:green; text-align:center;'>✅ Negocio '$nombre_negocio' registrado y base de datos '$nombre_bd' creada correctamente.</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nuevo Negocio</title>
    <link rel="stylesheet" href="/negocioencontrol/estilos.css">
</head>
<body>
    <div style="width:50%;margin:auto;margin-top:50px;padding:20px;border:1px solid #ccc;border-radius:10px;">
        <h2>Registrar Nuevo Negocio</h2>
        <form method="POST">
            <label>Nombre del Negocio:</label><br>
            <input type="text" name="nombre_negocio" required><br><br>

            <label>Plan:</label><br>
            <select name="plan" required>
                <option value="Básico">Básico</option>
                <option value="Premium">Premium</option>
                <option value="Empresarial">Empresarial</option>
            </select><br><br>

            <button type="submit">Registrar Negocio</button>
        </form>
    </div>
</body>
</html>
