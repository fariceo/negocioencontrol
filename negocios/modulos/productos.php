<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>Sesión expirada. Inicia sesión nuevamente.</p>";
    exit;
}
//
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'] ?? 'default_user';

// Consulta productos con stock
$query = $conexion->query("
    SELECT p.id_producto, p.producto, p.precio, p.imagen, COALESCE(b.cantidad,0) AS stock_actual
    FROM productos p
    LEFT JOIN bodega b ON p.id_producto = b.id_producto
    WHERE COALESCE(b.cantidad,0) > 0
    ORDER BY p.producto ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

#buscador {
    width: 80%;
    max-width: 400px;
    padding: 10px 14px;
    margin: 20px auto;
    display: block;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    outline: none;
    transition: .2s ease;
}

#buscador:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 4px rgba(56,189,248,.15);
}

.container {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 10px 15px 100px;
    align-items: stretch; /* NUEVO */
}

.producto-card {
    background: rgba(255,255,255,0.96);
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(15,23,42,.08);
    padding: 15px;
     min-height: 360px; /* NUEVO */
     justify-content: space-between; /* NUEVO */
     overflow: hidden; /* NUEVO */
    width: 220px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 1px solid #e5e7eb;
    transition: all .25s ease;
}

.producto-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 35px rgba(15,23,42,.14);
}

.producto-imagen {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 14px;
    margin-bottom: 12px;
    display: block;
    transition: transform .25s ease;
}

.producto-nombre {
    font-weight: 700;
    font-size: 1.05rem;
    color: #0f172a;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    line-height: 1.3;
    margin-bottom: 8px;
    word-break: break-word;
}

.producto-precio {
    color: #059669;
    font-weight: 800;
    font-size: 1.08rem;
    margin-bottom: 12px;
}

input.cantidad {
    width: 90px;
    padding: 9px 10px;
    margin-bottom: 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    text-align: center;
    background: #f8fafc;
    font-weight: 600;
    outline: none;
    transition: .2s ease;
}

input.cantidad:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 4px rgba(56,189,248,.15);
    background: #fff;
}


button.agregar {
    width: 100%;
    padding: 10px 14px;
    border: none;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: white;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    transition: .2s ease;
    box-shadow: 0 6px 14px rgba(34,197,94,.25);
    margin-top: auto;
}

button.agregar:hover {
    background: linear-gradient(135deg, #15803d, #16a34a);
    transform: translateY(-1px);
}
button.agregar:active {
    transform: scale(.98);
}

#carritoBtn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: #fff;
    padding: 12px 18px;
    border-radius: 999px;
    cursor: pointer;
    display: flex;
    align-items: center;
    box-shadow: 0 10px 25px rgba(34,197,94,.35);
    z-index: 1000;
    font-weight: bold;
    transition: .2s ease;
}

#carritoBtn:hover {
    transform: translateY(-2px) scale(1.02);
}

#contadorCarrito {
    margin-left: 8px;
    font-weight: bold;
}

/* =========================
   MODAL GENERAL BONITO
========================= */
#carritoContenedor {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 92%;
    max-width: 820px;
    max-height: 92vh;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 22px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.30);
    z-index: 1000;
    flex-direction: column;
    overflow-y: auto;   /* scroll general del modal */
    overflow-x: hidden;
    padding: 0;
    border: 1px solid rgba(255,255,255,0.35);
    backdrop-filter: blur(4px);
    animation: modalFadeIn .25s ease;
}

/* Scroll bonito del modal */
#carritoContenedor::-webkit-scrollbar {
    width: 10px;
}

#carritoContenedor::-webkit-scrollbar-track {
    background: transparent;
}

#carritoContenedor::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #94a3b8, #64748b);
    border-radius: 20px;
}

/* Header elegante */
.carrito-header {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: sticky;
    top: 0;
    z-index: 5;
}

.carrito-header h4 {
    margin: 0;
    font-size: 1.2em;
    color: #fff;
    font-weight: 700;
    letter-spacing: .3px;
}

.carrito-header button {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    transition: .2s ease;
}

.carrito-header button:hover {
    background: rgba(255,255,255,0.22);
    transform: scale(1.05);
}

