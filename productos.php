<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>Sesión expirada. Inicia sesión nuevamente.</p>";
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$usuario = $_SESSION['usuario'] ?? 'default_user';

// Consulta productos con stock
$query = $conexion->query("
    SELECT p.id_producto, p.producto, p.precio, p.imagen, b.descripcion, COALESCE(b.cantidad,0) AS stock_actual, p.categoria
    FROM productos p
    LEFT JOIN bodega b ON p.id_producto = b.id_producto
    WHERE COALESCE(b.cantidad,0) > 0
    ORDER BY p.producto ASC
");

// Guardar productos en array
$productos = [];
while($row = $query->fetch_assoc()){
    $productos[] = $row;
}

// Categorías
$categoriasQuery = $conexion->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
$categorias = [];
while($cat = $categoriasQuery->fetch_assoc()){
    $categorias[] = $cat['categoria'];
}
?>

<style>
.modulo-productos-wrapper {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    min-height: 100vh;
    padding-bottom: 100px;
}

#buscadorProductos {
    width: 100%;
    max-width: 400px;
    padding: 10px 14px;
    display: block;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    outline: none;
    transition: .2s ease;
}

#buscadorProductos:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 4px rgba(56,189,248,.15);
}

.buscador-wrapper {
    position: relative;
    width: 80%;
    max-width: 400px;
    margin: 20px auto;
}

.buscador-wrapper i {
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    color:#888;
}

.container-productos {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 10px 12px 100px;
    align-items: stretch;
}

.producto-card {
    background: rgba(255,255,255,0.96);
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(15,23,42,.08);
    padding: 15px;
    min-height: 320px;
    justify-content: space-between;
    overflow: hidden;
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
    height: 300px;
    object-fit: cover;
    border-radius: 14px;
    margin-bottom: 12px;
    display: block;
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

.producto-detalle {
    font-size: 0.92rem;
    color: #475569;
    line-height: 1.45;
    min-height: 65px;
    margin-bottom: 12px;
    padding: 10px 12px;
    text-align: center;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0;
    border: 1px solid rgba(255,255,255,0.35);
    animation: modalFadeIn .25s ease;
}

#carritoContenedor::-webkit-scrollbar {
    width: 10px;
}

#carritoContenedor::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #94a3b8, #64748b);
    border-radius: 20px;
}

.carrito-header {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 5;
}

.carrito-header h4 {
    margin: 0;
    font-size: 1.2em;
    color: #fff;
    font-weight: 700;
}

.carrito-header button {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
}

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
}

.carrito-item .nombre-producto {
    grid-area: nombre;
    font-weight: 700;
    color: #0f172a;
    text-align: left;
    word-break: break-word;
}

.carrito-item .precio-producto {
    grid-area: precio;
    font-weight: 700;
    color: #059669;
}

.carrito-item input {
    grid-area: cantidad;
    width: 70px;
    padding: 8px 6px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    text-align: center;
    background: #f8fafc;
    font-weight: 600;
}

.carrito-item button {
    grid-area: eliminar;
    width: 44px;
    height: 42px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
}

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

#cobroForm {
    padding: 18px 20px !important;
    border-top: 1px solid #ddd;
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
}

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
}

.h3-titulo {
    text-align: center;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin: 24px 0 10px;
}

.h3-titulo::after {
    content: "";
    display: block;
    width: 90px;
    height: 5px;
    margin: 12px auto 0;
    border-radius: 999px;
    background: linear-gradient(90deg, #22c55e, #38bdf8);
}

.categoria-card {
    background: rgba(255,255,255,0.95);
    border-radius: 18px;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    padding: 14px;
    width: 280px;
    text-align: center;
    transition: all .25s ease;
    cursor: pointer;
}

.categoria-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px rgba(15,23,42,.12);
}

.categoria-imagenes img {
    border-radius: 10px;
}

.categoria-imagenes {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: nowrap;
}

.img-categoria {
    width: 82px;
    height: 82px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid #f1f5f9;
    box-shadow: 0 4px 10px rgba(0,0,0,.06);
    transition: transform .2s ease;
}

