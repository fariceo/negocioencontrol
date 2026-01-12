<?php
// Conexión a la BD
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// Traer módulos habilitados
$sql = "SELECT modulo, etiqueta, icono, ruta 
        FROM ajustes_menu 
        WHERE habilitado = 1";

$result = $conexion->query($sql);

// Validación
$menus = (!$result) ? [] : $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
/* ====== CONTENEDOR PRINCIPAL ====== */
#menuContainer {
    background: #2c3e50;
    width: 100%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}

/* ====== BOTÓN HAMBURGUESA ====== */
#menuToggle {
    display: none;
    font-size: 26px;
    color: white;
    background: none;
    border: none;
    padding: 12px;
    cursor: pointer;
}

/* ====== LISTA PRINCIPAL ====== */
#menuLinks {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

#menuLinks li a {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    color: white;
    background: #34495e;
    text-decoration: none;
    border-radius: 6px;
    transition: 0.2s;
    font-size: 14px;
    white-space: nowrap;
}

#menuLinks li a:hover {
    background: #1abc9c;
}

/* ====== RESPONSIVE ====== */
@media (max-width: 768px) {
    #menuToggle {
        display: block;
    }

    #menuLinks {
        display: none;
        flex-direction: column;
        padding: 10px;
    }

    #menuLinks.open {
        display: flex;
    }

    #menuLinks li {
        width: 100%;
    }

    #menuLinks li a {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<div id="menuContainer">
    <button id="menuToggle">&#9776;</button>

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
            <li><span style="color:white;">No hay módulos habilitados</span></li>
        <?php endif; ?>

        <!-- Ajustes -->
        <li>
            <a href="#" data-ruta="/negocioencontrol/negocios/modulos/modulos_adicionales.php" title="Ajustes">
                <i class="fa-solid fa-cogs"></i> Ajustes
            </a>
        </li>
    </ul>
</div>

<script>
document.getElementById("menuToggle").addEventListener("click", function() {
    document.getElementById("menuLinks").classList.toggle("open");
});
</script>
