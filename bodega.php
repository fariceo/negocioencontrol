<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
require '/var/www/elpollovolantuso/vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

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

<h2 style="text-align:center;">📦 Inventario Bodega</h2>

<form id="formProducto" style="max-width:500px; margin:10px auto; display:flex; flex-direction:column; gap:10px;">
    <input type="hidden" name="id" id="idProducto" value="0">
    <input type="text" name="producto" id="producto" placeholder="Nombre del producto" required>
    <input type="text" name="descripcion" id="descripcion" placeholder="Descripción">
    <input type="number" name="cantidad" id="cantidad" placeholder="Cantidad" min="0" required>
    <input type="number" name="precio" id="precio" placeholder="Precio" step="0.01" required>
    <input type="file" name="imagen" id="imagen" accept="image/*">
    <button type="submit" style="background:#27ae60; color:white; padding:8px; border:none; border-radius:4px; cursor:pointer;">Guardar</button>
</form>

<table border="1" style="width:100%; margin-top:20px; border-collapse:collapse; text-align:center;">
    <thead style="background:#f0f0f0;">
        <tr>
            <th>ID</th><th>Producto</th><th>Descripción</th><th>Cantidad</th>
            <th>Precio</th><th>Imagen</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($p = $query->fetch_assoc()): ?>
        <tr data-id="<?= $p['id'] ?>" 
            data-producto="<?= htmlspecialchars($p['producto']) ?>" 
            data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>" 
            data-cantidad="<?= $p['cantidad'] ?>" 
            data-precio="<?= $p['precio'] ?>" 
            data-img="<?= htmlspecialchars($p['img']) ?>">
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

<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@latest/dist/browser-image-compression.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){

    const form = document.getElementById('formProducto');
    const inputFile = document.getElementById('imagen');

    // Guardar / Editar
    form.addEventListener('submit', async e=>{
        e.preventDefault();
        const formData = new FormData(form);
        if(inputFile.files.length>0){
            const file = inputFile.files[0];
            try {
                const compressed = await imageCompression(file,{maxSizeMB:0.5,maxWidthOrHeight:1000,useWebWorker:true});
                formData.set('imagen', compressed, file.name);
            } catch(err){ alert('Error imagen: '+err.message); return; }
        }
        fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
            method:'POST', body: formData
        })
        .then(r=>r.json())
        .then(resp=>{ alert(resp.msg); if(resp.ok) location.reload(); });
    });

    // Delegación de eventos para Editar y Borrar
    document.querySelector('table tbody').addEventListener('click', e=>{
        const tr = e.target.closest('tr');
        if(!tr) return;
        // Editar
        if(e.target.classList.contains('editar')){
            document.getElementById('idProducto').value = tr.dataset.id;
            document.getElementById('producto').value = tr.dataset.producto;
            document.getElementById('descripcion').value = tr.dataset.descripcion;
            document.getElementById('cantidad').value = tr.dataset.cantidad;
            document.getElementById('precio').value = tr.dataset.precio;
        }
        // Borrar
        if(e.target.classList.contains('borrar')){
            if(!confirm('¿Eliminar este producto?')) return;
            const id = tr.dataset.id;
            fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: `eliminar=1&id=${id}`
            })
            .then(r=>r.json())
            .then(resp=>{ alert(resp.msg); if(resp.ok) location.reload(); });
        }
    });

});
</script>
