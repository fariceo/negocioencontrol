<?php
include 'conexion_encontrol.php';

$usuario = $_POST['usuario'];
$producto = $_POST['producto'];
$cantidad = (int)$_POST['cantidad'];
$precio = (float)$_POST['precio'];
$total = $cantidad * $precio;
$estado = 1; // Puedes poner 1 como "pendiente"
$delivery = (int)$_POST['delivery'];
$metodo_pago = $_POST['metodo_pago'];
$fecha = date('Y-m-d');
$hora = date('H:i:s');

$sql = "INSERT INTO pedidos (usuario, producto, cantidad, precio, total, estado, delivery, metodo_pago, fecha, hora)
        VALUES ('$usuario', '$producto', $cantidad, $precio, $total, $estado, $delivery, '$metodo_pago', '$fecha', '$hora')";

if (mysqli_query($conexion_encontrol, $sql)) {
    echo "✅ Pedido agregado correctamente.";
} else {
    echo "❌ Error al agregar pedido: " . mysqli_error($conexion_encontrol);
}
?>
