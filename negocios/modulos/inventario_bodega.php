<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>");
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];

$defaultImage = "/negocioencontrol/negocios/modulos/assets/default.png";

/* =========================
   CONSULTA ACTUALIZADA
   ========================= */
$stmt = $conexion->prepare("
    SELECT 
        b.id,
        b.id_producto,
        p.producto,
        p.codigo_barra,
        b.descripcion,
        b.cantidad,
        b.precio,
        b.categoria,
        b.stock_inicial,
        b.fecha_registro,
        COALESCE(p.imagen,'') AS img
    FROM bodega b
    INNER JOIN productos p ON p.id_producto = b.id_producto
    WHERE b.negocio = ?
    ORDER BY p.producto ASC
");
$stmt->bind_param("s", $negocio);
$stmt->execute();
$query = $stmt->get_result();
?>

<style>
  .row-card td {
    padding: 0;
    border: none;
    background: transparent;
}

.card-img {
    width: 100%;
    overflow: hidden;
    border-radius: 18px;
    margin: 10px 0 6px 0;
    background: #fff;
    box-shadow: 0 8px 22px rgba(0,0,0,.08);
    border: 1px solid #ececec;
}

.card-img img {
    width: 100%;
    height: 420px;
    object-fit: cover;
    display: block;
    cursor: pointer;
    transition: transform .25s ease;
}

.card-img img:hover {
    transform: scale(1.02);
}
/* tabla limpia */
table {
    border-collapse: collapse;
    width: 100%;
}

td, th {
    padding: 10px 12px;
    font-size: 0.95em;
    border-bottom: 1px solid #e8e8e8;
}

tr[data-id] td {
    background: #fff;
    font-size: 15px;
}

tr[data-id] td:nth-child(2) {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
}

.action-row td {
    background: #fafafa;
    padding: 5px;
}

</style>
<style>
#bodega_wrap * { box-sizing:border-box; }
#bodega_wrap { font-family:Arial; background:#f7f7f7; padding:10px; }
#bodega_wrap header { background:#34495e; color:white; padding:12px; text-align:center; font-size:20px; font-weight:bold; border-radius:6px; margin-bottom:10px; }
#bodega_wrap .toolbar { display:flex; gap:10px; justify-content:center; margin-bottom:15px; }
#bodega_wrap .btn { padding:10px 15px; border:none; border-radius:6px; cursor:pointer; background:#3498db; color:white; transition:.2s; font-size:15px; }
#bodega_wrap .btn:hover { background:#2980b9; }
#bodega_wrap table { width:100%; border-collapse:collapse; background:white; border-radius:6px; overflow:hidden; }
#bodega_wrap th, #bodega_wrap td { padding:10px; border:1px solid #ddd; text-align:center; }
#bodega_wrap th { background:#eee; }
#bodega_wrap .img-card { width:70px; height:70px; object-fit:cover; border-radius:10px; cursor:pointer; }
#bodega_wrap .action-row { background:#fafafa; }
#modalForm_bodega { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.6); display:none; justify-content:center; align-items:center; z-index:9999; }
#formBox_bodega { background:white; padding:20px; width:95%; max-width:450px; border-radius:8px; }
#formBox_bodega input, #formBox_bodega button { width:100%; padding:10px; margin:6px 0; border-radius:5px; border:1px solid #ccc; }
#searchInput { margin-bottom:10px; padding:8px; width:100%; border-radius:5px; border:1px solid #ccc; }

/* =========================
   MODAL PROFESIONAL INVENTARIO
   ========================= */
/* =========================
   MODAL PROFESIONAL INVENTARIO
   ========================= */
#img_modal_overlay{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.82);
    display: none;
    justify-content: center;
    align-items: flex-start; /* antes center */
    z-index: 99999;
    padding: 25px 20px;
    overflow-y: auto;
}

#img_modal_box{
    width: 100%;
    max-width: 980px;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 15px 50px rgba(0,0,0,.35);
    display: flex;
    flex-direction: column;
    animation: modalFadeIn .25s ease;
    position: relative;
    margin: 0 auto;
}

