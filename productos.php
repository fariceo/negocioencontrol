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
body {font-family: Arial,sans-serif; margin:0; padding:0; background:#f2f2f2;}
#buscador {width:80%; max-width:400px; padding:8px 12px; margin:20px auto; display:block; border-radius:5px; border:1px solid #ccc;}
.container {display:flex; gap:20px; justify-content:center; flex-wrap:wrap; padding-bottom:100px;}
.producto-card {background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.15); padding:15px; width:220px; text-align:center; display:flex; flex-direction:column; align-items:center;}
.producto-card:hover {transform:translateY(-5px); box-shadow:0 5px 15px rgba(0,0,0,0.25);}
.producto-imagen {width:100%; height:160px; object-fit:cover; border-radius:6px; margin-bottom:10px;}
.producto-nombre {font-weight:bold; font-size:1.1em; margin-bottom:8px; color:#2c3e50;}
.producto-precio {color:#16a085; font-weight:600; margin-bottom:10px;}
input.cantidad {width:60px; padding:5px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;}
button.agregar {padding:6px 12px; border:none; background:#27ae60; color:white; border-radius:4px; cursor:pointer; font-weight:bold;}
button.agregar:hover {background:#1e8449;}
#carritoBtn {position:fixed; bottom:20px; right:20px; background:#28a745; color:#fff; padding:10px 15px; border-radius:30px; cursor:pointer; display:flex; align-items:center; box-shadow:0 4px 6px rgba(0,0,0,0.2); z-index:1000;}
#contadorCarrito {margin-left:8px; font-weight:bold;}
#carritoContenedor {display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:75%; max-width:800px; height:75%; max-height:600px; background:#fff; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.4); z-index:1000; flex-direction:column; overflow:hidden; padding:0;}
.carrito-header {background:#f8f9fa; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ddd;}
.carrito-header h4 {margin:0; font-size:1.2em; color:#333;}
.carrito-header button {background:#dc3545; color:#fff; border:none; padding:5px 12px; border-radius:5px; cursor:pointer;}
.carrito-header button:hover {background:#c0392b;}
#itemsCarrito {padding:15px 20px; flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:10px;}
.carrito-item {display:flex; justify-content:space-between; align-items:center; background:#fafafa; padding:10px 15px; border-radius:6px; border:1px solid #eee;}
.carrito-item input {width:50px; border:1px solid #ccc; border-radius:4px; padding:2px 4px;}
.carrito-item button {background:#e74c3c; color:#fff; border:none; padding:3px 8px; border-radius:4px; cursor:pointer;}
.carrito-item button:hover {background:#c0392b;}
#total {font-weight:bold; text-align:right; padding:15px 20px; border-top:1px solid #ddd; background:#f9f9f9;}
#notificacionProducto {position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#27ae60; color:#fff; padding:15px 25px; border-radius:10px; font-weight:bold; font-size:1.2em; opacity:0; pointer-events:none; z-index:2000; transition:all 1s ease-out;}
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
        <h4><?= htmlspecialchars($usuario) ?></h4>
        <button id="cerrarCarrito">X</button>
    </div>

    <div id="itemsCarrito"></div>

    <div id="total">Total: $0.00</div>

    <!-- Cobro -->
    <div id="cobroForm" style="padding:15px; border-top:1px solid #ddd;">
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
                const div = document.createElement('div');
                div.className = 'carrito-item';
                div.innerHTML = `
                    <span>${item.producto} x <input type="number" class="cantItem" data-id="${item.id}" value="${item.cantidad}" min="1"/></span>
                    <span>$${(item.precio*item.cantidad).toFixed(2)}</span>
                    <button class="eliminar" data-id="${item.id}">X</button>
                `;
                itemsCont.appendChild(div);
                total += item.precio*item.cantidad;
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

        // Modificar
        if(e.target.classList.contains('cantItem')){
            const id = parseInt(e.target.dataset.id);
            const cantidad = parseInt(e.target.value);
            if(cantidad<1) return;
            fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=modificar',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id,cantidad})
            }).then(res=>res.json()).then(data=>{ if(data.ok) actualizarCarrito(); });
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
