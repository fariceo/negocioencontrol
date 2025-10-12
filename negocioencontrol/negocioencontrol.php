<?php
include 'conexion_encontrol.php';

// Obtener el primer logo registrado
$res = $conexion_encontrol->query("SELECT logo FROM ajustes_generales ORDER BY id ASC LIMIT 1");
$logoURL = '';

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $rutaFisica = __DIR__ . '/ajustes_encontrol/uploads/' . $row['logo']; // ruta física en el servidor
    if (file_exists($rutaFisica)) {
        $logoURL = 'ajustes_encontrol/uploads/' . $row['logo']; // ruta relativa al navegador
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agregar Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: Arial, sans-serif; margin:0; }

        /* LOGO */
        .logo-empresa { text-align:center; padding:15px; background-color:#f9f9f9; }
        .logo-empresa img { max-height:80px; }
        .logo-empresa {
    padding: 15px;
    background-color: #f9f9f9;
    display: flex;          /* usar flex para alinear */
    align-items: center;    /* centrar verticalmente */
}

.logo-empresa {
    width: 80px;            /* tamaño del logo */
    height: 80px;
    border-radius: 50%;     /* lo hace circular */
    object-fit: cover;      /* recorta la imagen si no es cuadrada */
    margin-left: 10px;      /* margen desde el borde izquierdo */
    border: 2px solid #ccc; /* opcional: borde */
}


        /* TABLA */
        table { border-collapse: collapse; width: 70%; margin: auto; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        button { padding: 5px 10px; background-color: #4CAF50; border: none; color: white; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .contenedor { display: flex; justify-content: center; margin-top: 20px; }
        .buscador { text-align: center; margin-bottom: 15px; }
        input[type="text"] { padding: 5px; width: 250px; }

        /* MENÚ HORIZONTAL */
        .menu-horizontal {
            text-align: center;
            background-color: #27ae60;
            padding: 10px 0;
        }
        .menu-horizontal a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 15px;
            background-color: #2ecc71;
            border-radius: 5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .menu-horizontal a:hover { background-color: #1e8449; }
        .menu-horizontal i { font-size: 1.2rem; }

        /* BOTÓN HAMBURGUESA */
        .menu-mobile { display: none; text-align: left; padding: 10px; background-color: #27ae60; }
        .menu-toggle i { font-size: 1.5rem; color: white; cursor:pointer; }

        /* MENÚ LATERAL */
        .menu-oculto {
            width: 250px;
            height: 100%;
            background-color: #2ecc71;
            position: fixed;
            top: 0;
            left: -250px;
            transition: left 0.3s;
            padding-top: 60px;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.3);
        }
        .menu-oculto ul { list-style: none; padding: 0; margin: 0; }
        .menu-oculto li { border-bottom: 1px solid rgba(255,255,255,0.2); }
        .menu-oculto li a {
            display: block; color: white; text-decoration: none;
            padding: 15px 20px; font-weight: 600;
        }
        .menu-oculto li a i { margin-right: 10px; }
        .menu-oculto li a:hover { background-color: #27ae60; }
        .menu-visible { left: 0; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .menu-horizontal { display: none; }
            .menu-mobile { display: block; }
        }

    </style>
</head>
<body>

<!-- LOGO DE LA EMPRESA -->
<?php if($logoURL): ?>
<div class="logo-empresa">
    <img src="<?= $logoURL ?>" alt="Logo Empresa">
</div>
<?php else: ?>
<div class="logo-empresa">
    <p style="color:red;">No hay logo disponible</p>
</div>
<?php endif; ?>

<!-- MENÚ HORIZONTAL (desktop) -->
<div class="menu-horizontal">
    <a href="negocioencontrol.php"><i class="fas fa-store"></i> Productos</a>
    <a href="carrito_encontrol.php"><i class="fas fa-shopping-cart"></i> Carrito</a>
    <a href="analisis_ventas.php"><i class="fas fa-chart-bar"></i> Análisis</a>
    <a href="ajustes_encontrol.php"><i class="fas fa-cog"></i> Ajustes</a>
    <a href="almacen_encontrol.php"><i class="fas fa-pallet"></i> Inventario</a>
</div>

<!-- MENÚ MÓVIL -->
<div class="menu-mobile">
    <a href="#" class="menu-toggle"><i class="fas fa-bars"></i></a>
</div>

<!-- MENÚ LATERAL -->
<nav id="menuLateral" class="menu-oculto">
    <ul>
        <li><a href="negocioencontrol.php"><i class="fas fa-store"></i> Productos</a></li>
        <li><a href="carrito_encontrol.php"><i class="fas fa-shopping-cart"></i> Carrito</a></li>
        <li><a href="analisis_ventas.php"><i class="fas fa-chart-bar"></i> Análisis de ventas</a></li>
        <li><a href="ajustes_encontrol.php"><i class="fas fa-cog"></i> Ajustes</a></li>
        <li><a href="#"><i class="fas fa-users"></i> Clientes</a></li>
        <li><a href="#"><i class="fas fa-file-invoice"></i> Pedidos</a></li>
        <li><a href="#"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
    </ul>
</nav>

<h2 style="text-align:center;"> Productos</h2>

<!-- Buscador -->
<div class="buscador">
    <input type="text" id="busqueda" placeholder="Buscar producto...">
</div>

<!-- Tabla -->
<div class="contenedor">
    <table id="tablaProductos">
        <tr>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Acción</th>
        </tr>
    </table>
</div>

<div id="mensaje" style="margin-top:20px; font-weight:bold; color:green; text-align:center;"></div>

<script>
// Cargar productos
function cargarProductos(filtro = "") {
    fetch('buscar_productos.php?busqueda=' + encodeURIComponent(filtro))
    .then(res => res.text())
    .then(html => { document.getElementById('tablaProductos').innerHTML = html; });
}
document.getElementById('busqueda').addEventListener('keyup', function() {
    cargarProductos(this.value);
});
cargarProductos();

// Agregar pedido
function agregarPedido(id, producto, precio) {
    let cantidad = document.getElementById('cantidad_' + id).value;
    let usuario = prompt("Ingrese el nombre del usuario:");
    let delivery = confirm("¿Es delivery?") ? 1 : 0;
    let metodo_pago = prompt("Método de pago:");

    if (!usuario || usuario.trim() === "") { alert("Debe ingresar un usuario."); return; }

    let datos = new FormData();
    datos.append('usuario', usuario);
    datos.append('producto', producto);
    datos.append('cantidad', cantidad);
    datos.append('precio', precio);
    datos.append('delivery', delivery);
    datos.append('metodo_pago', metodo_pago);

    fetch('agregar_pedido.php', { method:'POST', body:datos })
        .then(response => response.text())
        .then(data => { document.getElementById('mensaje').innerHTML = data; })
        .catch(error => { console.error('Error:', error); });
}

// Abrir/cerrar menú lateral
const menuToggle = document.querySelector('.menu-toggle');
const menuLateral = document.getElementById('menuLateral');
menuToggle.addEventListener('click', function(e) {
    e.preventDefault();
    menuLateral.classList.toggle('menu-visible');
});

// Cerrar menú al hacer clic fuera
document.addEventListener('click', function(e){
    if(!menuLateral.contains(e.target) && !menuToggle.contains(e.target)) {
        menuLateral.classList.remove('menu-visible');
    }
});
</script>

</body>
</html>
