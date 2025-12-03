
<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

$mensaje = "";
file_put_contents('/var/www/elpollovolantuso/negocioencontrol/log.txt', "Ejecutando registro de negocio...\n", FILE_APPEND);

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
    $nombre_negocio = trim($_POST['nombre_negocio'] ?? '');
    $correo_admin = trim($_POST['correo_admin'] ?? '');
    $contraseña_admin = $_POST['contraseña_admin'] ?? '';

    if ($nombre_negocio && $correo_admin && $contraseña_admin) {

        // --- Generar nombre seguro para la BD ---
        $nombre_bd = strtolower(preg_replace('/[^a-z0-9_]/', '_', str_replace(' ', '_', $nombre_negocio)));

        // --- Verificar si el negocio ya existe ---
        $stmt = $conexion->prepare("SELECT id_negocio, nombre_bd FROM negocios WHERE nombre_negocio = ?");
        $stmt->bind_param("s", $nombre_negocio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Registrar nuevo negocio
            $stmtInsert = $conexion->prepare("INSERT INTO negocios (nombre_negocio, nombre_bd, plan, estado, creado_en)
                                             VALUES (?, ?, 'Básico', 'activo', NOW())");
            $stmtInsert->bind_param("ss", $nombre_negocio, $nombre_bd);
            $stmtInsert->execute();
            $id_negocio = $stmtInsert->insert_id;
        } else {
            $row = $result->fetch_assoc();
            $id_negocio = $row['id_negocio'];
            $nombre_bd = $row['nombre_bd'];
        }

        // --- Crear BD del negocio si no existe ---
        if ($conexion->query("CREATE DATABASE IF NOT EXISTS `$nombre_bd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === FALSE) {
            die("Error al crear la base de datos del negocio: " . $conexion->error);
        }

        // --- Conectarse a la BD del negocio ---
        $conexionNegocio = new mysqli($host, $user, $pass, $nombre_bd);
        if ($conexionNegocio->connect_error) {
            die("Error al conectar a la BD del negocio: " . $conexionNegocio->connect_error);
        }

        // --- Crear tablas base del negocio ---
        $tablas = [
            "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
                id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                correo VARCHAR(100) UNIQUE NOT NULL,
                contraseña VARCHAR(255) NOT NULL,
                rol ENUM('admin','empleado','cajero') DEFAULT 'empleado',
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "ajustes" => "CREATE TABLE IF NOT EXISTS ajustes (
                id_ajuste INT AUTO_INCREMENT PRIMARY KEY,
                nombre_negocio VARCHAR(100) NOT NULL,
                logo VARCHAR(255),
                telefono VARCHAR(50),
                direccion VARCHAR(255),
                correo VARCHAR(100)
            ) ENGINE=InnoDB",

            "ajustes_menu" => "CREATE TABLE IF NOT EXISTS ajustes_menu (
                id INT AUTO_INCREMENT PRIMARY KEY,
                modulo VARCHAR(100) NOT NULL,
                etiqueta VARCHAR(255),
                icono VARCHAR(50),
                ruta VARCHAR(255),
                habilitado TINYINT(1) DEFAULT 1,
                orden INT(0) DEFAULT 0
            ) ENGINE=InnoDB",

            "bodega" => "CREATE TABLE IF NOT EXISTS bodega (
                id INT AUTO_INCREMENT PRIMARY KEY,
                negocio VARCHAR(100) NOT NULL,
                producto VARCHAR(100) NOT NULL,
                descripcion TEXT,
                cantidad DECIMAL(10,2) DEFAULT 0,
                precio DECIMAL(10,2) DEFAULT 0,
                categoria VARCHAR(50),
                stock_inicial INT DEFAULT 0,
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (negocio, producto)
            ) ENGINE=InnoDB",

            "productos" => "CREATE TABLE IF NOT EXISTS productos (
                id_producto INT AUTO_INCREMENT PRIMARY KEY,
                producto VARCHAR(100) NOT NULL,
                precio DECIMAL(10,2) NOT NULL,
                categoria VARCHAR(50),
                stock_inicial INT DEFAULT 0,
                imagen VARCHAR(255) DEFAULT '/negocioencontrol/negocios/modulos/assets/default.png'
            ) ENGINE=InnoDB",

            "ventas" => "CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negocio VARCHAR(100) NOT NULL,
    vendedor VARCHAR(100) NOT NULL,
    cliente VARCHAR(100) NOT NULL,
    productos JSON NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",


	

            "clientes" => "CREATE TABLE IF NOT EXISTS clientes (
                id_cliente INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                telefono VARCHAR(50),
                correo VARCHAR(100)
            ) ENGINE=InnoDB",

            // --- Nuevas tablas ---
            "carrito" => "CREATE TABLE IF NOT EXISTS carrito (
                id INT AUTO_INCREMENT PRIMARY KEY,
                negocio VARCHAR(100) NOT NULL,
                usuario VARCHAR(100) NOT NULL,
                producto VARCHAR(100) NOT NULL,
                precio DECIMAL(10,2) NOT NULL,
                cantidad INT DEFAULT 1,
                estado VARCHAR(100) NOT NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "gastos" => "CREATE TABLE IF NOT EXISTS gastos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                negocio VARCHAR(100) NOT NULL,
                usuario VARCHAR(100) NOT NULL,
                tipo VARCHAR(50),
                descripcion TEXT,
                cantidad INT DEFAULT 1,
                precio DECIMAL(10,2) DEFAULT 0,
                total DECIMAL(10,2) GENERATED ALWAYS AS (cantidad*precio) STORED,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        ];

        foreach ($tablas as $nombre => $sql) {
            $conexionNegocio->query($sql);
        }

        // --- Crear usuario administrador ---
        $hash_pass = password_hash($contraseña_admin, PASSWORD_BCRYPT);

        $checkAdminCentral = $conexion->prepare("SELECT id_usuario FROM usuarios_central WHERE correo = ?");
        $checkAdminCentral->bind_param("s", $correo_admin);
        $checkAdminCentral->execute();
        $resCentral = $checkAdminCentral->get_result();

        if ($resCentral->num_rows === 0) {
            $stmtCentral = $conexion->prepare("INSERT INTO usuarios_central (correo, contraseña, id_negocio, rol, estado, creado_en)
                                               VALUES (?, ?, ?, 'admin', 'activo', NOW())");
            $stmtCentral->bind_param("ssi", $correo_admin, $hash_pass, $id_negocio);
            $stmtCentral->execute();
        }

        // En BD del negocio
        $nombre_admin = explode('@', $correo_admin)[0];
        $checkAdminNegocio = $conexionNegocio->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $checkAdminNegocio->bind_param("s", $correo_admin);
        $checkAdminNegocio->execute();
        $resNegocio = $checkAdminNegocio->get_result();

        if ($resNegocio->num_rows === 0) {
            $stmtNegocio = $conexionNegocio->prepare("INSERT INTO usuarios (nombre, correo, contraseña, rol)
                                                      VALUES (?, ?, ?, 'admin')");
            $stmtNegocio->bind_param("sss", $nombre_admin, $correo_admin, $hash_pass);
            $stmtNegocio->execute();
        }

        // --- Copiar productos base ---
        $conexionNegocio->query("INSERT IGNORE INTO productos (producto, precio, categoria, stock_inicial, imagen)
                                SELECT producto, precio, categoria, stock_inicial, imagen
                                FROM negocioencontrol.productos");

        // --- Insertar módulos iniciales ---
        $modulos_iniciales = [
            ['productos', 'Productos', 'fas fa-store', 'productos.php', 1, 1],
            ['ventas', 'Ventas', 'fas fa-cash-register', 'ventas.php', 1, 2],
            ['bodega', 'Bodega', 'fas fa-warehouse', 'bodega.php', 1, 3],
            ['gastos', 'Gastos', 'fas fa-receipt', 'gastos.php', 1, 4],
        ];

        foreach ($modulos_iniciales as $mod) {
            $sql = "INSERT IGNORE INTO ajustes_menu (modulo, etiqueta, icono, ruta, habilitado, orden)
                    VALUES ('{$mod[0]}', '{$mod[1]}', '{$mod[2]}', '{$mod[3]}', {$mod[4]}, {$mod[5]})";
            $conexionNegocio->query($sql);
        }

        $conexionNegocio->close();
        $conexion->close();

        
 // --- Iniciar sesión automáticamente ---
        $_SESSION['nombre_bd_negocio'] = $nombre_bd;
        $mensaje = "✅ Negocio '$nombre_negocio' registrado correctamente con todas las tablas necesarias.";


        $_SESSION['usuario'] = $correo_admin;
        $_SESSION['rol'] = 'admin';
        $_SESSION['id_negocio'] = $id_negocio;

// --- Redirigir al panel ---
header("Location: ../negocios/index.php");
        exit;

    } else {
        $mensaje = "❌ Por favor completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

	<meta charset="UTF-8" name="viewport" content="width=device-width">
    <title>Registro de Negocio y Usuario</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        form { max-width: 420px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input, button { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: 1px solid #aaa; }
        button { background-color: #28a745; color: #fff; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
        .mensaje { text-align: center; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
<button onclick="window.history.back()" class="btn-atras">← Atrás</button>

<style>
.btn-atras {
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.3s;
}
.btn-atras:hover {
  background-color: #d32f2f;
}
</style>

<div class="mensaje"><?php echo $mensaje; ?></div>

<form method="POST">
    <h2 style="text-align:center;">Registrar Negocio</h2>
    <input type="text" name="nombre_negocio" placeholder="Nombre del negocio" required>
    <h3>Datos del Administrador</h3>
    <input type="email" name="correo_admin" placeholder="Correo electronico del administrador" required>
    <input type="password" name="contraseña_admin" placeholder="Contraseña" required>
    <button type="submit">Registrar Negocio</button>
</form>

</body>
</html>
