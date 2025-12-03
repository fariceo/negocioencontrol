<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    header("Location: ../usuarios/login.php");
    exit;
}

// Conexión a la base de datos del negocio
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
?>
<div>
    <h2 style="text-align:center;">📊 Compras / Gastos</h2>

    <!-- Submenú interno -->
    <ul id="submenuCompras" style="list-style:none; display:flex; gap:10px; justify-content:center; padding:0; margin:15px 0;">
        <li><a href="#" data-ruta="compras/ingresar_gasto.php" style="background:#3498db;color:white;padding:8px 12px;border-radius:4px;text-decoration:none;">➕ Ingresar Gasto</a></li>
<li>
  <a href="#" data-ruta="/negocioencontrol/negocios/modulos/bodega.php" 
     style="background:#27ae60;color:white;padding:8px 12px;border-radius:4px;text-decoration:none;">
     📦 Bodega
  </a>
</li>
        <li><a href="#" data-ruta="compras/listado_gastos.php" style="background:#8e44ad;color:white;padding:8px 12px;border-radius:4px;text-decoration:none;">📋 Listado</a></li>
    </ul>

    <div id="contenidoCompras" style="padding:20px; border:1px solid #ccc; border-radius:6px; min-height:200px;">
        <p style="text-align:center;">Seleccione una opción del submenú.</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const submenu = document.querySelectorAll('#submenuCompras a');
    const contenidoCompras = document.getElementById('contenidoCompras');

    function cargarSubModulo(ruta){
        fetch(ruta)
        .then(res => res.text())
        .then(html => {
            contenidoCompras.innerHTML = html;

            // Ejecutar scripts internos del submódulo
            const scripts = contenidoCompras.querySelectorAll("script");
            scripts.forEach(s => {
                const newScript = document.createElement("script");
                if(s.src) newScript.src = s.src;
                else newScript.textContent = `(function(){ ${s.textContent} })();`;
                document.body.appendChild(newScript);
                s.remove();
            });
        })
        .catch(err => {
            contenidoCompras.innerHTML = "<p style='color:red;text-align:center;'>Error cargando submódulo: " + err.message + "</p>";
        });
    }

    submenu.forEach(a => {
        a.addEventListener("click", function(e){
            e.preventDefault();
            cargarSubModulo(this.dataset.ruta);
        });
    });
});
</script>
