<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    //header("Location: ../usuarios/login.php");
    header("Location: https://elpollovolantuso.com/negocioencontrol/usuarios/login.php");
exit;

}

// Conexión a la base de datos
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel de Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: #f8fafc;
}

/* BOTÓN VOLVER */
#btnVolverMenu {
    display: none;
    background: linear-gradient(135deg, #10b981, #14b8a6);
    border: none;
    padding: 10px 14px;
    margin: 12px;
    color: white;
    font-size: 15px;
    font-weight: bold;
    border-radius: 12px;
    cursor: pointer;
    position: relative;
    z-index: 2000;
    box-shadow: 0 8px 20px rgba(16,185,129,0.25);
    transition: all 0.25s ease;
}

#btnVolverMenu:hover {
    transform: translateY(-1px);
}

/* CONTENIDO */
#contenido {
    padding: 20px;
    min-height: 500px;
    background: #f8fafc;
}

/* MÓVIL */
@media (max-width: 768px) {
    #contenido {
        display: none;
        padding: 14px;
        min-height: auto;
    }

    #menuToggle {
        display: block !important;
        position: relative;
        z-index: 3000;
    }

    #menuLinks.show {
        display: flex !important;
    }

    #btnVolverMenu {
        width: calc(100% - 24px);
        margin: 12px;
        text-align: center;
    }
}
</style>
</head>
<body>

<?php include "layout/marquesina.php"; ?>
<?php include "layout/menu.php"; ?>

<button id="btnVolverMenu" type="button">⟵ Volver al menú</button>

<div id="contenido" style="overflow-y:auto;"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuContainer = document.getElementById("menuContainer");
    const contenido = document.getElementById("contenido");
    const btnVolver = document.getElementById("btnVolverMenu");
    const menuToggle = document.getElementById("menuToggle");
    const menuList = document.getElementById("menuLinks");

    function esMovil() {
        return window.innerWidth <= 768;
    }

    function obtenerLinksMenu() {
        return document.querySelectorAll("#menuLinks a");
    }

    function mostrarMenu() {
        menuContainer.style.display = "block";
        contenido.style.display = "none";
        btnVolver.style.display = "none";

        if (menuToggle) {
            menuToggle.style.display = "block";
        }

        if (menuList) {
            menuList.classList.add("show");
        }
    }

    function mostrarContenido() {
        menuContainer.style.display = "none";
        contenido.style.display = "block";
        btnVolver.style.display = "block";

        if (menuList) {
            menuList.classList.remove("show");
        }
    }

    function modoEscritorio() {
        menuContainer.style.display = "block";
        contenido.style.display = "block";
        btnVolver.style.display = "none";

        if (menuToggle) {
            menuToggle.style.display = "none";
        }

        if (menuList) {
            menuList.classList.remove("show");
        }
    }

    function ejecutarScriptsDentroDeContenido() {
        const scripts = contenido.querySelectorAll("script");

        scripts.forEach(scriptOriginal => {
            const nuevoScript = document.createElement("script");

            if (scriptOriginal.src) {
                nuevoScript.src = scriptOriginal.src;
            } else {
                nuevoScript.textContent = scriptOriginal.textContent;
            }

            document.body.appendChild(nuevoScript);
            scriptOriginal.remove();
        });
    }

    function cargarModulo(ruta) {
        fetch(ruta)
            .then(res => {
                if (!res.ok) {
                    throw new Error("Error al cargar el módulo");
                }
                return res.text();
            })
            .then(html => {
                contenido.innerHTML = html;
                contenido.scrollTop = 0;

                ejecutarScriptsDentroDeContenido();

                if (esMovil()) {
                    mostrarContenido();
                }
            })
            .catch(err => {
                contenido.innerHTML = `
                    <p style="color:red; text-align:center; font-weight:bold;">
                        No se pudo cargar el módulo: ${err.message}
                    </p>
                `;

                if (esMovil()) {
                    mostrarContenido();
                }
            });
    }

    function activarEventosMenu() {
        const links = obtenerLinksMenu();

        links.forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();

                const ruta = this.dataset.ruta;
                if (ruta) {
                    cargarModulo(ruta);
                }
            });
        });
    }

    /* BOTÓN HAMBURGUESA */
    if (menuToggle) {
        menuToggle.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (menuList) {
                menuList.classList.toggle("show");
            }
        });
    }

    /* BOTÓN VOLVER */
    if (btnVolver) {
        btnVolver.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (esMovil()) {
                mostrarMenu();
            } else {
                modoEscritorio();
            }
        });
    }

    /* ACTIVAR LINKS */
    activarEventosMenu();

    /* ESTADO INICIAL */
    if (esMovil()) {
        mostrarMenu();
    } else {
        modoEscritorio();
        cargarModulo('/negocioencontrol/negocios/modulos/productos.php');
    }

    /* CAMBIO DE TAMAÑO */
    window.addEventListener("resize", function () {
        if (esMovil()) {
            if (contenido.innerHTML.trim() !== "") {
                mostrarContenido();
            } else {
                mostrarMenu();
            }
        } else {
            modoEscritorio();
        }
    });
});
</script>

<form action="/negocioencontrol/usuarios/logout.php" method="post"
      style="position:fixed; bottom:20px; left:20px; z-index:9999;">
    <button type="submit"
            style="padding:10px 14px; background:#dc3545; color:#fff; border:none; border-radius:10px; cursor:pointer; font-weight:bold;">
        Cerrar sesión
    </button>
</form>

</body>
</html>