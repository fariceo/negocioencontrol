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
}

.card-img {
    width: 100%;
    display: flex;              /* 👈 activa centrado */
    justify-content: center;    /* 👈 centra horizontal */
    align-items: center;        /* 👈 centra vertical */
    overflow: hidden;
    border-radius: 6px;
    margin: 4px 0;
}

.card-img img {
    width: 300px;
    height: 250px;
    object-fit: cover;
    display: block;
    border-radius: 10px; /* opcional para efecto tarjeta */
}

/* tabla limpia */
table {
    border-collapse: collapse;
    width: 100%;
}

td, th {
    padding: 4px 6px;      /* 👈 menos espacio */
    font-size: 0.9em;
    border-bottom: 1px solid #e0e0e0;
}

.action-row td {
    background: #fafafa;
    padding: 5px;
}

/* ===== CONVERTIR TABLA EN TARJETAS VISUALES ===== */

#tbody_bodega tr {
    border: none !important;
}

#tbody_bodega tr.row-card,
#tbody_bodega tr[data-id],
#tbody_bodega tr.action-row {
    background: white;
}

#tbody_bodega tr.row-card td {
    border: none;
    padding: 0;
}

#tbody_bodega tr[data-id] td {
    border: none;
    padding: 6px 10px;
    font-size: 14px;
}

#tbody_bodega tr.action-row td {
    border: none;
    padding: 10px;
}

/* Contenedor visual tipo tarjeta */
#tbody_bodega tr.row-card td {
    padding-top: 15px;
}

/* Espacio entre productos */
#tbody_bodega tr.action-row {
    border-bottom: 15px solid #f7f7f7;
}

/* Sombra tipo card */
#tbody_bodega tr.row-card td,
#tbody_bodega tr[data-id] td,
#tbody_bodega tr.action-row td {
    background: white;
}

#tbody_bodega tr.row-card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-radius: 12px;
}

/* Redondear esquinas superiores */
#tbody_bodega tr.row-card td:first-child {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

/* Redondear esquinas inferiores */
#tbody_bodega tr.action-row td {
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
}

/* Mejorar imagen */
.card-img img {
    border-radius: 12px 12px 0 0;
}

/* Mejorar botones */
#bodega_wrap .btn {
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

#bodega_wrap .btn:hover {
    transform: translateY(-1px);
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
#img_modal { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); max-width:90%; max-height:90%; display:none; border:5px solid #fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,.5); z-index:99999; cursor:pointer; }
#searchInput { margin-bottom:10px; padding:8px; width:100%; border-radius:5px; border:1px solid #ccc; }
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


<img id="img_modal" onclick="this.style.display='none'">

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
            <img src="<?= $p['img'] ?: $defaultImage ?>" alt="Producto">
        </div>
    </td>
</tr>

<!-- FILA DATOS -->
<tr data-id="<?= $p['id'] ?>">
    <td><?= $p['id'] ?></td>
    <td>
        <strong><?= htmlspecialchars($p['producto']) ?></strong><br>
        <small>Código: <?= htmlspecialchars($p['codigo_barra']) ?></small>
    </td>
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
const img_modal = document.getElementById('img_modal');
const searchInput = wrap.querySelector('#searchInput');

function showForm(){ modal.style.display='flex'; }
function closeForm(){ modal.style.display='none'; form.reset(); }

document.getElementById('btnNuevo').addEventListener('click',()=>{
    form.reset();
    wrap.querySelector("#idProducto_bodega").value = 0;
    codigo_barra_bodega.value = Date.now();
    showForm();
});

document.getElementById('btnCerrar').addEventListener('click',closeForm);

tbody.addEventListener('click', e=>{
    if(e.target.tagName === "IMG"){
        img_modal.src = e.target.src;
        img_modal.style.display='block';
    }
});