.categoria-card:hover .img-categoria {
    transform: scale(1.04);
}

#volverCategorias {
    display: inline-block;
    margin: 10px 15px;
    padding: 10px 14px;
    border: none;
    border-radius: 12px;
    background: #64748b;
    color: white;
    cursor: pointer;
    font-weight: bold;
}



.categoria-nombre {
    margin-top: 8px;
    font-weight: 700;
    text-align: center;
    font-size: 0.9rem;
    color: #0f172a;
    line-height: 1.2;
    word-break: break-word;
}


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

@media (min-width: 769px) {
    .categoria-card {
        width: 300px;
        padding: 16px;
    }

    .img-categoria {
        width: 100px;
        height: 100px;
    }

    .categoria-nombre {
        font-size: 1.05rem;
    }
}

@media (max-width: 768px) {
    .producto-card {
        width: 95%;
        min-height: auto;
    }

    .producto-imagen {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

    #carritoContenedor {
        width: 96%;
        max-height: 95vh;
    }

    .h3-titulo {
        font-size: 1.6rem;
    }

    .categoria-card {
        width: calc(33.333% - 10px);
        min-width: unset;
        max-width: unset;
        padding: 8px;
        border-radius: 14px;
    }

    .categoria-imagenes {
        gap: 3px !important;
        justify-content: center;
    }

    .img-categoria {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        object-fit: cover;
    }

    .categoria-nombre {
        font-size: 0.8rem;
        margin-top: 6px;
        line-height: 1.2;
    }
}

</style>

<div class="modulo-productos-wrapper">

    <!-- Buscador -->
    <div class="buscador-wrapper">
        <input type="text" id="buscadorProductos" placeholder="Buscar producto...">
        <i class="fas fa-search"></i>
    </div>

    <!-- Categorías -->
    <div class="container-productos" id="categoriasContainer">
        <?php foreach($categorias as $cat): ?>
            <?php