/* Lista carrito */
#itemsCarrito {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px 20px;
}

.carrito-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    grid-template-areas:
        "nombre nombre nombre"
        "precio cantidad eliminar";
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.95);
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    transition: .2s ease;
}

.carrito-item .nombre-producto {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.4;
    text-align: left;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}

.carrito-item .precio-producto {
    font-weight: 700;
    color: #059669;
    text-align: center;
    white-space: nowrap;
}

.carrito-item input {
    width: 70px;
    padding: 8px 6px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    text-align: center;
    background: #f8fafc;
    font-weight: 600;
    justify-self: center;
    outline: none;
}

.carrito-item input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 4px rgba(56,189,248,.15);
    background: #fff;
}

.carrito-item button {
    width: 85px;
    padding: 9px 10px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    justify-self: center;
    transition: .2s ease;
}

.carrito-item button:hover {
    transform: scale(1.05);
}

/* Total */
#total {
    font-weight: bold;
    text-align: right;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    background: rgba(255,255,255,0.95);
    font-size: 1.08rem;
    color: #0f172a;
}

/* Formulario */
#cobroForm {
    padding: 18px 20px !important;
    border-top: 1px solid #ddd;
    overflow: visible !important;
    background: #fff;
}

#cobroForm label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #334155;
}

#cobroForm input,
#cobroForm select {
    width: 100%;
    padding: 10px 12px !important;
    margin-bottom: 10px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 12px !important;
    background: #f8fafc;
    box-sizing: border-box;
    outline: none;
    transition: .2s ease;
}

#cobroForm input:focus,
#cobroForm select:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 4px rgba(56,189,248,.15);
    background: #fff;
}

#cobroForm input[type="radio"] {
    width: auto !important;
    margin-right: 5px;
}

#btnCobrar {
    width: 100% !important;
    background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
    color: #fff !important;
    border: none !important;
    padding: 12px !important;
    border-radius: 14px !important;
    font-weight: bold !important;
    cursor: pointer !important;
    font-size: 15px !important;
    margin-top: 10px;
    box-shadow: 0 10px 20px rgba(59,130,246,.22);
    transition: .2s ease;
}

#btnCobrar:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, #1d4ed8, #2563eb) !important;
}

/* Notificación */
#notificacionProducto {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: #fff;
    padding: 16px 28px;
    border-radius: 16px;
    font-weight: bold;
    font-size: 1.1em;
    opacity: 0;
    pointer-events: none;
    z-index: 2000;
    transition: all .6s ease-out;
    box-shadow: 0 15px 35px rgba(34,197,94,.35);
}

/* Animación modal */
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translate(-50%, -48%) scale(.96);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

/* Responsive */
@media (max-width: 768px) {
    #buscador {
        width: 90%;
    }

    
    .producto-card {
    background: rgba(255,255,255,0.96);
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(15,23,42,.08);
    padding: 16px;
    width: 220px;
    min-height: 360px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #e5e7eb;
    transition: all .25s ease;
    overflow: hidden;
}

    .producto-imagen {
        height: 130px;
    }

    #carritoContenedor {
        width: 96%;
        max-height: 95vh;
        border-radius: 20px;
    }

    .carrito-header {
        padding: 16px 18px;
    }

    .carrito-header h4 {
        font-size: 1.05em;
    }
.carrito-item .nombre-producto {
    width: 100%;
     text-align: left;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}
   #itemsCarrito {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    overflow: visible;
    flex: unset;
    max-height: none;
    background:
        radial-gradient(circle at top right, rgba(56,189,248,.07), transparent 240px),
        radial-gradient(circle at bottom left, rgba(34,197,94,.06), transparent 240px),
        transparent;
}

    .carrito-item {
    display: grid; /* CAMBIO IMPORTANTE */
    grid-template-columns: 1.8fr 90px 110px 90px; /* simétrico */
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.95);
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    transition: .2s ease;
}

    #total {
        text-align: left;
    }

    #cobroForm {
        padding: 15px !important;
    }

    #carritoBtn {
        bottom: 15px;
        right: 15px;
        padding: 11px 15px;
        font-size: 14px;
    }

    .carrito-item .nombre-producto {
    grid-area: nombre;
    width: 100%;
    text-align: left;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: break-word;
    font-size: 1rem;
    margin-bottom: 2px;
}

