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

$query = $conexion->query("
    SELECT b.*, COALESCE(p.imagen,'') AS img
    FROM bodega b
    LEFT JOIN productos p ON b.producto = p.producto
    WHERE b.negocio='{$conexion->real_escape_string($negocio)}'
    ORDER BY b.producto ASC
");
?>

<style>
/* ----- ESTILOS AISLADOS PARA MÓDULO ----- */
#bodega_wrap * { box-sizing: border-box; }

#bodega_wrap {
    font-family: Arial;
    background: #f7f7f7;
    padding: 10px;
}

/* Encabezado */
#bodega_wrap header {
    background:#34495e;
    color:white;
    padding:12px;
    text-align:center;
    font-size:20px;
    font-weight:bold;
    border-radius:6px;
    margin-bottom:10px;
}

/* Botones */
#bodega_wrap .toolbar {
    display:flex;
    gap:10px;
    justify-content:center;
    margin-bottom:15px;
}

#bodega_wrap .btn {
    padding:10px 15px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    background:#3498db;
    color:white;
    transition:0.2s;
    font-size:15px;
}
#bodega_wrap .btn:hover { background:#2980b9; }

/* Tabla */
#bodega_wrap table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:6px;
    overflow:hidden;
}

#bodega_wrap th, 
#bodega_wrap td {
    padding:10px;
    border:1px solid #ddd;
    text-align:center;
}

#bodega_wrap th {
    background:#eee;
}

/* Imagen tipo tarjeta */
#bodega_wrap .img-card {
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.3);
    background:white;
}

/* Fila de acciones */
#bodega_wrap .action-row {
    background:#fafafa;
}

/* Modal */
#modalForm_bodega {
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

#formBox_bodega {
    background:white;
    padding:20px;
    width:95%;
    max-width:450px;
    border-radius:8px;
}

#formBox_bodega input, #formBox_bodega button {
    width:100%;
    padding:10px;
    margin:6px 0;
    border-radius:5px;
    border:1px solid #ccc;
}

</style>

<div id="bodega_wrap">

<header>📦 Inventario de Bodega</header>

<div class="toolbar">
    <button class="btn" onclick="showFormBodega(0)">➕ Nuevo Producto</button>
</div>

<!-- MODAL -->
<div id="modalForm_bodega">
    <div id="formBox_bodega">

        <button onclick="closeFormBodega()" 
            style="background:#e74c3c;color:white;border:none;border-radius:5px;padding:8px 12px;">
            Cerrar ✖
        </button>

        <form id="formProducto_bodega" enctype="multipart/form-data">
            <input type="hidden" name="id" id="idProducto_bodega">

            <input type="text" name="producto" id="producto_bodega" placeholder="Producto" required>
            <input type="text" name="descripcion" id="descripcion_bodega" placeholder="Descripción">
            <input type="number" name="cantidad" id="cantidad_bodega" placeholder="Cantidad">
            <input type="number" name="precio" id="precio_bodega" placeholder="Precio">
            <input type="text" name="categoria" id="categoria_bodega" placeholder="Categoría">
            <input type="number" name="stock_inicial" id="stock_bodega" placeholder="Stock inicial">

            <input type="file" name="imagen" id="imagen_bodega" accept="image/*">

            <button type="submit" style="background:#27ae60;color:white;border:none;">
                Guardar
            </button>
        </form>

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
    <th>Imagen</th>
</tr>
</thead>
<tbody>

<?php while($p = $query->fetch_assoc()): ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['producto']) ?></td>
    <td><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= $p['cantidad'] ?></td>
    <td><?= number_format($p['precio'],2) ?></td>

    <td>
        <img src="<?= !empty($p['img']) ? $p['img'] : $defaultImage ?>"
             class="img-card">
    </td>
</tr>

<tr class="action-row">
    <td colspan="6">
        <button class="btn"
            onclick="editBodega(
                <?= $p['id'] ?>,
                '<?= htmlspecialchars($p['producto']) ?>',
                '<?= htmlspecialchars($p['descripcion']) ?>',
                <?= $p['cantidad'] ?>,
                <?= $p['precio'] ?>,
                '<?= $p['categoria'] ?>',
                <?= $p['stock_inicial'] ?>
            )">
            ✏ Editar
        </button>

        <button class="btn" style="background:#c0392b;"
            onclick="deleteBodega(<?= $p['id'] ?>)">
            🗑 Borrar
        </button>
    </td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>

<script>
/* ----- JS ÚNICO PARA ESTE MÓDULO ----- */

function showFormBodega(){
    document.getElementById("modalForm_bodega").style.display = "flex";
}

function closeFormBodega(){
    document.getElementById("modalForm_bodega").style.display = "none";
}

function editBodega(id,p,d,c,pr,cat,stock){
    showFormBodega();

    document.getElementById("idProducto_bodega").value = id;
    document.getElementById("producto_bodega").value = p;
    document.getElementById("descripcion_bodega").value = d;
    document.getElementById("cantidad_bodega").value = c;
    document.getElementById("precio_bodega").value = pr;
    document.getElementById("categoria_bodega").value = cat;
    document.getElementById("stock_bodega").value = stock;
}

function deleteBodega(id){
    if(!confirm("¿Eliminar este registro?")) return;

    fetch("bodega_accion.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `eliminar=1&id=${id}`
    })
    .then(r => r.json())
    .then(resp => {
        if(resp.ok) location.reload();
        else alert(resp.msg);
    });
}

document.getElementById("formProducto_bodega").addEventListener("submit", e =>{
    e.preventDefault();
    let data = new FormData(e.target);

    fetch("bodega_accion.php", {
        method:"POST",
        body:data
    })
    .then(r=>r.json())
    .then(resp=>{
        if(resp.ok){
            alert(resp.msg);
            location.reload();
        } else {
            alert(resp.msg);
        }
    });
});
</script>