/* IMAGEN ARRIBA */
#img_modal_top{
    width: 100%;
    height: 430px; /* altura fija real del bloque negro */
    background: #000;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
    padding: 0;
}
#img_modal_top::before{
    content: "";
    position: absolute;
    inset: 0;
    background: #000;
    z-index: 1;
}

/* IMAGEN PRINCIPAL */
#img_modal{
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    margin: auto;
    background: transparent;
    border-radius: 0;
    position: relative;
    z-index: 2;
}

/* DETALLES ABAJO */
#img_modal_footer{
    padding: 24px;
    background: #ffffff;
    overflow: visible;
    flex: unset;
    min-height: auto;
}

.modal-header-row{
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.modal-header-row h2{
    margin: 0;
    font-size: 26px;
    color: #2c3e50;
}

.modal-header-row small{
    color: #777;
    display: block;
    margin-top: 4px;
    font-size: 13px;
}

.modal-grid-detalles{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

.detalle-card{
    background: #f8f9fb;
    border: 1px solid #e8e8e8;
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,.03);
}

.detalle-card strong{
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-size: 14px;
}

.detalle-card div{
    color: #444;
    line-height: 1.6;
    font-size: 15px;
    white-space: pre-wrap;
    word-break: break-word;
}

.barcode-box{
    background: #fff;
    border: 1px dashed #ccc;
    border-radius: 14px;
    padding: 16px;
    text-align: center;
}

.barcode-box strong{
    display: block;
    margin-bottom: 10px;
    color: #333;
}

#barcode_svg{
    max-width: 100%;
    height: 80px;
}

#cerrar_img_modal{
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,.75);
    color: #fff;
    border: none;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    z-index: 10;
}

#cerrar_img_modal:hover{
    background: #e74c3c;
}

.btn-edit-modal{
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.btn-edit-modal:hover{
    background: #2980b9;
}

@keyframes modalFadeIn{
    from{
        opacity: 0;
        transform: translateY(10px) scale(.98);
    }
    to{
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media(max-width:768px){
    #img_modal_overlay{
        padding: 10px;
        align-items: flex-start;
    }

    #img_modal_box{
        border-radius: 14px;
        margin-top: 10px;
    }

    #img_modal_top{
        height: 260px;
        padding: 0;
    }

    #img_modal{
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
    }

    #img_modal_footer{
        padding: 16px;
    }

    .modal-header-row{
        flex-direction: column;
        align-items: flex-start;
    }

    .modal-header-row h2{
        font-size: 21px;
    }

    .detalle-card{
        padding: 12px;
    }

    .modal-grid-detalles{
        grid-template-columns: 1fr;
    }

    .btn-edit-modal{
        width: 100%;
        text-align: center;
    }
}

#img_modal_top{
    width: 100%;
    height: 430px;
    background: #000;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
    padding: 0;
}

#img_modal_top::before{
    content: "";
    position: absolute;
    inset: 0;
    background: #000;
    z-index: 1;
}

#img_modal{
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    margin: auto;
    background: transparent;
    border-radius: 0;
    position: relative;
    z-index: 2;
}
</style>

<div id="bodega_wrap">
<header>📦 Inventario de Bodega</header>

<input type="text" id="searchInput" placeholder="Buscar producto...">

<div class="toolbar">
    <button class="btn" id="btnNuevo">➕ Nuevo Producto</button>
</div>
<div id="modalForm_bodega">
<div id="formBox_bodega">
<button style="background:#e74c3c;color:white;border:none;border-radius:5px;padding:8px 12px;" id="btnCerrar">Cerrar ✖</button>