tbody.addEventListener('click', e=>{
const t = e.target;

if(t.classList.contains('btn-edit')){
    showForm();
    idProducto_bodega.value = t.dataset.id;
    id_producto.value = t.dataset.id_producto;
    producto_bodega.value = t.dataset.producto;
    descripcion_bodega.value = t.dataset.descripcion;
    cantidad_bodega.value = t.dataset.cantidad;
    precio_bodega.value = t.dataset.precio;
    categoria_bodega.value = t.dataset.categoria;
    stock_inicial_bodega.value = t.dataset.stock;
    codigo_barra_bodega.value = t.dataset.codigo_barra || '';
}

if(t.classList.contains('btn-delete')){
    if(confirm('¿Eliminar este registro?')){
        fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`eliminar=1&id=${t.dataset.id}`
        })
        .then(r=>r.json())
        .then(resp=>{
            if(resp.ok){
                const row = tbody.querySelector(`tr[data-id='${t.dataset.id}']`);
                const actionRow = tbody.querySelector(`tr.action-row[data-id='${t.dataset.id}']`);
                const imgRow = row ? row.previousElementSibling : null;

                if(imgRow) imgRow.remove();
                if(row) row.remove();
                if(actionRow) actionRow.remove();
            } else {
                alert(resp.msg);
            }
        });
    }
}
});

let timeout = null;

searchInput.addEventListener('input', e=>{
    clearTimeout(timeout);
    const value = e.target.value.trim();

    timeout = setTimeout(()=>{
        fetch(`/negocioencontrol/negocios/modulos/bodega_accion.php?buscar=${encodeURIComponent(value)}`)
        .then(res=>res.json())
        .then(resp=>{
            if(!resp.ok) return;

            tbody.innerHTML = "";

            resp.data.forEach(item=>{

                const imgRow = `
                <tr class="row-card">
                    <td colspan="6">
                        <div class="card-img">
                            <img src="${item.img || '<?= $defaultImage ?>'}">
                        </div>
                    </td>
                </tr>`;

                const dataRow = `
                <tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td>${item.producto}</td>
                    <td>${item.descripcion || ''}</td>
                    <td>${item.cantidad}</td>
                    <td>${parseFloat(item.precio).toFixed(2)}</td>
                </tr>`;

                const actionRow = `
                <tr class="action-row" data-id="${item.id}">
                    <td colspan="6">
                        <button class="btn btn-edit"
                            data-id="${item.id}"
                            data-id_producto="${item.id_producto}"
                            data-producto="${item.producto}"
                            data-descripcion="${item.descripcion || ''}"
                            data-cantidad="${item.cantidad}"
                            data-precio="${item.precio}"
                            data-categoria="${item.categoria || ''}"
                            data-stock="${item.stock_inicial || 0}"
                            data-codigo_barra="${item.codigo_barra || ''}"
                        >✏ Editar</button>

                        <button class="btn btn-delete" style="background:#c0392b;" data-id="${item.id}">
                            🗑 Borrar
                        </button>
                    </td>
                </tr>`;

                tbody.innerHTML += imgRow + dataRow + actionRow;
            });
        });
    }, 300);
});


/* =========================
   SUBMIT CORREGIDO
   ========================= */
form.addEventListener('submit', e=>{
    e.preventDefault();

    fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
        method:'POST',
        body:new FormData(form)
    })
    .then(r=>r.json())
    .then(resp=>{
        alert(resp.msg);
        if(!resp.ok) return;

        const imagenInput = document.getElementById("imagen_bodega");
        const imagenActualizada = imagenInput.files.length > 0;
        const id = idProducto_bodega.value;

        let row = tbody.querySelector(`tr[data-id='${id}']`);
        let actionRow = tbody.querySelector(`tr.action-row[data-id='${id}']`);

        if(row && actionRow){

            row.children[1].textContent = producto_bodega.value;
            row.children[2].textContent = descripcion_bodega.value;
            row.children[3].textContent = cantidad_bodega.value;
            row.children[4].textContent = parseFloat(precio_bodega.value).toFixed(2);

            const btnEdit = actionRow.querySelector('.btn-edit');
            btnEdit.dataset.producto = producto_bodega.value;
            btnEdit.dataset.descripcion = descripcion_bodega.value;
            btnEdit.dataset.cantidad = cantidad_bodega.value;
            btnEdit.dataset.precio = precio_bodega.value;
            btnEdit.dataset.categoria = categoria_bodega.value;
            btnEdit.dataset.stock = stock_inicial_bodega.value;
            btnEdit.dataset.codigo_barra = codigo_barra_bodega.value;

       if(imagenActualizada && resp.img){
    const imgRow = row.previousElementSibling;
    if(imgRow && imgRow.classList.contains("row-card")){
        const img = imgRow.querySelector("img");
        img.src = resp.img;
    }
}

        } else {
            location.reload();
        }

        closeForm();
    });
});

})();
</script>