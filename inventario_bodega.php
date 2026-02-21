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
#bodega_wrap *{box-sizing:border-box;}
#bodega_wrap{font-family:Arial;background:#f7f7f7;padding:10px;}
#bodega_wrap header{background:#34495e;color:white;padding:12px;text-align:center;font-size:20px;font-weight:bold;border-radius:6px;margin-bottom:10px;}
#bodega_wrap .toolbar{text-align:center;margin-bottom:15px;}
#bodega_wrap .btn{padding:8px 12px;border:none;border-radius:6px;cursor:pointer;background:#3498db;color:white;font-size:14px;}
#bodega_wrap table{width:100%;border-collapse:collapse;background:white;}
#bodega_wrap th,#bodega_wrap td{padding:8px;border:1px solid #ddd;text-align:center;}
#bodega_wrap th{background:#eee;}
.row-card td{padding:0;border:none;}
.card-img{text-align:center;margin:10px auto;}
.card-img img{width:200px;height:250px;object-fit:cover;border-radius:8px;}
.action-row td{background:#fafafa;}
#modalForm_bodega{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);display:none;justify-content:center;align-items:center;z-index:9999;}
#formBox_bodega{background:white;padding:20px;width:95%;max-width:450px;border-radius:8px;}
#formBox_bodega input,#formBox_bodega button{width:100%;padding:10px;margin:6px 0;border-radius:5px;border:1px solid #ccc;}
#img_modal{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);max-width:90%;max-height:90%;display:none;border:5px solid #fff;border-radius:8px;z-index:99999;cursor:pointer;}
#searchInput{margin-bottom:10px;padding:8px;width:100%;border-radius:5px;border:1px solid #ccc;}

/* RESPONSIVE */
@media (max-width:768px){
#bodega_wrap table,#bodega_wrap thead,#bodega_wrap tbody,#bodega_wrap th,#bodega_wrap td,#bodega_wrap tr{display:block;width:100%;}
#bodega_wrap thead{display:none;}
#bodega_wrap tr{margin-bottom:15px;background:white;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:10px;}
.row-card{box-shadow:none;padding:0;margin-bottom:0;}
.card-img img{width:100%;height:200px;}
tr[data-id]:not(.row-card):not(.action-row) td{border:none;text-align:left;padding:6px 4px;}
tr[data-id]:not(.row-card):not(.action-row) td::before{font-weight:bold;display:block;color:#555;}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(1)::before{content:"ID";}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(2)::before{content:"Producto";}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(3)::before{content:"Código";}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(4)::before{content:"Descripción";}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(5)::before{content:"Cantidad";}
tr[data-id]:not(.row-card):not(.action-row) td:nth-child(6)::before{content:"Precio";}
.action-row td{border:none;text-align:center;}
#bodega_wrap .btn{width:100%;margin:5px 0;}
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
<button id="btnCerrar" style="background:#e74c3c;color:white;">Cerrar ✖</button>

<form id="formProducto_bodega" enctype="multipart/form-data">
<input type="hidden" name="id" id="idProducto_bodega">
<input type="hidden" name="id_producto" id="id_producto">
<input type="file" name="imagen" id="imagen_bodega" accept="image/*">
<input type="text" name="producto" id="producto_bodega" placeholder="Producto">
<input type="text" name="descripcion" id="descripcion_bodega" placeholder="Descripción">
<input type="text" name="codigo_barra" id="codigo_barra_bodega" placeholder="Código de barras">
<input type="number" name="cantidad" id="cantidad_bodega" placeholder="Cantidad">
<input type="number" name="stock_inicial" id="stock_inicial_bodega" placeholder="Stock inicial">
<input type="number" step="0.01" name="precio" id="precio_bodega" placeholder="Precio">
<input type="text" name="categoria" id="categoria_bodega" placeholder="Categoría">
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
<th>Código</th>
<th>Descripción</th>
<th>Cantidad</th>
<th>Precio</th>
</tr>
</thead>
<tbody id="tbody_bodega">

<?php while($p=$query->fetch_assoc()): ?>

<tr class="row-card" data-id="<?= $p['id'] ?>">
<td colspan="6">
<div class="card-img">
<img src="<?= $p['img'] ?: $defaultImage ?>">
</div>
</td>
</tr>

<tr data-id="<?= $p['id'] ?>">
<td><?= $p['id'] ?></td>
<td><?= htmlspecialchars($p['producto']) ?></td>
<td><?= htmlspecialchars($p['codigo_barra']) ?></td>
<td><?= htmlspecialchars($p['descripcion']) ?></td>
<td><?= $p['cantidad'] ?></td>
<td><?= number_format($p['precio'],2) ?></td>
</tr>

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

<button class="btn btn-delete" style="background:#c0392b;" data-id="<?= $p['id'] ?>">🗑 Borrar</button>
</td>
</tr>

<?php endwhile; ?>
</tbody>
</table>
</div>

<script>
(function(){
const wrap=document.getElementById('bodega_wrap');
const modal=wrap.querySelector("#modalForm_bodega");
const form=wrap.querySelector("#formProducto_bodega");
const tbody=wrap.querySelector("#tbody_bodega");
const searchInput=wrap.querySelector("#searchInput");

function showForm(){modal.style.display='flex';}
function closeForm(){modal.style.display='none';form.reset();}

wrap.querySelector("#btnNuevo").addEventListener("click",()=>{
form.reset();
wrap.querySelector("#idProducto_bodega").value=0;
wrap.querySelector("#codigo_barra_bodega").value=Date.now();
showForm();
});

wrap.querySelector("#btnCerrar").addEventListener("click",closeForm);

tbody.addEventListener("click",e=>{
const t=e.target;

if(t.classList.contains("btn-edit")){
showForm();
idProducto_bodega.value=t.dataset.id;
id_producto.value=t.dataset.id_producto;
producto_bodega.value=t.dataset.producto;
descripcion_bodega.value=t.dataset.descripcion;
cantidad_bodega.value=t.dataset.cantidad;
precio_bodega.value=t.dataset.precio;
categoria_bodega.value=t.dataset.categoria;
stock_inicial_bodega.value=t.dataset.stock;
codigo_barra_bodega.value=t.dataset.codigo_barra||'';
}

if(t.classList.contains("btn-delete")){
if(confirm("¿Eliminar este registro?")){
fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`eliminar=1&id=${t.dataset.id}`
})
.then(r=>r.json())
.then(resp=>{
if(resp.ok){
tbody.querySelectorAll(`tr[data-id='${t.dataset.id}']`).forEach(r=>r.remove());
}else alert(resp.msg);
});
}
}
});

searchInput.addEventListener("input",e=>{
const value=e.target.value.toLowerCase();
tbody.querySelectorAll("tr[data-id]:not(.row-card):not(.action-row)").forEach(row=>{
const id=row.dataset.id;
const nombre=row.children[1].textContent.toLowerCase();
const mostrar=nombre.includes(value);
tbody.querySelectorAll(`tr[data-id='${id}']`).forEach(r=>r.style.display=mostrar?'':'none');
});
});

form.addEventListener("submit",e=>{
e.preventDefault();

fetch("/negocioencontrol/negocios/modulos/bodega_accion.php",{
method:'POST',
body:new FormData(form)
})
.then(r=>r.json())
.then(resp=>{
alert(resp.msg);
if(!resp.ok)return;

const id=idProducto_bodega.value;

let imageRow=tbody.querySelector(`tr.row-card[data-id='${id}']`);
let dataRow=tbody.querySelector(`tr[data-id='${id}']:not(.row-card):not(.action-row)`);
let actionRow=tbody.querySelector(`tr.action-row[data-id='${id}']`);

if(dataRow && actionRow){
dataRow.children[1].textContent=producto_bodega.value;
dataRow.children[2].textContent=codigo_barra_bodega.value;
dataRow.children[3].textContent=descripcion_bodega.value;
dataRow.children[4].textContent=cantidad_bodega.value;
dataRow.children[5].textContent=parseFloat(precio_bodega.value).toFixed(2);

const btnEdit=actionRow.querySelector(".btn-edit");
btnEdit.dataset.producto=producto_bodega.value;
btnEdit.dataset.descripcion=descripcion_bodega.value;
btnEdit.dataset.cantidad=cantidad_bodega.value;
btnEdit.dataset.precio=precio_bodega.value;
btnEdit.dataset.categoria=categoria_bodega.value;
btnEdit.dataset.stock=stock_inicial_bodega.value;
btnEdit.dataset.codigo_barra=codigo_barra_bodega.value;

if(document.getElementById("imagen_bodega").files.length>0 && imageRow){
const img=imageRow.querySelector("img");
img.src=img.src.split("?")[0]+"?v="+Date.now();
}

}else{
location.reload();
}

closeForm();
});
});
})();
</script>
