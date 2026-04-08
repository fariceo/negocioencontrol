<?php
header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

/* ======================================
   1️⃣ OBTENER TOKEN
====================================== */

$headers = getallheaders();
$token = $headers['Authorization'] ?? $_POST['token'] ?? '';

$token = str_replace('Bearer ', '', $token);

if ($token == '') {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "msg" => "Token no proporcionado"
    ]);
    exit;
}

/* ======================================
   2️⃣ VALIDAR TOKEN EN BD CENTRAL
====================================== */

$db = new Conexion();
$conexion = $db->principal(); // control_clientes

$sql = "
SELECT 
    t.token,
    t.fecha,
    uc.id_usuario,
    uc.rol,
    uc.estado,
    n.id_negocio,
    n.nombre_negocio,
    n.nombre_bd
FROM tokens t
JOIN usuarios_central uc ON t.id_usuario = uc.id_usuario
JOIN negocios n ON uc.id_negocio = n.id_negocio
WHERE t.token = ?
LIMIT 1
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "msg" => "Token inválido"
    ]);
    exit;
}

/* ======================================
   3️⃣ VALIDACIONES EXTRA
====================================== */

if ($row['estado'] != 1) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "msg" => "Usuario inactivo"
    ]);
    exit;
}

/* Token expira en 24h */
$fechaToken = strtotime($row['fecha']);
if ((time() - $fechaToken) > 86400) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "msg" => "Token expirado"
    ]);
    exit;
}

/* ======================================
   4️⃣ RESPUESTA LIMPIA
====================================== */

echo json_encode([
    "success" => true,
    "usuario" => [
        "id_usuario" => $row['id_usuario'],
        "rol" => $row['rol']
    ],
    "negocio" => [
        "id_negocio" => $row['id_negocio'],
        "nombre" => $row['nombre_negocio'],
        "bd" => $row['nombre_bd']
    ]
]);