.carrito-item .precio-producto {
    grid-area: precio;
    text-align: left;
    font-size: 1rem;
    font-weight: 800;
}

.carrito-item input {
    grid-area: cantidad;
    width: 70px;
    justify-self: center;
}

.carrito-item button {
    grid-area: eliminar;
    width: 44px;
    height: 42px;
    padding: 0;
    border-radius: 12px;
    justify-self: end;
}
}

@media (max-width: 480px) {
    .producto-card {
        width: 85%;
    }
}
</style>

<style>

    .h3-titulo {
    text-align: center;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin: 24px 0 10px;
    position: relative;
    letter-spacing: 0.5px;
}

.h3-titulo::after {
    content: "";
    display: block;
    width: 90px;
    height: 5px;
    margin: 12px auto 0;
    border-radius: 999px;
    background: linear-gradient(90deg, #22c55e, #38bdf8);
    box-shadow: 0 4px 12px rgba(34,197,94,0.25);
}

@media (max-width: 768px) {
    .h3-titulo {
        font-size: 1.6rem;
        margin: 20px 0 8px;
    }

    .h3-titulo::after {
        width: 70px;
        height: 4px;
    }
}
</style>
</head>
<body>

<!-- Buscador -->
<div style="position: relative; width: 80%; max-width: 400px; margin: 20px auto;">
    <input type="text" id="buscador" placeholder="Buscar producto...">
    <i class="fas fa-search" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#888;"></i>
</div>

<!-- Productos -->
<div class="container" id="productosContainer">
<?php while($p = $query->fetch_assoc()): ?>
<div class="producto-card">
    <?php $imgSrc = !empty($p['imagen']) ? $p['imagen'] : "/negocioencontrol/negocios/modulos/assets/default.png"; ?>
    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['producto']) ?>" class="producto-imagen">
    <div class="producto-nombre"><?= htmlspecialchars($p['producto']) ?></div>
    <div class="producto-precio">$<?= number_format($p['precio'],2) ?></div>
    <input type="number" class="cantidad" value="1" min="1">
    <button class="agregar" data-id="<?= $p['id_producto'] ?>" data-producto="<?= htmlspecialchars($p['producto']) ?>" data-precio="<?= $p['precio'] ?>">Agregar</button>
</div>
<?php endwhile; ?>
</div>

<!-- Botón carrito -->
<div id="carritoBtn">🛒 <span id="contadorCarrito">0</span></div>


<!-- Carrito -->

<div id="carritoContenedor">

    <div class="carrito-header">
        <h4>Vendedor: <?= htmlspecialchars($usuario) ?></h4>
        <button id="cerrarCarrito">X</button>
    </div>
         <h3 class="h3-titulo">Productos</h3>

    <div id="itemsCarrito"></div>

    <div id="total">Total: $0.00</div>

    <!-- Cobro -->
     <h3 class="h3-titulo">Datos de la compra</h3>
    <div id="cobroForm" style="padding: 15px 20px !important;
    border-top: 1px solid #ddd;
    overflow: visible !important;">
        <label>Nombre del cliente:</label>
        <input type="text" id="nombreCliente" placeholder="Ej: Juan Pérez" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">
        <label>Tipo de identificación:</label>
        <select id="tipoIdentificacion" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">
        <option value="05">Cédula</option>
        <option value="04">RUC</option>
        <option value="06">Pasaporte</option>
        <option value="07">Consumidor Final</option>
        </select>

<label>Número de identificación:</label>
<input type="text" id="identificacionCliente" placeholder="Ej: 0912345678" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">

<label>Dirección (opcional):</label>
<input type="text" id="direccionCliente" placeholder="Ej: Av. Principal y Calle 2" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">

