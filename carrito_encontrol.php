<?php
include 'conexion_encontrol.php';

$sql = "SELECT 
            usuario,
            GROUP_CONCAT(CONCAT(producto, ' (', cantidad, ')') SEPARATOR ', ') AS productos,
            SUM(cantidad) AS total_cantidad,
            SUM(total) AS total_precio,
            MAX(estado) AS estado,
            MAX(delivery) AS delivery,
            GROUP_CONCAT(DISTINCT metodo_pago SEPARATOR ', ') AS metodos_pago,
            MAX(fecha) AS fecha,
            MAX(hora) AS hora
        FROM pedidos
        WHERE estado = 1
        GROUP BY usuario
        ORDER BY fecha DESC, hora DESC";

$result = mysqli_query($conexion_encontrol, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <title>Pedidos agrupados por Usuario</title>
    <style>
      body {
    font-family: Arial, sans-serif;
    background-color: #f9fafb;
    color: #333;
}

table {
    border-collapse: collapse;
    width: 90%;
    margin: 20px auto;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
    background-color: #ffffff;
}

th, td {
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    text-align: center;
    font-size: 0.95rem;
}

th {
    background-color: #3b82f6; /* Azul vivo */
    color: #ffffff;
    font-weight: 600;
}

.pendiente {
    background-color: #fef3c7; /* Amarillo suave */
    color: #92400e; /* Marrón oscuro */
    font-weight: 600;
}

.entregado {
    background-color: #d1fae5; /* Verde menta suave */
    color: #065f46; /* Verde oscuro */
    font-weight: 600;
}

tbody tr:hover {
    background-color: #f3f4f6; /* Gris claro al pasar mouse */
    cursor: default;
}

    </style>
</head>
<body>
<!-- Menú de navegación con íconos -->
<div class="menu">
<a href="negocioencontrol.php" class="producto-link">
 <i class="fas fa-store"></i> 
</a>

    <a href="carrito_encontrol.php"><i class="fas fa-shopping-cart"></i></a>
    <a href="gastos.php"><i class="fas fa-wallet"></i></a>
  <!--  <a href="usuarios.php"><i class="fas fa-users"></i></a>-->
</div>

<style>
.menu {
    text-align: center;
    background-color: #333;
    padding: 10px 0;
}

.menu a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
    padding: 8px 15px;
    background-color: #4CAF50;
    border-radius: 5px;
    transition: background-color 0.3s;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px; /* espacio entre icono y texto */
    font-size: 1rem;
}

.menu a:hover {
    background-color: #45a049;
}

.menu i {
    font-size: 1.2rem; /* tamaño del icono */
}

.producto-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: white;
  text-decoration: none;
  background-color: #4CAF50;
  padding: 8px 15px;
  border-radius: 5px;
  font-weight: 600;
  transition: background-color 0.3s;
}

.producto-link:hover {
  background-color: #45a049;
}

.producto-link i {
  font-size: 1.2rem;
}





</style>

<h2 style="text-align:center;">Pedidos</h2>

<table>
    <tr>
        <th>Usuario</th>
        <th>Productos (Cantidad)</th>
        <th>Total Cantidad</th>
        <th>Total Precio</th>
        <th>Estado</th>
        <th>Delivery</th>
    
        
    </tr>

<?php
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $estadoTexto = ($row['estado'] == 1) ? "Pendiente" : "Procesado";
        $deliveryTexto = ($row['delivery'] == 1) ? "Sí" : "No";
        $claseFila = ($row['estado'] == 1) ? "pendiente" : "entregado";

        echo "<tr class='$claseFila'>
                <td>" . htmlspecialchars($row['usuario']) . "</td>
                <td style='text-align:left;'>" . htmlspecialchars($row['productos']) . "</td>
                <td>" . $row['total_cantidad'] . "</td>
                <td>$" . number_format($row['total_precio'], 2) . "</td>
                <td>$estadoTexto</td>
                <td>$deliveryTexto</td>
               
              
              </tr>
              <tr> 
               <td>Fecha:</td>
                <td>" . $row['fecha'] . "</td>
                <td>Hora:</td>
                <td>" . $row['hora'] . "</td>
                <td>Metodo de Pago:</td>
                 <td>" . htmlspecialchars($row['metodos_pago']) . "</td>
            
                
              </tr>";
    }
} else {
    echo "<tr><td colspan='9'>No hay pedidos para mostrar.</td></tr>";
}
?>

</table>

</body>
</html>
