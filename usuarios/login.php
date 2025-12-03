<?php
session_start();
include "../core/conexion.php"; 

$db = new Conexion();
$conexionCentral = $db->central();
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';

    if ($correo && $contraseña) {
        $stmt = $conexionCentral->prepare("
            SELECT u.id_usuario, u.contraseña, u.id_negocio, n.nombre_bd
            FROM usuarios_central u
            INNER JOIN negocios n ON u.id_negocio = n.id_negocio
            WHERE u.correo = ? AND u.estado='activo'
        ");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($contraseña, $row['contraseña'])) {
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['id_negocio'] = $row['id_negocio'];
                $_SESSION['nombre_bd_negocio'] = $row['nombre_bd'];
                $_SESSION['usuario'] = $correo; // correo como identificador
                header("Location: ../negocios/index.php");
                exit;
            } else {
                $mensaje = "Contraseña incorrecta.";
            }
        } else {
            $mensaje = "Usuario no encontrado o inactivo.";
        }
    } else {
        $mensaje = "Completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta charset="UTF-8" name="viewport" content="width=device-width">
<title>Login - Negocio en Control</title>
<style>
body { font-family: Arial; margin: 50px; background-color: #f4f4f4; }
form { max-width: 400px; margin: auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
input, button { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
button { background-color: #28a745; color: #fff; border: none; cursor: pointer; }
button:hover { background-color: #218838; }
.mensaje { text-align: center; color: red; margin-bottom: 20px; }
p { text-align: center; }
a { color: blue; text-decoration: underline; }
</style>
</head>
<body>
<h2 style="text-align:center;">Login - Negocio en Control</h2>
<div class="mensaje"><?= $mensaje ?></div>
<form method="POST">
    <input type="email" name="correo" placeholder="Correo electrónico" required>
    <input type="password" name="contraseña" placeholder="Contraseña" required>
    <button type="submit">Ingresar</button>
</form>
<p><a href="registro_usuario.php">Registrar nuevo usuario</a></p>
</body>
</html>
