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

// Traer productos de bodega
$query = $conexion->query("
    SELECT b.*, COALESCE(p.imagen,'') AS img
    FROM bodega b
    LEFT JOIN productos p ON b.producto = p.producto
    WHERE b.negocio='{$conexion->real_escape_string($negocio)}'
    ORDER BY b.producto ASC
");
?>
<h2 style="text-align:center;">📦 Bodega</h2>

<!-- Formulario agregar/editar -->
<form id="formProducto" style="max-width:500px; margin:10px auto; display:flex; flex-direction:column; gap:10px;">
    <input type="hidden" name="id" id="idProducto" value="0">
    <input type="text" name="producto" id="producto" placeholder="Nombre del producto" required>
    <input type="text" name="descripcion" id="descripcion" placeholder="Descripción">
    <input type="number" name="cantidad" id="cantidad" placeholder="Cantidad" min="0" required>
    <input type="number" name="precio" id="precio" placeholder="Precio" step="0.01" required>
    <input type="text" name="categoria" id="categoria" placeholder="Categoría">
    <input type="number" name="stock_inicial" id="stock_inicial" placeholder="Stock inicial" min="0">
    <input type="file" name="imagen" id="imagen">
    <button type="submit" style="background:#27ae60; color:white; padding:8px; border:none; border-radius:4px; cursor:pointer;">Guardar</button>
</form>

<!-- Tabla de productos -->
<table border="1" style="width:100%; margin-top:20px; border-collapse:collapse; text-align:center;">
    <thead style="background:#f0f0f0;">
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Imagen</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($p = $query->fetch_assoc()): ?>
            <tr data-id="<?= $p['id'] ?>" data-producto="<?= htmlspecialchars($p['producto']) ?>" data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>" data-cantidad="<?= $p['cantidad'] ?>" data-precio="<?= $p['precio'] ?>" data-categoria="<?= $p['categoria'] ?? '' ?>" data-stock="<?= $p['stock_inicial'] ?? 0 ?>" data-img="<?= htmlspecialchars($p['img']) ?>">
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['producto']) ?></td>
                <td><?= htmlspecialchars($p['descripcion']) ?></td>
                <td><?= $p['cantidad'] ?></td>
                <td><?= number_format($p['precio'],2) ?></td>
                <td><img src="<?= !empty($p['img'])?$p['img']:$defaultImage ?>" width="50"></td>
                <td>
                    <button class="editar">Editar</button>
                    <button class="borrar">Borrar</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
const form = document.getElementById('formProducto');

// Submit formulario agregar/editar
form.addEventListener('submit', e=>{
    e.preventDefault();
    const data = new FormData(form);
    fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
        method:'POST',
        body:data
    })
    .then(r=>r.json())
    .then(resp=>{
        if(resp.ok){
            alert(resp.msg);
            location.reload();
        } else alert(resp.msg);
    });
});

// Editar producto
document.querySelectorAll('.editar').forEach(btn=>{
    btn.addEventListener('click', e=>{
        const tr = e.target.closest('tr');
        document.getElementById('idProducto').value = tr.dataset.id;
        document.getElementById('producto').value = tr.dataset.producto;
        document.getElementById('descripcion').value = tr.dataset.descripcion;
        document.getElementById('cantidad').value = tr.dataset.cantidad;
        document.getElementById('precio').value = tr.dataset.precio;
        document.getElementById('categoria').value = tr.dataset.categoria;
        document.getElementById('stock_inicial').value = tr.dataset.stock;
    });
});

// Borrar producto
document.querySelectorAll('.borrar').forEach(btn=>{
    btn.addEventListener('click', e=>{
        if(!confirm('¿Eliminar este producto?')) return;
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: `eliminar=1&id=${id}`
        })
        .then(r=>r.json())
        .then(resp=>{
            if(resp.ok) location.reload();
            else alert(resp.msg);
        });
    });
});
</script>
