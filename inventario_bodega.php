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
    max-height: 250px;      /* 👈 MUY compacta */
    overflow: hidden;
    border-radius: 6px;
    margin: 4px 0;
}

.card-img img {
    width: 100%;
    height: 250px;          /* 👈 altura fija pequeña */
    object-fit: cover;
    display: block;
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
const img_modal = document.getElementById('img_modal');
const searchInput = document.getElementById('searchInput');

function showForm(){ modal.style.display='flex'; }
function closeForm(){ modal.style.display='none'; form.reset(); }
document.getElementById('btnNuevo').addEventListener('click',()=>{
    form.reset();
    wrap.querySelector("#idProducto_bodega").value = 0;
    // Generar código automático
    codigo_barra_bodega.value = Date.now(); // ejemplo: timestamp como código
    showForm();
});

document.getElementById('btnCerrar').addEventListener('click',closeForm);

tbody.addEventListener('click', e=>{
    if(e.target.classList.contains('img-card')){
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
    precio_bodega.value = t.dataset.precio; // float incluido
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
                tbody.querySelector(`tr[data-id='${t.dataset.id}']`).remove();
                tbody.querySelector(`tr.action-row[data-id='${t.dataset.id}']`).remove();
            } else alert(resp.msg);
        });
    }
}
});

searchInput.addEventListener('input', e=>{
    const value = e.target.value;

    fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`buscar_bodega=${encodeURIComponent(value)}`
    })
    .then(r=>r.text())
    .then(html=>{
        tbody.innerHTML = html;
    });
});


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

        // 🟢 SI ES EDICIÓN
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

                // 🔁 ACTUALIZAR IMAGEN SIN RECARGAR (SI SE CAMBIÓ)
    if(imagenActualizada){
        const img = row.querySelector("img");
        img.src = img.src.split("?")[0] + "?v=" + Date.now();
    }


        } else {
            // 🟢 SI ES NUEVO → recarga ligera (opcional)
            location.reload();
        }

        closeForm();
    });
});

})();

// === COMPRESOR AUTOMÁTICO PARA IMÁGENES (MISMO QUE VERSIÓN VIEJA) ===
const fileInput = document.getElementById("imagen_bodega");

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

fileInput.addEventListener("change", async function () {
    const file = this.files[0];
    if (!file) return;

    const compressedBlob = await compressImage(file, 0.7);
    const newFile = new File([compressedBlob], "foto.jpg", { type: "image/jpeg" });

    const dt = new DataTransfer();
    dt.items.add(newFile);
    fileInput.files = dt.files;

    console.log("Imagen comprimida y lista:", newFile);
});

</script>

