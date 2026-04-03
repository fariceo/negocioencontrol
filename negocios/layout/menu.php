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
/* =========================
   CONTENEDOR GENERAL
========================= */
#menuContainer {
    width: 100%;
    position: sticky;
    top: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #0b1220 0%, #111827 50%, #0f172a 100%);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.28);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(14px);
}

/* =========================
   WRAPPER
========================= */
.menu-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px 18px 18px;
}

/* =========================
   CABECERA
========================= */
.menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

/* =========================
   TITULO
========================= */
.menu-title {
    color: #f8fafc;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.menu-title i {
    font-size: 19px;
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.12);
    padding: 11px;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(56, 189, 248, 0.12);
}

/* =========================
   SUBTEXTO
========================= */
.menu-subtitle {
    color: rgba(255, 255, 255, 0.68);
    font-size: 13px;
    margin-top: 4px;
    font-weight: 500;
}

/* =========================
   BOTÓN HAMBURGUESA
========================= */
#menuToggle {
    display: none;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.10);
    color: #f8fafc;
    font-size: 22px;
    border-radius: 16px;
    padding: 11px 14px;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.10);
}

#menuToggle:hover {
    background: rgba(56, 189, 248, 0.12);
    border-color: rgba(56, 189, 248, 0.25);
    transform: translateY(-1px);
}

/* =========================
   LISTA DE MENÚ
========================= */
#menuLinks {
    list-style: none;
    margin: 18px 0 0;
    padding: 0;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
}

/* =========================
   ITEMS
========================= */
#menuLinks li {
    list-style: none;
}

/* =========================
   ENLACES
========================= */
#menuLinks li a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 14px 18px;
    border-radius: 18px;
    text-decoration: none;
    color: #e5e7eb;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 14px;
    font-weight: 700;
    transition: all 0.28s ease;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.10);
    min-height: 52px;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

/* Efecto brillo */
#menuLinks li a::before {
    content: "";
    position: absolute;
    top: 0;
    left: -120%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent 0%,
        rgba(255,255,255,0.10) 50%,
        transparent 100%
    );
    transition: all 0.6s ease;
}

#menuLinks li a:hover::before {
    left: 120%;
}

#menuLinks li a i {
    font-size: 16px;
    min-width: 18px;
    text-align: center;
    color: #38bdf8;
    transition: 0.25s ease;
}

/* =========================
   HOVER
========================= */
#menuLinks li a:hover {
    transform: translateY(-3px);
    background: rgba(15, 23, 42, 0.85);
    border-color: rgba(56, 189, 248, 0.25);
    color: #ffffff;
    box-shadow: 0 14px 28px rgba(56, 189, 248, 0.14);
}

#menuLinks li a:hover i {
    color: #7dd3fc;
}

/* =========================
   AJUSTES DESTACADO
========================= */
#menuLinks li:last-child a {
    background: linear-gradient(135deg, #06b6d4, #2563eb);
    border: none;
    color: #ffffff;
    box-shadow: 0 12px 26px rgba(37, 99, 235, 0.25);
}

#menuLinks li:last-child a i {
    color: #ffffff;
}

#menuLinks li:last-child a:hover {
    transform: translateY(-3px);
    background: linear-gradient(135deg, #0891b2, #1d4ed8);
    box-shadow: 0 16px 30px rgba(29, 78, 216, 0.32);
}

/* =========================
   ESTADO ACTIVO
========================= */
#menuLinks li a.activo {
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.25);
    color: #ffffff;
    box-shadow: 0 12px 24px rgba(56, 189, 248, 0.14);
}

#menuLinks li a.activo i {
    color: #7dd3fc;
}

/* =========================
   MÓVIL
========================= */
@media (max-width: 768px) {
    .menu-wrapper {
        padding: 14px;
    }

    .menu-title {
        font-size: 17px;
    }

    .menu-subtitle {
        font-size: 12px;
    }

    #menuToggle {
        display: block;
    }

    #menuLinks {
        display: none;
        flex-direction: column;
        margin-top: 16px;
        gap: 10px;
        animation: slideDown 0.25s ease;
    }

    #menuLinks.show {
        display: flex;
    }

    #menuLinks li {
        width: 100%;
    }

    #menuLinks li a {
        width: 100%;
        justify-content: flex-start;
        padding: 15px 16px;
        border-radius: 18px;
        font-size: 15px;
    }

    #menuLinks li a:hover {
        transform: none;
    }
}

/* =========================
   MÓVIL PEQUEÑO
========================= */
@media (max-width: 480px) {
    .menu-title {
        font-size: 15px;
    }

    .menu-title i {
        font-size: 16px;
        padding: 9px;
    }

    #menuToggle {
        font-size: 20px;
        padding: 9px 12px;
    }

    #menuLinks li a {
        font-size: 14px;
        padding: 14px 14px;
    }
}

/* =========================
   ANIMACIÓN
========================= */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div id="menuContainer">
    <div class="menu-wrapper">
        <div class="menu-header">
            <div>
                <div class="menu-title">
                    <i class="fa-solid fa-store"></i>
                    Panel del negocio
                </div>
                <div class="menu-subtitle">
                    Accede rápido a tus módulos principales
                </div>
            </div>

            <button id="menuToggle" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <ul id="menuLinks">
            <?php if(!empty($menus)): ?>
                <?php foreach($menus as $row): ?>
                    <li>
                        <a href="#" data-ruta="/negocioencontrol/negocios/modulos/<?php echo htmlspecialchars($row['ruta']); ?>">
                            <?php if(!empty($row['icono'])): ?>
                                <i class="<?php echo htmlspecialchars($row['icono']); ?>"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($row['etiqueta']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>
                    <span style="color:white; font-weight:bold;">No hay módulos habilitados</span>
                </li>
            <?php endif; ?>

            <li>
                <a href="#" data-ruta="/negocioencontrol/negocios/modulos/modulos_adicionales.php" title="Ajustes">
                    <i class="fa-solid fa-cogs"></i>
                    <span>Ajustes</span>
                </a>
            </li>
        </ul>
    </div>
</div>