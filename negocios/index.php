<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    header("Location: ../usuarios/login.php");
    exit;
}

// Conexión a la base de datos del negocio
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

// Función para verificar que la consulta devuelva resultados
function safeQuery($conexion, $sql){
    $res = $conexion->query($sql);
    if(!$res) return false;
    return $res;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" name="viewport" content="width=device-width">
<title>Panel de Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {font-family: Arial, sans-serif; margin:0; padding:0;}
#contenido {padding:20px;}
#menuLinks {list-style:none; display:flex; gap:10px; padding:0; margin:0; background:#2c3e50; flex-wrap: wrap;}
#menuLinks li a {color:white; text-decoration:none; padding:8px 12px; display:block; background:#34495e; border-radius:4px;}
#menuLinks li a:hover {background:#1abc9c;}
</style>
</head>
<body>

<?php 
// Cargar marquesina de forma segura
if(file_exists("layout/marquesina.php")){
    include "layout/marquesina.php"; 
} else {
    echo "<div style='background:#111;color:white;padding:5px;text-align:center;'>Marquesina no disponible</div>";
}

// Cargar menú de forma segura
if(file_exists("layout/menu.php")){
    include "layout/menu.php"; 
} else {
    echo "<div style='background:#34495e;color:white;padding:10px;text-align:center;'>Menú no disponible</div>";
}
?>

<div id="contenido" style='height:450px; overflow-y:auto;'>
    <h2 style="text-align:center;">Bienvenido al panel</h2>
    <p style="text-align:center;">Seleccione un módulo del menú para comenzar.</p>
</div>

<form action="/negocioencontrol/usuarios/logout.php" method="post" 
      style="position:fixed; bottom:20px; left:20px;">
    <button type="submit" 
            style="padding:8px 12px; background:#dc3545; color:#fff; border:none; border-radius:5px; cursor:pointer;">
        Cerrar sesión
    </button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuLinks = document.querySelectorAll('#menuLinks a');
    const contenido = document.getElementById('contenido');

    function cargarModulo(ruta) {
        fetch(ruta)
        .then(res => res.text())
        .then(html => {
            contenido.innerHTML = html;
            contenido.scrollTop = 0;

            // Ejecutar scripts del módulo cargado
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
            console.error(err);
        });
    }

    menuLinks.forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            const ruta = this.dataset.ruta;
            cargarModulo(ruta);
        });
    });

    document.addEventListener('click', function(e){
        if(e.target.classList.contains('agregar')){
            const card = e.target.closest('.producto-card');
            if(!card) return;
            const producto = e.target.dataset.producto;
            const precio = parseFloat(e.target.dataset.precio);
            const cantidad = parseInt(card.querySelector('.cantidad')?.value || 0);

            fetch('/negocioencontrol/negocios/modulos/agregar_carrito.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({producto, precio, cantidad})
            })
            .then(res => res.json())
            .then(data => {
                if(data.ok){
                    cargarCarrito();
                    const noti = document.getElementById('notificacionProducto');
                    if(noti){
                        noti.textContent = 'Producto agregado';
                        noti.style.opacity = 1;
                        setTimeout(()=> noti.style.opacity = 0, 1000);
                    }
                } else {
                    alert(data.mensaje || 'Error al agregar');
                }
            });
        }

        if(e.target.id === 'cerrarCarrito'){
            const cont = document.getElementById('carritoContenedor');
            if(cont) cont.style.display='none';
        }

        if(e.target.id === 'carritoBtn'){
            const cont = document.getElementById('carritoContenedor');
            if(cont) cont.style.display = 'flex';
        }
    });

    function cargarCarrito(){
        fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php')
        .then(res => res.json())
        .then(data => {
            const itemsCont = document.getElementById('itemsCarrito');
            const contador = document.getElementById('contadorCarrito');
            let total = 0;
            if(itemsCont){
                itemsCont.innerHTML = '';
                data.forEach(item=>{
                    const div = document.createElement('div');
                    div.className = 'carrito-item';
                    div.innerHTML = `
                        <span>${item.producto} x ${item.cantidad}</span>
                        <span>$${(item.precio*item.cantidad).toFixed(2)}</span>
                    `;
                    itemsCont.appendChild(div);
                    total += item.precio*item.cantidad;
                });
            }
            if(contador) contador.textContent = data.length;
            const totalDiv = document.getElementById('total');
            if(totalDiv) totalDiv.textContent = 'Total: $' + total.toFixed(2);
        });
    }

    cargarCarrito();
});
</script>

</body>
</html>