$productosCat = $conexion->query("
    SELECT imagen 
    FROM productos 
    WHERE categoria = '".$conexion->real_escape_string($cat)."'
      AND imagen IS NOT NULL
      AND imagen != ''
    ORDER BY RAND()
    LIMIT 3
");
?>
            <div class="categoria-card" data-categoria="<?= htmlspecialchars($cat) ?>">
                <div class="categoria-imagenes">
                    <?php while($pimg = $productosCat->fetch_assoc()): ?>
                        <?php $imgSrc = !empty($pimg['imagen']) ? $pimg['imagen'] : "/negocioencontrol/negocios/modulos/assets/default.png"; ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="img-categoria">
                    <?php endwhile; ?>
                </div>
                <div class="categoria-nombre">
    <?= htmlspecialchars($cat) ?>
</div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Productos -->
    <div id="productosSeccion" style="display:none;">
        <button id="volverCategorias">← Volver a Categorías</button>

        <div class="container-productos" id="productosContainer">
            <?php foreach($productos as $p): ?>
                <div class="producto-card producto-item" data-categoria="<?= htmlspecialchars($p['categoria']) ?>" style="display:none;">
                    <?php $imgSrc = !empty($p['imagen']) ? $p['imagen'] : "/negocioencontrol/negocios/modulos/assets/default.png"; ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="producto-imagen">                
                    <div class="producto-nombre"><?= htmlspecialchars($p['producto']) ?></div>
                    <div class="producto-detalle">
                        <?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción disponible') ?>
                    </div>
                    <div class="producto-precio">$<?= number_format($p['precio'],2) ?></div>
                    <input type="number" class="cantidad" value="1" min="1">
                    <button class="agregar"
                            data-id="<?= $p['id_producto'] ?>"
                            data-producto="<?= htmlspecialchars($p['producto']) ?>"
                            data-precio="<?= $p['precio'] ?>">
                        Agregar
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Botón carrito -->
    <div id="carritoBtn">🛒 <span id="contadorCarrito">0</span></div>

    <!-- Modal carrito -->
    <div id="carritoContenedor">
        <div class="carrito-header">
            <h4>Vendedor: <?= htmlspecialchars($usuario) ?></h4>
            <button id="cerrarCarrito">X</button>
        </div>

        <h3 class="h3-titulo">Productos</h3>
        <div id="itemsCarrito"></div>
        <div id="total">Total: $0.00</div>

        <h3 class="h3-titulo">Datos de la compra</h3>
        <div id="cobroForm">
            <label>Nombre del cliente:</label>
            <input type="text" id="nombreCliente" placeholder="Ej: Juan Pérez">

            <label>Tipo de identificación:</label>
            <select id="tipoIdentificacion">
                <option value="05">Cédula</option>
                <option value="04">RUC</option>
                <option value="06">Pasaporte</option>
                <option value="07">Consumidor Final</option>
            </select>

            <label>Número de identificación:</label>
            <input type="text" id="identificacionCliente" placeholder="Ej: 0912345678">

            <label>Dirección (opcional):</label>
            <input type="text" id="direccionCliente" placeholder="Ej: Av. Principal y Calle 2">

            <label>Teléfono (opcional):</label>
            <input type="text" id="telefonoCliente" placeholder="Ej: 0999999999">

            <label>Correo (opcional):</label>
            <input type="email" id="correoCliente" placeholder="cliente@correo.com">

            <div style="margin:8px 0;">
                <label><input type="radio" name="metodoPago" value="efectivo" checked> Efectivo</label>
                <label style="margin-left:15px;"><input type="radio" name="metodoPago" value="transferencia"> Transferencia</label>
                <label style="margin-left:15px;"><input type="radio" name="metodoPago" value="credito"> Crédito</label>
            </div>

            <button id="btnCobrar">💰 Cobrar</button>
        </div>
    </div>

    <div id="notificacionProducto"></div>
</div>

<script>
(function(){
    const wrapper = document.querySelector('.modulo-productos-wrapper');
    if (!wrapper) return;

    const carritoBtn = wrapper.querySelector('#carritoBtn');
    const carritoCont = wrapper.querySelector('#carritoContenedor');
    const cerrarBtn = wrapper.querySelector('#cerrarCarrito');
    const contador = wrapper.querySelector('#contadorCarrito');
    const itemsCont = wrapper.querySelector('#itemsCarrito');
    const totalDiv = wrapper.querySelector('#total');
    const noti = wrapper.querySelector('#notificacionProducto');

    const categoriasContainer = wrapper.querySelector('#categoriasContainer');
    const productosSeccion = wrapper.querySelector('#productosSeccion');
    const volverBtn = wrapper.querySelector('#volverCategorias');
    const buscador = wrapper.querySelector('#buscadorProductos');

    function actualizarCarrito(){
        fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=cargar')
        .then(res => res.json())
        .then(data => {
            let total = 0;
            itemsCont.innerHTML = '';

            if(!Array.isArray(data) || data.length === 0){
                itemsCont.innerHTML = `<div style="text-align:center;color:#999;">🛒 Tu carrito está vacío</div>`;
                contador.textContent = 0;
                totalDiv.textContent = 'Total: $0.00';
                return;
            }

            data.forEach(item => {
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
        })
        .catch(err => {
            console.error('Error cargando carrito:', err);
        });
    }

    if (carritoBtn) {
        carritoBtn.addEventListener('click', function(){
            carritoCont.style.display = 'flex';
        });
    }

    if (cerrarBtn) {
        cerrarBtn.addEventListener('click', function(){
            carritoCont.style.display = 'none';
        });
    }

    wrapper.addEventListener('click', function(e){

        // =========================
        // AGREGAR AL CARRITO
        // =========================
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
            .then(res => res.json())
            .then(data => {
                if(data.ok){
                    actualizarCarrito();
                    noti.textContent = `"${producto}" agregado al carrito`;
                    noti.style.opacity = 1;
                    setTimeout(() => noti.style.opacity = 0, 1200);
                }
            })
            .catch(err => {
                console.error('Error agregando producto:', err);
            });
            return;
        }

        // =========================
        // ELIMINAR DEL CARRITO
        // =========================
        if(e.target.classList.contains('eliminar')){
            const id = parseInt(e.target.dataset.id);
            fetch('/negocioencontrol/negocios/modulos/cargar_carrito.php?accion=eliminar',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id})
            })
            .then(res => res.json())
            .then(data => {
                if(data.ok) actualizarCarrito();
            })
            .catch(err => {
                console.error('Error eliminando producto:', err);
            });
            return;
        }

        // =========================
        // CLICK EN CATEGORÍA (CORREGIDO)
        // =========================
        const categoriaCard = e.target.closest('.categoria-card');
        if(categoriaCard && wrapper.contains(categoriaCard)){
            const categoria = categoriaCard.dataset.categoria;

            categoriasContainer.style.display = 'none';
            productosSeccion.style.display = 'block';

            wrapper.querySelectorAll('.producto-item').forEach(p => {
                p.style.display = (p.dataset.categoria === categoria) ? 'flex' : 'none';
            });

            return;
        }

        // =========================
        // VOLVER A CATEGORÍAS
        // =========================
        if(e.target.id === 'volverCategorias'){
            productosSeccion.style.display = 'none';
            categoriasContainer.style.display = 'flex';
            if (buscador) buscador.value = '';
            return;
        }
    });

    wrapper.addEventListener('change', function(e){
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
            })
            .catch(err => {
                console.error('Error modificando cantidad:', err);
            });
        }
    });

    const btnCobrar = wrapper.querySelector('#btnCobrar');
    if(btnCobrar){
        btnCobrar.addEventListener('click', ()=>{
            const nombreCliente = wrapper.querySelector('#nombreCliente').value.trim();
            const tipo_identificacion = wrapper.querySelector('#tipoIdentificacion')?.value || '07';
            const identificacion = wrapper.querySelector('#identificacionCliente')?.value.trim() || '';
            const correo = wrapper.querySelector('#correoCliente').value.trim();
            const direccion = wrapper.querySelector('#direccionCliente')?.value.trim() || '';
            const telefono = wrapper.querySelector('#telefonoCliente')?.value.trim() || '';
            const metodo_pago = wrapper.querySelector('input[name="metodoPago"]:checked').value;

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
                                if(respFactura.ok){
                                    alert(`🧾 Factura registrada correctamente
Factura ID: ${respFactura.id_factura}
Código: ${respFactura.codigo_compra}
Total: $${respFactura.total}
Estado SRI: pendiente
Ambiente: pruebas`);
                                } else {
                                    alert("⚠️ No se pudo registrar la factura:\n" + (respFactura.mensaje || 'Error desconocido'));
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                alert("Error al registrar la factura SRI");
                            });
                        }

                        actualizarCarrito();
                        carritoCont.style.display = 'none';

                        wrapper.querySelector('#nombreCliente').value = '';
                        wrapper.querySelector('#correoCliente').value = '';
                        wrapper.querySelector('#identificacionCliente').value = '';
                        wrapper.querySelector('#direccionCliente').value = '';
                        wrapper.querySelector('#telefonoCliente').value = '';

                    } else {
                        alert("⚠️ " + data.mensaje);
                    }
                })
                .catch(err=>{
                    console.error(err);
                    alert("Error al procesar la venta");
                });
            });
        });
    }

    if(buscador){
        buscador.addEventListener('input', ()=>{
            const filtro = buscador.value.toLowerCase();

            if(filtro.trim() !== ''){
                categoriasContainer.style.display = 'none';
                productosSeccion.style.display = 'block';
            }

            wrapper.querySelectorAll('.producto-item').forEach(card=>{
                const nombre = card.querySelector('.producto-nombre').textContent.toLowerCase();
                card.style.display = nombre.includes(filtro) ? 'flex' : 'none';
            });

            if(filtro.trim() === ''){
                productosSeccion.style.display = 'none';
                categoriasContainer.style.display = 'flex';
            }
        });
    }

    actualizarCarrito();
})();
</script>