<form id="formProducto_bodega" enctype="multipart/form-data">
    <input type="hidden" name="id" id="idProducto_bodega">
    <input type="hidden" name="id_producto" id="id_producto">

    <!-- IMAGEN ARRIBA (MISMO ID / NAME) -->
    <label for="imagen_bodega"><strong>Imagen del producto:</strong></label>
    <input type="file" name="imagen" id="imagen_bodega" accept="image/*">

    <label for="producto_bodega"><strong>Nombre del producto:</strong></label>
    <input type="text" name="producto" id="producto_bodega" placeholder="Ej: Arroz">

    <label for="descripcion_bodega"><strong>Descripción del producto:</strong></label>
    <input type="text" name="descripcion" id="descripcion_bodega" placeholder="Ej: Arroz 1kg">

    <label for="codigo_barra_bodega"><strong>Código de barras:</strong></label>
    <input type="text" name="codigo_barra" id="codigo_barra_bodega" placeholder="Ej: 123456789">

    <label for="cantidad_bodega"><strong>Cantidad en stock actual:</strong></label>
    <input type="number" name="cantidad" id="cantidad_bodega" placeholder="Cantidad disponible">

    <label for="stock_inicial_bodega"><strong>Stock inicial:</strong></label>
    <input type="number" name="stock_inicial" id="stock_inicial_bodega" placeholder="Cantidad inicial al crear">

    <label for="precio_bodega"><strong>Precio unitario:</strong></label>
    <input type="number" name="precio" id="precio_bodega" placeholder="Precio por unidad" step="0.01">

    <label for="categoria_bodega"><strong>Categoría del producto:</strong></label>
    <input type="text" name="categoria" id="categoria_bodega" placeholder="Ej: Granos, Bebidas">

    <button type="submit" style="background:#27ae60;color:white;border:none;">
        Guardar
    </button>
</form>

</div>
</div>

<div id="img_modal_overlay">
    <div id="img_modal_box">

        <button id="cerrar_img_modal">✖</button>

        <div id="img_modal_top">
            <img id="img_modal" src="" alt="Vista previa">
        </div>

        <div id="img_modal_footer">
            <div class="modal-header-row">
                <div>
                    <h2 id="modal_producto">Producto</h2>
                    <small id="modal_fecha">Fecha: -</small>
                </div>
           
            </div>

            <div class="modal-grid-detalles">
                <div class="detalle-card">
                    <strong>Descripción</strong>
                    <div id="modal_descripcion">-</div>
                </div>

                <div class="detalle-card">
                    <strong>Cantidad actual</strong>
                    <div id="modal_cantidad">-</div>
                </div>

                <div class="detalle-card">
                    <strong>Precio</strong>
                    <div>$<span id="modal_precio">-</span></div>
                </div>

                <div class="detalle-card">
                    <strong>Categoría</strong>
                    <div id="modal_categoria">-</div>
                </div>

                <div class="detalle-card">
                    <strong>Stock inicial</strong>
                    <div id="modal_stock">-</div>
                </div>

                <div class="detalle-card">
                    <strong>Código de barras</strong>
                    <div id="modal_codigo">-</div>
                </div>
            </div>

            <div class="barcode-box">
                <strong>Vista código</strong>
                <svg id="barcode_svg"></svg>
            </div>
        </div>
    </div>
</div>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Producto</th>
    <th>Descripción</th>
    <th>Cantidad</th>
    <th>Precio</th>
</tr>
</thead>

<tbody id="tbody_bodega">
<?php while($p=$query->fetch_assoc()): ?>

<!-- FILA IMAGEN / TARJETA -->
<tr class="row-card">
    <td colspan="6">
        <div class="card-img">
<img 
    class="img-card"
    src="<?= $p['img'] ?: $defaultImage ?>" 
    alt="Producto"
    data-id="<?= $p['id'] ?>"
    data-id_producto="<?= $p['id_producto'] ?>"
    data-producto="<?= htmlspecialchars($p['producto']) ?>"
    data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>"
    data-cantidad="<?= htmlspecialchars($p['cantidad']) ?>"
    data-precio="<?= number_format($p['precio'],2) ?>"
    data-categoria="<?= htmlspecialchars($p['categoria']) ?>"
    data-codigo="<?= htmlspecialchars($p['codigo_barra']) ?>"
    data-stock="<?= htmlspecialchars($p['stock_inicial']) ?>"
    data-fecha="<?= htmlspecialchars($p['fecha_registro']) ?>"
>        </div>
    </td>
