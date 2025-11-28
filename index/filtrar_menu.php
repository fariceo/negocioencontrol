<?php
// Activar errores para depuración

ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
include "../conexion.php"; // Asegúrate de que este archivo está en la misma ruta

if (!$conexion) {
    die("Error al conectar con la base de datos: " . mysqli_connect_error());
}

// Validar si se recibió la categoría
if (!isset($_GET['categoria']) || empty($_GET['categoria'])) {
    http_response_code(400);
    echo "Categoría no especificada";
    exit;
}

$categoria = $_GET['categoria'];

// Consulta segura con prepared statements
$sql = "SELECT producto, precio, detalle FROM menu WHERE categoria = ?";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo "Error al preparar la consulta: " . $conexion->error;
    exit;
}

$stmt->bind_param("s", $categoria);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificamos si hay productos
if ($resultado->num_rows === 0) {
    echo "<tr><td colspan='3'>No hay productos en esta categoría</td></tr>";
} else {
    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($fila['producto']) . "</td>";
        echo "<td>" . htmlspecialchars($fila['precio']) . "</td>";
        echo "<td>" . htmlspecialchars($fila['detalle']) . "</td>";
        echo "</tr>";
    }
}

// Cerrar conexiones
$stmt->close();
$conexion->close();
?>
