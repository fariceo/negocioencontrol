<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    header("Location: ../usuarios/login.php");
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
    margin:0; padding:0;
}

/* CONTENEDOR DEL MENÚ */
#menuContainer {
    background: #2c3e50;
    padding: 10px;
    position: relative;
}

/* BOTÓN HAMBURGUESA */
#menuToggle {
    background: transparent;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    display: none;
}

/* LISTA DE MENÚ */
#menuLinks {
    list-style:none;
    display:flex;
    gap:10px;
    padding:0; margin:0;
    background:#2c3e50;
    flex-wrap:wrap;
}

#menuLinks li a {
    color:white;
    text-decoration:none;
    padding:10px 14px;
    display:flex;
    align-items:center;
    gap:6px;
    background:#34495e;
    border-radius:6px;
    transition:0.3s;
}

#menuLinks li a:hover {
    background:#1abc9c;
    transform:translateY(-2px);
}

/* VOLVER AL MENU */
#btnVolverMenu {
    display:none;
    background:#1abc9c;
    border:none;
    padding:8px 12px;
    margin-bottom:10px;
    color:white;
    font-size:16px;
    border-radius:5px;
    cursor:pointer;
}

/* RESPONSIVE */
@media (max-width:768px) {

    #menuToggle {
        display:block;
    }

    #menuLinks {
        display:none;
        flex-direction:column;
        width:100%;
        background:#2c3e50;
        padding:10px;
        border-radius:8px;
    }

    #menuLinks.show {
        display:flex;
    }

    #menuLinks li a {
        width:100%;
        padding:12px;
        font-size:16px;
    }

    /* Contenido oculto en móvil */
    #contenido {
        display:none;
    }
}

#contenido {
    padding:20px;
}
</style>
</head>
<body>

<?php include "layout/marquesina.php"; ?>
<?php include "layout/menu.php"; ?>

<button id="btnVolverMenu">⟵ Volver al menú</button>

<div id="contenido" style="height:450px; overflow-y:auto;"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const menuContainer = document.getElementById("menuContainer");
    const menuLinks = document.querySelectorAll('#menuLinks a');
    const contenido = document.getElementById('contenido');
    const btnVolver = document.getElementById('btnVolverMenu');
    const menuToggle = document.getElementById('menuToggle');
    const menuList = document.getElementById('menuLinks');

    function esMovil() {
        return window.innerWidth <= 768;
    }

    /* Toggle del menú hamburguesa */
    if (menuToggle) {
        menuToggle.addEventListener("click", function() {
            menuList.classList.toggle("show");
        });
    }

    /* Acción al seleccionar módulo */
    menuLinks.forEach(a => {
        a.addEventListener('click', function() {

            if (esMovil()) {
                menuContainer.style.display = "none";
                btnVolver.style.display = "block";
                contenido.style.display = "block";
                menuList.classList.remove("show");
            }
        });
    });

    /* Botón para volver al menú */
 btnVolver.addEventListener("click", function() {
    if (esMovil()) {
        contenido.style.display = "none";
        btnVolver.style.display = "none";
        menuContainer.style.display = "block";

        // 👇 FORZAR menú hamburguesa abierto
        menuList.classList.add("show");
    }
});


    menuList.classList.add("show");

    /* FUNCIÓN PARA CARGAR MÓDULOS */
    function cargarModulo(ruta) {
        fetch(ruta)
        .then(res => res.text())
        .then(html => {
            contenido.innerHTML = html;
            contenido.scrollTop = 0;

            const scripts = contenido.querySelectorAll('script');
            scripts.forEach(s => {
                const newScript = document.createElement('script');
                if(s.src) newScript.src = s.src;
                else newScript.textContent = `(function(){ ${s.textContent} })();`;
                document.body.appendChild(newScript);
                s.remove();
            });
        })
        .catch(err => {
            contenido.innerHTML =
                "<p style='color:red; text-align:center;'>No se pudo cargar el módulo: " + err.message + "</p>";
        });
    }

    /* Cargar el primer módulo en escritorio */
    if (!esMovil()) {
        cargarModulo('/negocioencontrol/negocios/modulos/productos.php');
    }

    /* Click de carga */
    menuLinks.forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            cargarModulo(this.dataset.ruta);
        });
    });

});


</script>

<form action="/negocioencontrol/usuarios/logout.php" method="post"
      style="position:fixed; bottom:20px; left:20px;">
    <button type="submit"
            style="padding:8px 12px; background:#dc3545; color:#fff; border:none; border-radius:5px; cursor:pointer;">
        Cerrar sesión
    </button>
</form>

</body>
</html>
