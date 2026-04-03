<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("<p style='color:red; text-align:center;'>Sesión expirada. Inicia sesión nuevamente.</p>");
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$buscar = trim($_GET['buscar'] ?? '');

$sql = "
    SELECT 
        f.id_factura,
        f.id_venta,
        f.codigo_compra,
        f.cliente_nombre,
        f.cliente_identificacion,
        f.tipo_identificacion,
        f.metodo_pago,
        f.subtotal_0,
        f.subtotal_iva,
        f.iva_total,
        f.total,
        f.estado_sri,
        f.ambiente,
        f.clave_acceso,
        f.numero_autorizacion,
        f.xml_generado,
        f.xml_firmado,
        f.fecha_emision,
        v.productos,
        v.fecha_hora
    FROM facturacion_ventas f
    LEFT JOIN ventas v ON f.id_venta = v.id
";

if ($buscar !== '') {
    $buscarEsc = $conexion->real_escape_string($buscar);
    $sql .= " WHERE 
        f.codigo_compra LIKE '%{$buscarEsc}%'
        OR f.cliente_nombre LIKE '%{$buscarEsc}%'
        OR f.cliente_identificacion LIKE '%{$buscarEsc}%'
        OR f.estado_sri LIKE '%{$buscarEsc}%'
    ";
}

$sql .= " ORDER BY f.id_factura DESC";

$res = $conexion->query($sql);

