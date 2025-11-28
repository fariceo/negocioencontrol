<?php
include '../conexion.php';

if (isset($_GET['categoria'])) {
    $categoria = $_GET['categoria'];

    $sql = "SELECT * FROM menu WHERE categoria = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $producto = htmlspecialchars($row["producto"]);
        $precio = number_format($row["precio"], 2);
        $imagen = htmlspecialchars($row["img"]);
        $detalles = htmlspecialchars($row["detalles"]);

        echo "<tr>";
        echo "<td>$producto</td>";
        echo "<td>$$precio</td>";
        echo "<td>
                <img 
                  src='imagenes/$imagen' 
                  alt='Imagen de $producto' 
                  width='80' height='80'
                  title='$detalles'
                >
              </td>";
        echo "</tr>";
        echo "<tr><td colspan='3' style='text-align:center;'>
                <button 
                  class='btnAgregar'
                  data-producto='$producto'
                  data-precio='$precio'
                  data-imagen='imagenes/$imagen'
                >Agregar</button>
              </td></tr>";
    }

    $stmt->close();
}
$conexion->close();
?>