<label>Teléfono (opcional):</label>
<input type="text" id="telefonoCliente" placeholder="Ej: 0999999999" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">
        <label>Correo (opcional):</label>
        <input type="email" id="correoCliente" placeholder="cliente@correo.com" style="width:100%; padding:6px; margin-bottom:8px; border:1px solid #ccc; border-radius:4px;">

        <div style="margin:8px 0;">
            <label><input type="radio" name="metodoPago" value="efectivo" checked> Efectivo</label>
            <label style="margin-left:15px;"><input type="radio" name="metodoPago" value="transferencia"> Transferencia</label>
            <label><input type="radio" name="metodoPago" value="credito" > Credito</label>

        </div>


        <button id="btnCobrar" style="width:100%; background:#007bff; color:#fff; border:none; padding:10px; border-radius:6px; font-weight:bold; cursor:pointer;">💰 Cobrar</button>
    </div>
</div>

<div id="notificacionProducto"></div>

<script>
(function(){
    const carritoBtn = document.getElementById('carritoBtn');
    const carritoCont = document.getElementById('carritoContenedor');
    const cerrarBtn = document.getElementById('cerrarCarrito');
    const contador = document.getElementById('contadorCarrito');
    const itemsCont = document.getElementById('itemsCarrito');
    const totalDiv = document.getElementById('total');
    const noti = document.getElementById('notificacionProducto');
    const usuario = "<?= $usuario ?>";

    function actualizarCarrito(){
        fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=cargar')
        .then(res=>res.json())
        .then(data=>{
            let total = 0;
            itemsCont.innerHTML = '';
            if(!Array.isArray(data) || data.length===0){
                itemsCont.innerHTML = `<div style="text-align:center;color:#999;">🛒 Tu carrito está vacío</div>`;
                contador.textContent = 0;
                totalDiv.textContent = 'Total: $0.00';
                return;
            }
           data.forEach(item=>{
    const subtotal = item.precio * item.cantidad;

    const div = document.createElement('div');
    div.className = 'carrito-item';
    div.innerHTML = `
        <div class="nombre-producto">${item.producto}</div>
        <div class="precio-producto">$${subtotal.toFixed(2)}</div>
        <input type="number" class="cantItem" data-id="${item.id}" value="${item.cantidad}" min="1" inputmode="numeric"/>
        <button class="eliminar" data-id="${item.id}">X</button>
    `;
    itemsCont.appendChild(div);
    total += subtotal;
});
            contador.textContent = data.length;
            totalDiv.textContent = 'Total: $' + total.toFixed(2);
        });
    }

    carritoBtn.onclick = ()=> carritoCont.style.display='flex';
    cerrarBtn.onclick = ()=> carritoCont.style.display='none';

    document.body.addEventListener('click', e=>{
        // Agregar
        if(e.target.classList.contains('agregar')){
            const card = e.target.closest('.producto-card');
            const id_producto = parseInt(e.target.dataset.id);
            const producto = e.target.dataset.producto;
            const precio = parseFloat(e.target.dataset.precio);
            const cantidad = parseInt(card.querySelector('.cantidad').value);

            fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=agregar',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id_producto, producto, precio, cantidad})
            })
            .then(res=>res.json())
            .then(data=>{
                if(data.ok){
                    actualizarCarrito();
                    noti.textContent = `"${producto}" agregado al carrito`;
                    noti.style.opacity = 1;
                    setTimeout(()=>noti.style.opacity=0,1200);
                }
            });
        }

        // Eliminar
        if(e.target.classList.contains('eliminar')){
            const id = parseInt(e.target.dataset.id);
            fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=eliminar',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id})
            }).then(res=>res.json()).then(data=>{ if(data.ok) actualizarCarrito(); });
        }
    });

    document.body.addEventListener('change', e => {
    if(e.target.classList.contains('cantItem')){
        const id = parseInt(e.target.dataset.id);
        const cantidad = parseInt(e.target.value);

        if(isNaN(cantidad) || cantidad < 1){
            e.target.value = 1;
            return;
        }

        fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=modificar', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id, cantidad})
        })
        .then(res => res.json())
        .then(data => {
            if(data.ok) actualizarCarrito();
        });
    }
});

   // Cobrar