function claseEstado($estado){
    switch($estado){
        case 'xml_generado': return 'estado-xml';
        case 'firmado': return 'estado-firmado';
        case 'recibido': return 'estado-recibido';
        case 'autorizado': return 'estado-autorizado';
        case 'rechazado': return 'estado-rechazado';
        default: return 'estado-pendiente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ver Facturas</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{
    font-family: Arial, sans-serif;
    background:#f4f6f9;
    margin:0;
    padding:20px;
    color:#222;
}
h2{
    text-align:center;
    color:#2c3e50;
    margin-bottom:20px;
    font-size:28px;
}
.buscador-box{
    max-width:700px;
    margin:0 auto 25px auto;
    display:flex;
    gap:10px;
}
.buscador-box input{
    flex:1;
    padding:12px 14px;
    border:1px solid #ccc;
    border-radius:10px;
    font-size:15px;
}
.buscador-box button{
    background:#007bff;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:12px 18px;
    cursor:pointer;
    font-weight:bold;
}
.buscador-box button:hover{ background:#0056b3; }

/* TABLA DESKTOP */
.tabla-wrap{
    overflow-x:auto;
    background:#fff;
    border-radius:16px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
    padding:15px;
}
table{
    width:100%;
    border-collapse:collapse;
    min-width:1250px;
}
th, td{
    padding:12px 10px;
    border-bottom:1px solid #eee;
    text-align:left;
    vertical-align:top;
    font-size:14px;
}
th{
    background:#f8f9fa;
    color:#333;
    font-weight:bold;
}
tr:hover{ background:#fafafa; }

.badge{
    display:inline-block;
    padding:6px 11px;
    border-radius:999px;
    font-size:12px;
    font-weight:bold;
    color:#fff;
    text-transform:capitalize;
}
.estado-pendiente .badge{ background:#f39c12; }
.estado-xml .badge{ background:#3498db; }
.estado-firmado .badge{ background:#8e44ad; }
.estado-recibido .badge{ background:#16a085; }
.estado-autorizado .badge{ background:#27ae60; }
.estado-rechazado .badge{ background:#e74c3c; }

.btn{
    border:none;
    border-radius:8px;
    padding:8px 12px;
    cursor:pointer;
    font-size:13px;
    font-weight:bold;
    color:#fff;
    margin:2px;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.btn:hover{ opacity:0.92; }

.btn-ver{ background:#6c757d; }
.btn-xml{ background:#17a2b8; }
.btn-sri{ background:#28a745; }
.btn-pdf{ background:#dc3545; }
.btn-download{ background:#6f42c1; }
.btn-firmar{ background:#fd7e14; }
.btn-disabled{
    background:#adb5bd;
    cursor:not-allowed;
}

.detalle-productos{
    max-width:260px;
    white-space:pre-wrap;
    word-break:break-word;
    font-size:13px;
    color:#444;
}

/* TARJETAS MÓVIL */
.mobile-cards{
    display:none;
    flex-direction:column;
    gap:18px;
}

.factura-card{
    background:#fff;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:18px;
    border-left:7px solid #ccc;
    position:relative;
    overflow:hidden;
}
.factura-card.estado-pendiente{ border-left-color:#f39c12; }
.factura-card.estado-xml{ border-left-color:#3498db; }
.factura-card.estado-firmado{ border-left-color:#8e44ad; }
.factura-card.estado-recibido{ border-left-color:#16a085; }
.factura-card.estado-autorizado{ border-left-color:#27ae60; }
.factura-card.estado-rechazado{ border-left-color:#e74c3c; }

.factura-card .icono{
    position:absolute;
    top:16px;
    right:16px;
    font-size:26px;
    color:#dfe6ec;
}
.factura-card .codigo{
    font-size:14px;
    color:#666;
    font-weight:bold;
    margin-bottom:5px;
    padding-right:35px;
    word-break:break-word;
}
.factura-card .cliente{
    font-size:20px;
    font-weight:bold;
    color:#1f2937;
    margin-bottom:8px;
}
.factura-card .meta{
    font-size:13px;
    color:#666;
    margin-bottom:4px;
}
.factura-card .total{
    font-size:30px;
    font-weight:bold;
    color:#27ae60;
    margin:14px 0 10px 0;
}
.factura-card .mini-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:12px;
}
.factura-card .mini-box{
    background:#f8f9fa;
    border-radius:12px;
    padding:10px;
    font-size:13px;
}
.factura-card .mini-box strong{
    display:block;
    color:#333;
    margin-bottom:4px;
}
.factura-card .productos-box{
    background:#f8f9fa;
    border-radius:12px;
    padding:12px;
    font-size:13px;
    color:#444;
    white-space:pre-wrap;
    margin-top:10px;
    max-height:140px;
    overflow:auto;
}
.factura-card .acciones{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:16px;
}
.factura-card .acciones .btn{
    flex:1 1 calc(50% - 10px);
    justify-content:center;
    min-width:130px;
}

/* MODAL */
.modal-bg{
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.65);
    z-index:9999;
    justify-content:center;
    align-items:center;
    padding:15px;
}
.modal-box{
    background:#fff;
    width:100%;
    max-width:850px;
    max-height:90vh;
    overflow:auto;
    border-radius:18px;
    box-shadow:0 12px 35px rgba(0,0,0,0.35);
    padding:22px;
}
.modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}
.modal-header h3{ margin:0; }
.modal-close{
    background:#dc3545;
    color:#fff;
    border:none;
    padding:7px 12px;
    border-radius:8px;
    cursor:pointer;
}
.pre-box{
    background:#f8f9fa;
    border-radius:12px;
    padding:18px;
    font-family:monospace;
    font-size:14px;
    white-space:pre-wrap;
    line-height:1.6;
}

@media(max-width:768px){
    body{ padding:12px; }
    h2{ font-size:23px; }
    .buscador-box{
        flex-direction:column;
    }
    .tabla-wrap{
        display:none;
    }
    .mobile-cards{
        display:flex;
    }
    .factura-card .acciones .btn{
        flex:1 1 100%;
    }
}
</style>
</head>
<body>

<h2>🧾 Centro de Facturación</h2>

<form class="buscador-box" method="GET">
    <input type="text" name="buscar" placeholder="Buscar por cliente, cédula, estado o código..." value="<?= htmlspecialchars($buscar) ?>">
    <button type="submit"><i class="fas fa-search"></i> Buscar</button>
</form>

<!-- TABLA DESKTOP -->
<div class="tabla-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Cliente</th>
                <th>Identificación</th>
                <th>Método Pago</th>
                <th>Subtotal IVA</th>
                <th>IVA</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Clave Acceso</th>
                <th>XML</th>
                <th>Productos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
                <?php
                    $estado = $row['estado_sri'] ?: 'pendiente';
                    $clase = claseEstado($estado);
                    $productosTexto = '';
                    $productos = json_decode($row['productos'] ?? '[]', true);
                    if (is_array($productos)) {
                        foreach ($productos as $p) {
                            $nombre = $p['producto'] ?? 'Producto';
                            $cant = $p['cantidad'] ?? 0;
                            $precio = $p['precio'] ?? 0;
                            $productosTexto .= "- {$nombre} x{$cant} ($" . number_format((float)$precio, 2) . ")\n";
                        }
                    }
                ?>
                <tr class="<?= $clase ?>">
                    <td><?= (int)$row['id_factura'] ?></td>
                    <td><?= htmlspecialchars($row['codigo_compra']) ?></td>
                    <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($row['cliente_identificacion']) ?></td>
                    <td><?= htmlspecialchars($row['metodo_pago']) ?></td>
                    <td>$<?= number_format((float)$row['subtotal_iva'], 2) ?></td>
                    <td>$<?= number_format((float)$row['iva_total'], 2) ?></td>
                    <td><strong>$<?= number_format((float)$row['total'], 2) ?></strong></td>
                    <td><span class="badge"><?= htmlspecialchars($estado) ?></span></td>
                    <td><?= htmlspecialchars($row['fecha_emision']) ?></td>
                    <td style="font-size:12px; max-width:220px; word-break:break-word;">
                        <?= htmlspecialchars($row['clave_acceso'] ?? '') ?>
                    </td>
                    <td>
                        <?php if(!empty($row['xml_generado'])): ?>
                            <a href="/negocioencontrol/negocios/modulos/xml/generados/<?= urlencode($row['xml_generado']) ?>" target="_blank">Ver XML</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="detalle-productos"><?= htmlspecialchars(trim($productosTexto)) ?></td>
                    <td>
                        <button class="btn btn-ver"
                            onclick="verFactura(
                                <?= (int)$row['id_factura'] ?>,
                                `<?= htmlspecialchars(addslashes($row['codigo_compra'])) ?>`,
                                `<?= htmlspecialchars(addslashes($row['cliente_nombre'])) ?>`,
                                `<?= htmlspecialchars(addslashes($row['cliente_identificacion'])) ?>`,
                                `<?= htmlspecialchars(addslashes($row['metodo_pago'])) ?>`,
                                `<?= number_format((float)$row['subtotal_iva'], 2, '.', '') ?>`,
                                `<?= number_format((float)$row['iva_total'], 2, '.', '') ?>`,
                                `<?= number_format((float)$row['total'], 2, '.', '') ?>`,
                                `<?= htmlspecialchars(addslashes($estado)) ?>`,
                                `<?= htmlspecialchars(addslashes(trim($productosTexto))) ?>`
                            )">
                            <i class="fas fa-eye"></i> Ver
                        </button>

                        <?php if($estado === 'pendiente'): ?>
                            <button class="btn btn-xml" onclick="generarXML(<?= (int)$row['id_factura'] ?>)">
                                <i class="fas fa-file-code"></i> XML
                            </button>
                        <?php else: ?>
                            <button class="btn btn-download" onclick="descargarXML('<?= htmlspecialchars(addslashes($row['xml_generado'] ?? '')) ?>')">
                                <i class="fas fa-download"></i> XML
                            </button>
                        <?php endif; ?>

                        <?php if($estado === 'xml_generado'): ?>
                            <button class="btn btn-firmar" onclick="firmarXML(<?= (int)$row['id_factura'] ?>)">
                                <i class="fas fa-signature"></i> Firmar
                            </button>
                        <?php elseif($estado === 'firmado'): ?>
                            <button class="btn btn-download" onclick="descargarXMLFirmado('<?= htmlspecialchars(addslashes($row['xml_firmado'] ?? '')) ?>')">
                                <i class="fas fa-file-signature"></i> Firmado
                            </button>
                        <?php else: ?>
                            <button class="btn btn-firmar btn-disabled" disabled>
                                <i class="fas fa-signature"></i> Firmar
                            </button>
                        <?php endif; ?>

                        <?php if($estado === 'firmado' || $estado === 'rechazado'): ?>
                            <button class="btn btn-sri" onclick="reenviarSRI(<?= (int)$row['id_factura'] ?>)">
                                <i class="fas fa-paper-plane"></i> Reenviar
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sri btn-disabled" disabled>
                                <i class="fas fa-paper-plane"></i> SRI
                            </button>
                        <?php endif; ?>

                        <button class="btn btn-pdf" onclick="generarPDF(<?= (int)$row['id_factura'] ?>)">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="14" style="text-align:center; color:#888;">No hay facturas registradas</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- TARJETAS MÓVIL -->
<div class="mobile-cards">
<?php $resCards = $conexion->query($sql); ?>
<?php if($resCards && $resCards->num_rows > 0): ?>
    <?php while($row = $resCards->fetch_assoc()): ?>
        <?php
            $estado = $row['estado_sri'] ?: 'pendiente';
            $clase = claseEstado($estado);
            $productosTexto = '';
            $productos = json_decode($row['productos'] ?? '[]', true);

            if (is_array($productos)) {
                foreach ($productos as $p) {
                    $nombre = $p['producto'] ?? 'Producto';
                    $cant = $p['cantidad'] ?? 0;
                    $precio = $p['precio'] ?? 0;
                    $productosTexto .= "- {$nombre} x{$cant} ($" . number_format((float)$precio, 2) . ")\n";
                }
            }
        ?>
        <div class="factura-card <?= $clase ?>">
            <i class="fas fa-file-invoice-dollar icono"></i>

            <div class="codigo"><?= htmlspecialchars($row['codigo_compra']) ?></div>
            <div class="cliente"><?= htmlspecialchars($row['cliente_nombre']) ?></div>
            <div class="meta">CI/RUC: <?= htmlspecialchars($row['cliente_identificacion']) ?></div>
            <div class="meta">Pago: <?= htmlspecialchars($row['metodo_pago']) ?></div>
            <div class="meta">Fecha: <?= htmlspecialchars($row['fecha_emision']) ?></div>
            <div style="margin-top:8px;">
                <span class="badge"><?= htmlspecialchars($estado) ?></span>
            </div>

            <div class="total">$<?= number_format((float)$row['total'], 2) ?></div>

            <div class="mini-grid">
                <div class="mini-box">
                    <strong>Subtotal IVA</strong>
                    $<?= number_format((float)$row['subtotal_iva'], 2) ?>
                </div>
                <div class="mini-box">
                    <strong>IVA</strong>
                    $<?= number_format((float)$row['iva_total'], 2) ?>
                </div>
            </div>

            <?php if(!empty(trim($productosTexto))): ?>
                <div class="productos-box"><?= htmlspecialchars(trim($productosTexto)) ?></div>
            <?php endif; ?>

            <div class="acciones">
                <button class="btn btn-ver"
                    onclick="verFactura(
                        <?= (int)$row['id_factura'] ?>,
                        `<?= htmlspecialchars(addslashes($row['codigo_compra'])) ?>`,
                        `<?= htmlspecialchars(addslashes($row['cliente_nombre'])) ?>`,
                        `<?= htmlspecialchars(addslashes($row['cliente_identificacion'])) ?>`,
                        `<?= htmlspecialchars(addslashes($row['metodo_pago'])) ?>`,
                        `<?= number_format((float)$row['subtotal_iva'], 2, '.', '') ?>`,
                        `<?= number_format((float)$row['iva_total'], 2, '.', '') ?>`,
                        `<?= number_format((float)$row['total'], 2, '.', '') ?>`,
                        `<?= htmlspecialchars(addslashes($estado)) ?>`,
                        `<?= htmlspecialchars(addslashes(trim($productosTexto))) ?>`
                    )">
                    <i class="fas fa-eye"></i> Ver
                </button>

                <?php if($estado === 'pendiente'): ?>
                    <button class="btn btn-xml" onclick="generarXML(<?= (int)$row['id_factura'] ?>)">
                        <i class="fas fa-file-code"></i> Generar XML
                    </button>
                <?php else: ?>
                    <button class="btn btn-download" onclick="descargarXML('<?= htmlspecialchars(addslashes($row['xml_generado'] ?? '')) ?>')">
                        <i class="fas fa-download"></i> Descargar XML
                    </button>
                <?php endif; ?>

                <?php if($estado === 'xml_generado'): ?>
                    <button class="btn btn-firmar" onclick="firmarXML(<?= (int)$row['id_factura'] ?>)">
                        <i class="fas fa-signature"></i> Firmar XML
                    </button>
                <?php elseif($estado === 'firmado'): ?>
                    <button class="btn btn-download" onclick="descargarXMLFirmado('<?= htmlspecialchars(addslashes($row['xml_firmado'] ?? '')) ?>')">
                        <i class="fas fa-file-signature"></i> XML Firmado
                    </button>
                <?php else: ?>
                    <button class="btn btn-firmar btn-disabled" disabled>
                        <i class="fas fa-signature"></i> Firmar XML
                    </button>
                <?php endif; ?>

                <?php if($estado === 'firmado' || $estado === 'rechazado'): ?>
                    <button class="btn btn-sri" onclick="reenviarSRI(<?= (int)$row['id_factura'] ?>)">
                        <i class="fas fa-paper-plane"></i> Reenviar al SRI
                    </button>
                <?php else: ?>
                    <button class="btn btn-sri btn-disabled" disabled>
                        <i class="fas fa-paper-plane"></i> Enviar SRI
                    </button>
                <?php endif; ?>

                <button class="btn btn-pdf" onclick="generarPDF(<?= (int)$row['id_factura'] ?>)">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="factura-card" style="text-align:center; color:#888;">
        No hay facturas registradas
    </div>
<?php endif; ?>
</div>

<!-- MODAL -->
<div class="modal-bg" id="modalFactura">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Vista previa de factura</h3>
            <button class="modal-close" onclick="cerrarModal()">X</button>
        </div>
        <div class="pre-box" id="contenidoFactura"></div>
    </div>
</div>

<script>
function verFactura(id, codigo, cliente, identificacion, metodo, subtotal, iva, total, estado, productos){
    const contenido = `
FACTURA #${id}
Código: ${codigo}
Cliente: ${cliente}
Identificación: ${identificacion}
Método de pago: ${metodo}
Estado SRI: ${estado}

---------------------------
DETALLE DE PRODUCTOS
---------------------------
${productos || 'Sin detalle'}

---------------------------
RESUMEN
---------------------------
Subtotal IVA: $${subtotal}
IVA: $${iva}
TOTAL: $${total}
    `;
    document.getElementById('contenidoFactura').textContent = contenido;
    document.getElementById('modalFactura').style.display = 'flex';
}

function cerrarModal(){
    document.getElementById('modalFactura').style.display = 'none';
}

function generarXML(id_factura){
    if(!confirm("¿Generar XML para esta factura?")) return;

    const formData = new FormData();
    formData.append('id_factura', id_factura);

    fetch('/negocioencontrol/negocios/modulos/xml/generar_xml.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.ok){
            alert(
                "✅ XML generado correctamente\n\n" +
                "Factura ID: " + data.id_factura + "\n" +
                "Clave acceso: " + data.clave_acceso + "\n" +
                "Archivo XML: " + data.archivo_xml
            );
            location.reload();
        } else {
            alert("⚠️ " + (data.mensaje || "No se pudo generar el XML"));
            console.error(data);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error al generar XML");
    });
}

function firmarXML(id_factura){
    if(!confirm("¿Firmar XML de esta factura?")) return;

    const formData = new FormData();
    formData.append('id_factura', id_factura);

    fetch('/negocioencontrol/negocios/modulos/xml/firmar_xml.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.ok){
            alert(
                "✅ XML firmado correctamente\n\n" +
                "Factura ID: " + data.id_factura + "\n" +
                "Clave acceso: " + data.clave_acceso + "\n" +
                "Archivo firmado: " + data.archivo_firmado
            );
            location.reload();
        } else {
            alert("⚠️ " + (data.mensaje || "No se pudo firmar el XML"));
            console.error(data);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error al firmar XML");
    });
}

function descargarXML(nombreArchivo){
    if(!nombreArchivo){
        alert("No hay XML generado para esta factura");
        return;
    }
    window.open('/negocioencontrol/negocios/modulos/xml/generados/' + encodeURIComponent(nombreArchivo), '_blank');
}

function descargarXMLFirmado(nombreArchivo){
    if(!nombreArchivo){
        alert("No hay XML firmado para esta factura");
        return;
    }
    window.open('/negocioencontrol/negocios/modulos/xml/firmados/' + encodeURIComponent(nombreArchivo), '_blank');
}

function reenviarSRI(id_factura){
    alert("⚠️ Próximo paso: aquí conectaremos el envío real al SRI para la factura #" + id_factura);
}

function generarPDF(id_factura){
    alert("🖨️ Próximo paso: aquí generaremos el PDF de la factura #" + id_factura);
}

document.getElementById('modalFactura').addEventListener('click', function(e){
    if(e.target.id === 'modalFactura'){
        cerrarModal();
    }
});
</script>

</body>
</html>