</tr>

<!-- FILA DATOS -->
<tr data-id="<?= $p['id'] ?>">
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['producto']) ?></td>
    <td><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= $p['cantidad'] ?></td>
    <td><?= number_format($p['precio'],2) ?></td>
    
</tr>

<!-- FILA ACCIONES -->
<tr class="action-row" data-id="<?= $p['id'] ?>">
    <td colspan="6">
        <button class="btn btn-edit"
            data-id="<?= $p['id'] ?>"
            data-id_producto="<?= $p['id_producto'] ?>"
            data-producto="<?= htmlspecialchars($p['producto']) ?>"
            data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>"
            data-cantidad="<?= $p['cantidad'] ?>"
            data-precio="<?= $p['precio'] ?>"
            data-categoria="<?= htmlspecialchars($p['categoria']) ?>"
            data-stock="<?= $p['stock_inicial'] ?>"
            data-codigo_barra="<?= htmlspecialchars($p['codigo_barra']) ?>"
        >✏ Editar</button>

        <button class="btn btn-delete" style="background:#c0392b;" data-id="<?= $p['id'] ?>">
            🗑 Borrar
        </button>
    </td>
</tr>

<?php endwhile; ?>
</tbody>

</table>
</div>

<script>
(function(){
    const wrap = document.getElementById('bodega_wrap');
    const modal = wrap.querySelector("#modalForm_bodega");
    const form = wrap.querySelector("#formProducto_bodega");
    const tbody = wrap.querySelector("#tbody_bodega");
    const searchInput = document.getElementById('searchInput');

    // ===== FORM =====
    const idProducto_bodega = document.getElementById('idProducto_bodega');
    const id_producto = document.getElementById('id_producto');
    const producto_bodega = document.getElementById('producto_bodega');
    const descripcion_bodega = document.getElementById('descripcion_bodega');
    const codigo_barra_bodega = document.getElementById('codigo_barra_bodega');
    const cantidad_bodega = document.getElementById('cantidad_bodega');
    const stock_inicial_bodega = document.getElementById('stock_inicial_bodega');
    const precio_bodega = document.getElementById('precio_bodega');
    const categoria_bodega = document.getElementById('categoria_bodega');
    const imagen_bodega = document.getElementById('imagen_bodega');

    // ===== MODAL DETALLE =====
    const img_modal = document.getElementById('img_modal');
    const img_modal_overlay = document.getElementById('img_modal_overlay');
    const cerrar_img_modal = document.getElementById('cerrar_img_modal');
   

    const modal_producto = document.getElementById('modal_producto');
    const modal_fecha = document.getElementById('modal_fecha');
    const modal_descripcion = document.getElementById('modal_descripcion');
    const modal_cantidad = document.getElementById('modal_cantidad');
    const modal_precio = document.getElementById('modal_precio');
    const modal_categoria = document.getElementById('modal_categoria');
    const modal_codigo = document.getElementById('modal_codigo');
    const modal_stock = document.getElementById('modal_stock');
    const barcode_svg = document.getElementById('barcode_svg');

    function showForm(){
        modal.style.display = 'flex';
    }

    function closeForm(){
        modal.style.display = 'none';
        form.reset();
        idProducto_bodega.value = '';
        id_producto.value = '';
    }

    function closeImageModal(){
        img_modal_overlay.style.display = 'none';
    }

    function dibujarBarcodeSimple(codigo) {
        if (!barcode_svg) return;

        if (!codigo || codigo.trim() === '') {
            barcode_svg.innerHTML = `
                <svg width="100%" height="80" viewBox="0 0 220 80" xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="40" font-size="18" fill="#111">Sin código</text>
                </svg>
            `;
            return;
        }

        let barras = '';
        let x = 10;

        for (let i = 0; i < codigo.length; i++) {
            const n = parseInt(codigo[i], 10);
            const ancho = (isNaN(n) ? 1 : (n % 3) + 1);

            barras += `<rect x="${x}" y="10" width="${ancho}" height="50" fill="#111"/>`;
            x += ancho + 2;

            barras += `<rect x="${x}" y="15" width="1" height="45" fill="#111"/>`;
            x += 3;
        }

        barcode_svg.innerHTML = `
            <svg width="100%" height="80" viewBox="0 0 ${Math.max(x+20, 220)} 80" xmlns="http://www.w3.org/2000/svg">
                ${barras}
                <text x="10" y="75" font-size="14" fill="#111">${codigo}</text>
            </svg>
        `;
    }

    function llenarFormularioDesdeDataset(data){
        showForm();

        idProducto_bodega.value = data.id || '';
        id_producto.value = data.id_producto || '';
        producto_bodega.value = data.producto || '';
        descripcion_bodega.value = data.descripcion || '';
        cantidad_bodega.value = data.cantidad || '';
        precio_bodega.value = data.precio || '';
        categoria_bodega.value = data.categoria || '';
        stock_inicial_bodega.value = data.stock || '';
        codigo_barra_bodega.value = data.codigo_barra || data.codigo || '';
    }

    function abrirModalDetalle(img){
        img_modal.src = img.src;
        modal_producto.textContent = img.dataset.producto || '-';
        modal_fecha.textContent = "Fecha: " + (img.dataset.fecha || '-');
        modal_descripcion.textContent = img.dataset.descripcion || '-';
        modal_cantidad.textContent = img.dataset.cantidad || '-';
        modal_precio.textContent = img.dataset.precio || '-';
        modal_categoria.textContent = img.dataset.categoria || '-';
        modal_codigo.textContent = img.dataset.codigo || '-';
        modal_stock.textContent = img.dataset.stock || '-';

        dibujarBarcodeSimple(img.dataset.codigo || '');

      

        img_modal_overlay.style.display = 'flex';
    }

    // ===== BOTONES =====
    document.getElementById('btnNuevo').addEventListener('click', () => {
        form.reset();
        idProducto_bodega.value = 0;
        id_producto.value = '';
        codigo_barra_bodega.value = Date.now(); // código automático
        showForm();
    });

    document.getElementById('btnCerrar').addEventListener('click', closeForm);
    cerrar_img_modal.addEventListener('click', closeImageModal);

    img_modal_overlay.addEventListener('click', (e) => {
        if (e.target === img_modal_overlay) {
            closeImageModal();
        }
    });

 

    // ===== CLICK EN TABLA =====
    tbody.addEventListener('click', e => {
        const t = e.target;

        // Abrir modal detalle al hacer click en imagen
        if (t.classList.contains('img-card')) {
            abrirModalDetalle(t);
            return;
        }

        // Editar
        if (t.classList.contains('btn-edit')) {
            llenarFormularioDesdeDataset(t.dataset);
            return;
        }

        // Eliminar
        if (t.classList.contains('btn-delete')) {
            const id = t.dataset.id;

            if (confirm('¿Eliminar este registro?')) {
                fetch("/negocioencontrol/negocios/modulos/bodega_accion.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `eliminar=1&id=${encodeURIComponent(id)}`
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.ok) {
                        const rowData = tbody.querySelector(`tr[data-id='${id}']:not(.action-row)`);
                        const rowAction = tbody.querySelector(`tr.action-row[data-id='${id}']`);
                        const rowImage = rowData ? rowData.previousElementSibling : null;

                        if (rowImage && rowImage.classList.contains('row-card')) rowImage.remove();
                        if (rowData) rowData.remove();
                        if (rowAction) rowAction.remove();

                        const quedanFilas = tbody.querySelectorAll("tr[data-id]:not(.action-row)").length;

                        if (quedanFilas === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:20px;">
                                        No se encontraron productos
                                    </td>
                                </tr>
                            `;
                        }
                    } else {
                        alert(resp.msg || 'No se pudo eliminar');
                    }
                })
                .catch(err => {
                    console.error("Error al eliminar:", err);
                    alert("Ocurrió un error al eliminar el producto");
                });
            }
        }
    });

    // ===== BUSCADOR AJAX =====
    searchInput.addEventListener('input', e => {
        const value = e.target.value;

        fetch("/negocioencontrol/negocios/modulos/bodega_accion.php", {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `buscar_bodega=${encodeURIComponent(value)}`
        })
        .then(r => r.text())
        .then(html => {
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error("Error en búsqueda:", err);
        });
    });

    // ===== GUARDAR / EDITAR =====
    form.addEventListener('submit', e => {
        e.preventDefault();

        fetch("/negocioencontrol/negocios/modulos/bodega_accion.php", {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(resp => {
            alert(resp.msg);
            if (!resp.ok) return;

            const imagenActualizada = imagen_bodega.files.length > 0;
            const id = idProducto_bodega.value;

            let row = tbody.querySelector(`tr[data-id='${id}']:not(.action-row)`);
            let actionRow = tbody.querySelector(`tr.action-row[data-id='${id}']`);

            // ===== SI ES EDICIÓN =====
            if (row && actionRow) {
                row.children[1].textContent = producto_bodega.value;
                row.children[2].textContent = descripcion_bodega.value;
                row.children[3].textContent = cantidad_bodega.value;
                row.children[4].textContent = parseFloat(precio_bodega.value || 0).toFixed(2);

                const btnEdit = actionRow.querySelector('.btn-edit');
                btnEdit.dataset.producto = producto_bodega.value;
                btnEdit.dataset.descripcion = descripcion_bodega.value;
                btnEdit.dataset.cantidad = cantidad_bodega.value;
                btnEdit.dataset.precio = precio_bodega.value;
                btnEdit.dataset.categoria = categoria_bodega.value;
                btnEdit.dataset.stock = stock_inicial_bodega.value;
                btnEdit.dataset.codigo_barra = codigo_barra_bodega.value;

                const rowImage = row.previousElementSibling;
                const imgCard = rowImage ? rowImage.querySelector('.img-card') : null;

                if (imgCard) {
                    imgCard.dataset.producto = producto_bodega.value;
                    imgCard.dataset.descripcion = descripcion_bodega.value;
                    imgCard.dataset.cantidad = cantidad_bodega.value;
                    imgCard.dataset.precio = parseFloat(precio_bodega.value || 0).toFixed(2);
                    imgCard.dataset.categoria = categoria_bodega.value;
                    imgCard.dataset.stock = stock_inicial_bodega.value;
                    imgCard.dataset.codigo = codigo_barra_bodega.value;

                    if (imagenActualizada) {
                        imgCard.src = imgCard.src.split("?")[0] + "?v=" + Date.now();
                    }
                }

            } else {
                // ===== SI ES NUEVO =====
                location.reload();
            }

            closeForm();
        })
        .catch(err => {
            console.error("Error guardando:", err);
            alert("Ocurrió un error al guardar");
        });
    });

    // ===== COMPRESOR DE IMAGEN =====
    function compressImage(file, quality = 0.7) {
        return new Promise(resolve => {
            const reader = new FileReader();

            reader.onload = event => {
                const img = new Image();

                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    const ctx = canvas.getContext("2d");

                    let w = img.width;
                    let h = img.height;
                    const MAX = 1200;

                    if (w > MAX || h > MAX) {
                        if (w > h) {
                            h *= MAX / w;
                            w = MAX;
                        } else {
                            w *= MAX / h;
                            h = MAX;
                        }
                    }

                    canvas.width = w;
                    canvas.height = h;
                    ctx.drawImage(img, 0, 0, w, h);

                    canvas.toBlob(
                        blob => resolve(blob),
                        "image/jpeg",
                        quality
                    );
                };

                img.src = event.target.result;
            };

            reader.readAsDataURL(file);
        });
    }

    imagen_bodega.addEventListener("change", async function () {
        const file = this.files[0];
        if (!file) return;

        const compressedBlob = await compressImage(file, 0.7);
        const newFile = new File([compressedBlob], "foto.jpg", { type: "image/jpeg" });

        const dt = new DataTransfer();
        dt.items.add(newFile);
        imagen_bodega.files = dt.files;

        console.log("Imagen comprimida y lista:", newFile);
    });

})();
</script>