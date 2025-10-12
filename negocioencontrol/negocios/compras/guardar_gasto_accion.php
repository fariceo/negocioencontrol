<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(["ok"=>false,"msg"=>"⚠️ Sesión no válida"]);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// 📌 Listar gastos en JSON
if (isset($_GET['listar'])) {
    $sql = "SELECT usuario, tipo, descripcion, cantidad, precio, total, fecha
            FROM gastos
            WHERE negocio = '".mysqli_real_escape_string($conexion, $_SESSION['nombre_bd_negocio'])."'
            ORDER BY fecha DESC LIMIT 10";
    $res = mysqli_query($conexion, $sql);

    $gastos = [];
    while($row = mysqli_fetch_assoc($res)){
        $gastos[] = $row;
    }
    echo json_encode($gastos);
    exit;
}

// 📌 Insertar gasto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $negocio     = $_SESSION['nombre_bd_negocio'];
    $usuario     = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $tipo        = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $cantidad    = (float) $_POST['cantidad'];
    $precio      = (float) $_POST['precio'];  
    $fecha       = date("Y-m-d H:i:s");

    $sql = "INSERT INTO gastos (negocio, usuario, tipo, descripcion, cantidad, precio, fecha)
            VALUES ('$negocio','$usuario','$tipo','$descripcion',$cantidad,$precio,'$fecha')";

    if(mysqli_query($conexion,$sql)){
        echo json_encode(["ok"=>true,"msg"=>"✅ Gasto registrado correctamente"]);
    } else {
        echo json_encode(["ok"=>false,"msg"=>"❌ Error: ".mysqli_error($conexion)]);
    }
    exit;
}
?>
