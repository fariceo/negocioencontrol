<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../core/conexion.php";

$token       = $_POST['token'] ?? '';
$usuario     = $_POST['usuario'] ?? '';
$negocio     = $_POST['negocio'] ?? '';
$dispositivo = $_POST['dispositivo'] ?? 'android';

if ($token == '' || $usuario == '' || $negocio == '') {

    echo json_encode([
        "success" => false,
        "msg" => "Datos incompletos"
    ]);
    exit;
}

try {

    $conexionObj = new Conexion();
    $conn = $conexionObj->central(); // BD control_clientes

    if (!$conn) {
        echo json_encode([
            "success" => false,
            "msg" => "Error conexión BD"
        ]);
        exit;
    }

    // verificar si el token ya existe
    $sql = "SELECT id FROM tokens_fcm WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {

        // actualizar datos
        $sqlUpdate = "UPDATE tokens_fcm 
                      SET negocio = ?, usuario = ?, dispositivo = ?, fecha = NOW()
                      WHERE token = ?";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssss", $negocio, $usuario, $dispositivo, $token);
        $stmtUpdate->execute();

    } else {

        // insertar nuevo token
        $sqlInsert = "INSERT INTO tokens_fcm
        (
            negocio,
            usuario,
            token,
            dispositivo,
            fecha
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NOW()
        )";

        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("ssss", $negocio, $usuario, $token, $dispositivo);
        $stmtInsert->execute();

    }

    echo json_encode([
        "success" => true,
        "msg" => "Token guardado correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "msg" => "Error servidor",
        "error" => $e->getMessage()
    ]);
}