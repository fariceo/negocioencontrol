<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    die("<p style='color:red;text-align:center;'>⚠ Sesión no válida</p>");
}

$negocio = $_SESSION['nombre_bd_negocio'];
$conexion = new mysqli("localhost", "root", "clave", $negocio);

// --- Lista de módulos disponibles ---
$modulos_disponibles = [
    ['modulo' => 'reporte_ventas', 'etiqueta' => 'Reporte de Ventas', 'icono' => 'fas fa-chart-line'],
    ['modulo' => 'reporte_gastos', 'etiqueta' => 'Reporte de Gastos', 'icono' => 'fas fa-receipt'],
    ['modulo' => 'reporte_clientes', 'etiqueta' => 'Reporte de Clientes', 'icono' => 'fas fa-users'],
    ['modulo' => 'pagos', 'etiqueta' => 'Pagos', 'icono' => 'fas fa-money-bill-wave'],
    ['modulo' => 'fiados', 'etiqueta' => 'Fiados / Créditos', 'icono' => 'fas fa-hand-holding-usd']
];

// --- Peticiones AJAX ---
if (isset($_POST['accion'])) {

    // AGREGAR
    if ($_POST['accion'] === "agregar") {

        // guardamos la ruta absoluta del módulo en el servidor (no confiar ciegamente en lo enviado por el cliente)
        $ruta = "/negocioencontrol/negocios/modulos/" . $_POST['modulo'] . ".php";

        $sql = "INSERT IGNORE INTO ajustes_menu (modulo, etiqueta, icono, ruta, habilitado, orden)
                SELECT ?, ?, ?, ?, 1, COALESCE(MAX(orden)+1,1) FROM ajustes_menu";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $_POST['modulo'], $_POST['etiqueta'], $_POST['icono'], $ruta);
        $stmt->execute();

        exit("ok");
    }

    // QUITAR
    if ($_POST['accion'] === "quitar") {
        $stmt = $conexion->prepare("DELETE FROM ajustes_menu WHERE modulo=?");
        $stmt->bind_param("s", $_POST['modulo']);
        $stmt->execute();
        exit("ok");
    }

    // ORDEN
    if ($_POST['accion'] === "orden") {
        // Si el cliente envía orden[] como array, $_POST['orden'] será array
        if (!empty($_POST['orden']) && is_array($_POST['orden'])) {
            foreach ($_POST['orden'] as $pos => $mod) {
                $stmt = $conexion->prepare("UPDATE ajustes_menu SET orden=? WHERE modulo=?");
                $stmt->bind_param("is", $pos, $mod);
                $stmt->execute();
            }
        }
        exit("ok");
    }
}

// Obtener módulos instalados
$res = $conexion->query("SELECT modulo FROM ajustes_menu ORDER BY orden ASC");
$modulos_agregados = [];
while ($row = $res->fetch_assoc()) $modulos_agregados[$row['modulo']] = true;
?>

<style>
/* estilos del módulo */
#modulos_admin { font-family:Arial;margin:0;padding:0; }
#modulos_admin h2 { background:#34495e;color:#fff;padding:10px;margin:0;border-radius:6px; }
#modulos_admin p { margin:5px 0 12px; }

#modulos_admin .modulo {
    border:1px solid #ccc; padding:12px; margin-bottom:10px;
    border-radius:8px; display:flex; justify-content:space-between; align-items:center;
    background:#f9f9f9; cursor:grab;
}
#modulos_admin .modulo.dragging { opacity:.5; }

#modulos_admin button {
    padding:6px 12px; border:none; border-radius:5px; cursor:pointer;
}
#modulos_admin button.agregar { background:#28a745;color:#fff; }
#modulos_admin button.quitar  { background:#dc3545;color:#fff; }

