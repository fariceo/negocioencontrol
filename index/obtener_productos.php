<?php
header('Content-Type: application/json');

include '../conexion.php'; // aquí pones tu conexión a la BD

if (!isset($_GET['categoria'])) {
    echo json_encode([]);
    exit;
}

$categoria = $_GET['categoria'];

// Para evitar SQL injection, usa prepared statements
$stmt = $conexion->prepare("SELECT producto, precio, detalles FROM menu WHERE categoria = ?");
$stmt->bind_param("s", $categoria);
$stmt->execute();
$result = $stmt->get_result();

$productos = [];

while ($row = $result->fetch_assoc()) {
    $productos[] = [
        'producto' => $row['producto'],
        'precio' => $row['precio'],
        'detalles' => $row['detalles']
    ];
}

echo json_encode($productos);
?>
