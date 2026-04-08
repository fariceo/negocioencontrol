<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../core/conexion.php";

$negocio = $_POST['negocio'] ?? '';
$usuario = $_POST['usuario'] ?? '';

if($negocio=="" || $usuario==""){
    echo json_encode([
        "success"=>false,
        "msg"=>"Datos incompletos"
    ]);
    exit;
}

$conexionObj = new Conexion();
$conn = $conexionObj->negocio($negocio);

if(!$conn){
    echo json_encode([
        "success"=>false,
        "msg"=>"Error conexión BD"
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM carrito WHERE usuario=?");

$stmt->bind_param("s",$usuario);

if($stmt->execute()){

    echo json_encode([
        "success"=>true,
        "msg"=>"Carrito eliminado"
    ]);

}else{

    echo json_encode([
        "success"=>false,
        "msg"=>"Error: ".$stmt->error
    ]);

}

$stmt->close();
$conn->close();
?>