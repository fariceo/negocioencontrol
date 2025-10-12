<?php
include 'conexion_encontrol.php';

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : "";

if ($busqueda != "") {
    $sql = "SELECT id, producto, precio, img FROM menu WHERE producto LIKE '%" . mysqli_real_escape_string($conexion_encontrol, $busqueda) . "%'";
} else {
    $sql = "SELECT id, producto, precio, img FROM menu";
}

$result = mysqli_query($conexion_encontrol, $sql);

// Encabezado de la tabla, ahora con columna Imagen
echo "<tr>
        <th>Imagen</th>
        <th>Producto</th>
        <th>Precio</th>
        <th>Cantidad</th>
        <th>Acción</th>
      </tr>";


while ($row = mysqli_fetch_assoc($result)) {
    // Ruta imagen - ajusta la carpeta donde están tus imágenes
    $rutaImagen = "../imagenes/" . htmlspecialchars($row['img']);

    echo "<tr>
            <td><img src='$rutaImagen' alt='" . htmlspecialchars($row['producto']) . "' style='width:60px; height:auto; border-radius:5px;'></td>
            <td>" . htmlspecialchars($row['producto']) . "</td>
            <td>" . number_format($row['precio'], 2) . "</td>
            <td><input type='number' id='cantidad_" . $row['id'] . "' value='1' min='1' style='width:60px;'></td>
            <td><button onclick=\"agregarPedido('" . $row['id'] . "', '" . htmlspecialchars($row['producto']) . "', '" . $row['precio'] . "')\">Agregar</button></td>
          </tr>";
}
?>
