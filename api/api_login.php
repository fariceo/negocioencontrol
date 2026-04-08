<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../core/conexion.php";

$conexionObj = new Conexion();
$conexion = $conexionObj->central();

$correo = $_POST['correo'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {

    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;

}

$sql = "SELECT 
            u.id_usuario,
            u.contraseña,
            n.nombre_negocio,
            n.nombre_bd
        FROM usuarios_central u
        INNER JOIN negocios n
        ON u.id_negocio = n.id_negocio
        WHERE u.correo = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Error en la consulta SQL"
    ]);
    exit;

}

$stmt->bind_param("s", $correo);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $usuario = $result->fetch_assoc();

   if (password_verify($password, $usuario['contraseña'])) {

        echo json_encode([
            "success" => true,
            "id_usuario" => $usuario['id_usuario'],
            "nombre_negocio" => $usuario['nombre_negocio'],
            "nombre_bd" => $usuario['nombre_bd']
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Contraseña incorrecta"
        ]);

    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);

}

$stmt->close();
$conexion->close();