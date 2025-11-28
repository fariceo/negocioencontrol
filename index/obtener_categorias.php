<?php
include '../conexion.php';

// Tomar todas las categorías
$sql = "SELECT DISTINCT categoria FROM menu";
$result = mysqli_query($conexion, $sql);

$categorias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categorias[] = $row['categoria'];
}

// Opcional: desordenar el array para aleatoriedad
shuffle($categorias);

header('Content-Type: application/json');
echo json_encode($categorias);
