<?php
// Conexión a la BD
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// Traer módulos habilitados
$sql = "SELECT modulo, etiqueta, icono, ruta 
        FROM ajustes_menu 
        WHERE habilitado = 1 
        ";

$result = $conexion->query($sql);

// Validar que la consulta no falle
if(!$result){
    echo "<div style='color:red; padding:10px;'>Error al cargar el menú: " . $conexion->error . "</div>";
    $menus = [];
} else {
    $menus = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div id="menuContainer">
    <button id="menuToggle">&#9776;</button> <!-- Icono hamburguesa -->
    <ul id="menuLinks">
        <?php if(!empty($menus)): ?>
            <?php foreach($menus as $row): ?>
                <li>
                    <a href="#" data-ruta="/negocioencontrol/negocios/modulos/<?php echo $row['ruta']; ?>">
                        <?php if($row['icono']): ?>
                            <i class="<?php echo $row['icono']; ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($row['etiqueta']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li style="color:white; padding:5px;">No hay módulos habilitados</li>
        <?php endif; ?>
    </ul>
</div>

<script>
// Tu código JS de menú permanece igual
</script>