/* notificaciones */
.notify-box {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #2ecc71;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    z-index: 99999;
    opacity: 0;
    transform: translateY(-15px);
    transition: opacity .3s ease, transform .3s ease;
    pointer-events: none;
}
.notify-box.error { background: #e74c3c; }
.notify-box i { font-size: 18px; }
.show-msg { opacity: 1; transform: translateY(0); pointer-events: auto; }
</style>

<div id="modulos_admin">
    <h2>⚙ Gestionar Módulos Avanzado</h2>
    <p>Arrastra módulos para reordenarlos. Agrega o quita libremente.</p>

    <div id="lista_modulos">
    <?php foreach($modulos_disponibles as $m): ?>
        <div class="modulo" draggable="true" data-modulo="<?= htmlspecialchars($m['modulo']) ?>">
            <span><i class="<?= htmlspecialchars($m['icono']) ?>"></i> <?= htmlspecialchars($m['etiqueta']) ?></span>

            <?php if(isset($modulos_agregados[$m['modulo']])): ?>
                <button class="quitar">Quitar</button>
            <?php else: ?>
                <button class="agregar">Agregar</button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- contenedor de notificaciones -->
<div id="notificacion" class="notify-box" style="display:none;">
    <i class="fas fa-info-circle"></i>
    <span id="msg-text"></span>
</div>

<script>
(function(){

const lista = document.getElementById("lista_modulos");
const baseUrl = "/negocioencontrol/negocios/modulos/modulos_adicionales.php"; // ruta absoluta al archivo

// función de notificación
function notificar(msg, tipo="ok", callback=null) {
    const box = document.getElementById("notificacion");
    const msgText = document.getElementById("msg-text");
    const icon = box.querySelector("i");

    msgText.textContent = msg;
    box.classList.remove("error");

    if(tipo === "error") {
        box.classList.add("error");
        icon.className = "fas fa-times-circle";
    } else {
        icon.className = "fas fa-check-circle";
    }

    // mostrar (aseguramos display block)
    box.style.display = "flex";
    // forzar reflow para animación si ya estaba visible
    void box.offsetWidth;
    box.classList.add("show-msg");

    // ocultar después de 2.5s y ejecutar callback (recargar)
    setTimeout(() => {
        box.classList.remove("show-msg");
        // ocultar del DOM visual tras transición
        setTimeout(() => box.style.display = "none", 300);
        if (callback) setTimeout(callback, 300);
    }, 2500);
}

// --- AGREGAR / QUITAR ---
lista.addEventListener("click", e=>{
    const btn = e.target;
    if(btn.tagName !== "BUTTON") return;

    const box = btn.closest(".modulo");
    const modulo = box.dataset.modulo;

    // AGREGAR
    if(btn.classList.contains("agregar")){
        const icono = box.querySelector("i").className;
        // tomar solo el texto del span (no del botón)
        const etiqueta = box.querySelector("span").textContent.trim();
        const ruta = "/negocioencontrol/negocios/modulos/" + modulo + ".php";

        enviar("agregar", { modulo, etiqueta, icono, ruta });
    }

    // QUITAR
    if(btn.classList.contains("quitar")){
        enviar("quitar", { modulo });
    }
});

// Enviar acciones al servidor (maneja arrays para 'orden')
function enviar(accion, data){
    const fd = new FormData();
    fd.append("accion", accion);

    for(const k in data) {
        if (Array.isArray(data[k])) {
            // si es array lo enviamos como orden[] (esperado por el servidor)
            data[k].forEach(v => fd.append(k + "[]", v));
        } else {
            fd.append(k, data[k]);
        }
    }

    fetch(baseUrl, { method:"POST", body: fd })
    .then(r => r.text())
    .then(resp => {

        // AGREGAR — mensaje con nombre exacto
        if(accion === "agregar"){
            notificar(`Módulo ${data.modulo} agregado`, "ok", () => location.reload());
        }

        // QUITAR — mensaje con nombre exacto
        else if(accion === "quitar"){
            notificar(`Módulo ${data.modulo} eliminado`, "error", () => location.reload());
        }

        // ORDEN — mensaje simple
        else if(accion === "orden"){
            notificar(`Orden de módulos guardada`, "ok", () => location.reload());
        }

    })
    .catch(err => {
        console.error(err);
        notificar("Error en la petición: " + (err.message || "network"), "error");
    });
}


// --- DRAG & DROP ---
let dragItem = null;

function actualizarListeners() {
    // (por si se re-renderiza la lista en el futuro)
    document.querySelectorAll(".modulo").forEach(el=>{
        // aseguramos listeners en cada elemento (no duplicar)
        el.removeEventListener("dragstart", handleDragStart);
        el.removeEventListener("dragend", handleDragEnd);
        el.addEventListener("dragstart", handleDragStart);
        el.addEventListener("dragend", handleDragEnd);
    });
}

function handleDragStart(e){
    dragItem = this;
    this.classList.add("dragging");
}
function handleDragEnd(e){
    if (this) this.classList.remove("dragging");
    dragItem = null;
}

actualizarListeners();

lista.addEventListener("dragover", e=>{
    e.preventDefault();
    const after = [...lista.querySelectorAll(".modulo:not(.dragging)")].find(item => {
        return e.clientY <= item.offsetTop + item.offsetHeight/2;
    });
    if(after && dragItem) lista.insertBefore(dragItem, after);
    else if(dragItem) lista.appendChild(dragItem);
});

lista.addEventListener("drop", ()=>{
    // construir array de orden actual
    const orden = [...lista.querySelectorAll(".modulo")].map(el=>el.dataset.modulo);

    // enviar mediante la función única (maneja arrays)
    enviar("orden", { orden: orden });
});

})();
</script>
