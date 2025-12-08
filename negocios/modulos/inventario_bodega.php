(inventario_bodega.php) <?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>");
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];
$defaultImage = "/negocioencontrol/negocios/modulos/assets/default.png";

$stmt = $conexion->prepare("
    SELECT b.id, b.producto, b.descripcion, b.cantidad, b.precio, COALESCE(p.imagen,'') AS img
    FROM bodega b 
    LEFT JOIN productos p ON b.producto = p.producto
    WHERE b.negocio = ?
    ORDER BY b.producto ASC
");
$stmt->bind_param("s", $negocio);
$stmt->execute();
$query = $stmt->get_result();
?>

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
            <input type="text" name="producto" id="producto_bodega" placeholder="Producto" required>
            <input type="text" name="descripcion" id="descripcion_bodega" placeholder="Descripción">
            <input type="number" name="cantidad" id="cantidad_bodega" placeholder="Cantidad">
            <input type="number" name="precio" id="precio_bodega" placeholder="Precio">
            <input type="text" name="categoria" id="categoria_bodega" placeholder="Categoría">
            <input type="file" name="imagen" id="imagen_bodega" accept="image/*">
            <button type="submit" style="background:#27ae60;color:white;border:none;">Guardar</button>
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
    <th>Imagen</th>
</tr>
</thead>
<tbody id="tbody_bodega">

<?php while($p=$query->fetch_assoc()): ?>
<tr data-id="<?= $p['id'] ?>">
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['producto']) ?></td>
    <td><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= $p['cantidad'] ?></td>
    <td><?= number_format($p['precio'],2) ?></td>
    <td><img src="<?= !empty($p['img'])?$p['img']:$defaultImage ?>" class="img-card"></td>
</tr>
<tr class="action-row" data-id="<?= $p['id'] ?>">
    <td colspan="6">
        <button class="btn btn-edit" data-id="<?= $p['id'] ?>" data-producto="<?= htmlspecialchars($p['producto']) ?>" data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>" data-cantidad="<?= $p['cantidad'] ?>" data-precio="<?= $p['precio'] ?>" data-categoria="<?= htmlspecialchars($p['categoria'] ?? '') ?>">✏ Editar</button>
        <button class="btn btn-delete" style="background:#c0392b;" data-id="<?= $p['id'] ?>">🗑 Borrar</button>
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

    // NUEVO
    document.getElementById('btnNuevo').addEventListener('click',()=>{ showForm(); form.reset(); wrap.querySelector("#idProducto_bodega").value=0; });

    // CERRAR
    document.getElementById('btnCerrar').addEventListener('click',()=>closeForm());

    // CLICK IMAGEN
    tbody.addEventListener('click', e=>{
        if(e.target.classList.contains('img-card')){
            img_modal.src = e.target.src;
            img_modal.style.display='block';
        }
    });

    // EDITAR y ELIMINAR
    tbody.addEventListener('click', e=>{
        if(e.target.classList.contains('btn-edit')){
            const btn = e.target;
            showForm();
            wrap.querySelector("#idProducto_bodega").value = btn.dataset.id;
            wrap.querySelector("#producto_bodega").value = btn.dataset.producto;
            wrap.querySelector("#descripcion_bodega").value = btn.dataset.descripcion;
            wrap.querySelector("#cantidad_bodega").value = btn.dataset.cantidad;
            wrap.querySelector("#precio_bodega").value = btn.dataset.precio;
            wrap.querySelector("#categoria_bodega").value = btn.dataset.categoria;
        }
        if(e.target.classList.contains('btn-delete')){
            const id = e.target.dataset.id;
            if(confirm('¿Eliminar este registro?')){
                fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
                    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`eliminar=1&id=${id}`
                }).then(r=>r.json()).then(resp=>{
                    if(resp.ok){
                        tbody.querySelector(`tr[data-id='${id}']`).remove();
                        tbody.querySelector(`tr.action-row[data-id='${id}']`).remove();
                    }else alert(resp.msg);
                });
            }
        }
    });

    // BUSCADOR
    searchInput.addEventListener('input', e=>{
        const val = e.target.value.toLowerCase();
        tbody.querySelectorAll('tr').forEach(tr=>{
            if(!tr.dataset.id) return;
            const text = tr.children[1].textContent.toLowerCase();
            tr.style.display = text.includes(val)?'':'none';
            const actionRow = tbody.querySelector(`tr.action-row[data-id='${tr.dataset.id}']`);
            if(actionRow) actionRow.style.display = tr.style.display;
        });
    });

    // GUARDAR PRODUCTO
    form.addEventListener('submit', e=>{
        e.preventDefault();
        const data = new FormData(form);
        const id = document.getElementById('idProducto_bodega').value;

        fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{ method:'POST', body:data })
        .then(r=>r.json()).then(resp=>{
            if(resp.ok){
                alert(resp.msg);
                closeForm();
                location.reload(); // Aquí puedes mejorar para actualizar solo la fila sin recargar
            } else alert(resp.msg);
        });
    });

})();
</script>
 