document.getElementById('btnCobrar').addEventListener('click', ()=>{
    const nombreCliente = document.getElementById('nombreCliente').value.trim();
    const tipo_identificacion = document.getElementById('tipoIdentificacion')?.value || '07';
    const identificacion = document.getElementById('identificacionCliente')?.value.trim() || '';
    const correo = document.getElementById('correoCliente').value.trim();
    const direccion = document.getElementById('direccionCliente')?.value.trim() || '';
    const telefono = document.getElementById('telefonoCliente')?.value.trim() || '';
    const metodo_pago = document.querySelector('input[name="metodoPago"]:checked').value;

    if(!nombreCliente){
        alert('Ingrese nombre del cliente');
        return;
    }

    if(tipo_identificacion !== '07' && !identificacion){
        alert('Ingrese la identificación del cliente');
        return;
    }

    fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=cargar')
    .then(res=>res.json())
    .then(carrito=>{
        if(!Array.isArray(carrito) || carrito.length===0){
            alert('El carrito está vacío');
            return;
        }

        const formData = new FormData();
        formData.append('nombreCliente', nombreCliente);
        formData.append('correo', correo);
        formData.append('telefono', telefono);
        formData.append('direccion', direccion);
        formData.append('metodo_pago', metodo_pago);
        formData.append('carrito', JSON.stringify(carrito));

        fetch('/negocioencontrol/negocios/modulos/cobrar_carrito.php', {
            method:'POST',
            body: formData
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.ok){
                const idVenta = data.id_venta;

                const emitir = confirm(`✅ Venta registrada\nID Venta: ${idVenta}\n\n¿Desea registrar factura para el SRI?`);

                if(emitir){
                    const formFactura = new FormData();
                    formFactura.append('id_venta', idVenta);
                    formFactura.append('cliente_nombre', nombreCliente);
                    formFactura.append('cliente_identificacion', identificacion);
                    formFactura.append('tipo_identificacion', tipo_identificacion);
                    formFactura.append('correo', correo);
                    formFactura.append('direccion', direccion);
                    formFactura.append('telefono', telefono);
                    formFactura.append('metodo_pago', metodo_pago);

                    fetch('/negocioencontrol/negocios/modulos/factura_sri.php', {
                        method: 'POST',
                        body: formFactura
                    })
                    .then(res => res.json())
                    .then(respFactura => {
    console.log("RESPUESTA FACTURA SRI:", respFactura);

    if(respFactura.ok){
alert(
`🧾 Factura registrada correctamente
Factura ID: ${respFactura.id_factura}
Código: ${respFactura.codigo_compra}
Total: $${respFactura.total}
Estado SRI: pendiente
Ambiente: pruebas`
);    } else {
        alert(
            "⚠️ No se pudo registrar la factura:\n" +
            (respFactura.mensaje || 'Error desconocido') +
            (respFactura.error ? "\n\nDetalle técnico:\n" + respFactura.error : "") +
            (respFactura.debug ? "\n\nDebug:\n" + JSON.stringify(respFactura.debug, null, 2) : "")
        );
    }
})
.catch(err => {
    console.error("ERROR FETCH FACTURA:", err);
    alert("Error al registrar la factura SRI");
});
                }

                actualizarCarrito();
                document.getElementById('nombreCliente').value = '';
                document.getElementById('correoCliente').value = '';

                if(document.getElementById('identificacionCliente')){
                    document.getElementById('identificacionCliente').value = '';
                }
                if(document.getElementById('direccionCliente')){
                    document.getElementById('direccionCliente').value = '';
                }
                if(document.getElementById('telefonoCliente')){
                    document.getElementById('telefonoCliente').value = '';
                }

            }else{
                alert("⚠️ " + data.mensaje);
            }
        })
        .catch(err=>{
            console.error(err);
            alert("Error al procesar la venta");
        });
    });
});
    // Buscador
    document.getElementById('buscador').addEventListener('input', ()=>{
        const filtro = document.getElementById('buscador').value.toLowerCase();
        document.querySelectorAll('.producto-card').forEach(card=>{
            const nombre = card.querySelector('.producto-nombre').textContent.toLowerCase();
            card.style.display = nombre.includes(filtro) ? 'flex' : 'none';
        });
    });

    actualizarCarrito();
})();
</script>
</body>
</html>
