<?php
session_start();

// Si viene por POST normal (form-data o x-www-form-urlencoded)
if (!empty($_POST["usuario"])) {
    $_SESSION["usuario"] = $_POST["usuario"];
    echo json_encode(["success" => true, "usuario" => $_SESSION["usuario"]]);
    exit;
}

// Si viene por JSON
$data = json_decode(file_get_contents("php://input"), true);

if ($data && !empty($data["usuario"])) {
    $_SESSION["usuario"] = $data["usuario"];
    echo json_encode(["success" => true, "usuario" => $_SESSION["usuario"]]);
    exit;
}

echo json_encode(["success" => false, "mensaje" => "Usuario vacío"]);
