<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p>No autorizado</p>";
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $conexion->real_escape_string($_SESSION['nombre_bd_negocio']);
?>

<div class="ventas-header" style="text-align:center; margin-bottom:15px;">
    <h2>📊 Ventas realizadas</h2>
    <p><strong>Total general de ventas:</strong> <span id="total-general">0</span></p>
</div>

<!-- BOTÓN PARA ABRIR MODAL -->
<div style="text-align:center; margin-bottom:15px;">
    <button id="btn-open-modal" class="btn-pag">Abrir filtros</button>
</div>

<!-- CONTENEDOR DE VENTAS -->
<div class="ventas-container" id="ventas-container">
    <!-- Aquí se cargan las ventas por AJAX -->
</div>

<!-- MODAL -->
<div id="modal-filtros" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Selecciona un filtro</h3>

        <button class="filtro-btn" data-filtro="todos">Todos</button>
        <button class="filtro-btn" data-filtro="hoy">Hoy</button>
        <button class="filtro-btn" data-filtro="ayer">Ayer</button>
        <button class="filtro-btn" data-filtro="semana">Esta semana</button>
        <button class="filtro-btn" data-filtro="mes">Este mes</button>

        <hr>

        <h4>Personalizado</h4>
        <input type="date" id="fecha_ini">
        <input type="date" id="fecha_fin">
        <button id="btn-personalizado" class="filtro-btn">Aplicar</button>
    </div>
</div>

<style>
/* BOTONES */
.btn-pag {
    background:#2980b9;
    color:#fff;
    padding:8px 12px;
    border-radius:5px;
    border:none;
    cursor:pointer;
}

/* VENTAS */
.ventas-container {
    display:flex;
    flex-direction:column;
    gap:15px;
    padding:10px;
}
.venta-card {
    background:#fff;
    border-radius:10px;
    padding:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    display:flex;
    flex-direction:column;
    gap:10px;
}
.venta-header, .venta-footer {
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    font-size:0.95em;
    color:#333;
}
.venta-productos {
    display:flex;
    flex-direction:column;
    gap:4px;
    padding:5px 0;
    border-top:1px solid #eee;
    border-bottom:1px solid #eee;
}
.producto-item {
    font-size:0.9em;
    color:#555;
}
.badge {
    padding:4px 8px;
    border-radius:5px;
    color:#fff;
    font-size:0.8em;
}
.badge-efectivo { background:#27ae60; }
.badge-transferencia { background:#2980b9; }
.badge-credito { background:#f39c12; }
.venta-total {
    margin-top:8px;
    font-weight:bold;
    text-align:right;
    color:#16a085;
}
.fecha-venta {
    cursor:pointer;
    text-decoration:underline;
    color:#2980b9;
}

/* MODAL */
.modal {
    display:none;
    position:fixed;
    z-index:1000;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
}
.modal-content {
    background:#fff;
    padding:20px;
    border-radius:10px;
    width:90%;
    max-width:400px;
    text-align:center;
}
.close-modal {
    float:right;
    font-size:20px;
    cursor:pointer;
}
.filtro-btn {
    background:#2980b9;
    color:#fff;
    padding:8px 12px;
    border-radius:5px;
    border:none;
    cursor:pointer;
    margin:5px;
}
</style>

<script>
const baseUrl = '/negocioencontrol/negocios/modulos/ventas/';

// Modal
const modal = document.getElementById('modal-filtros');
document.getElementById('btn-open-modal').onclick = () => modal.style.display = 'flex';
document.querySelector('.close-modal').onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if(e.target == modal) modal.style.display = 'none'; }

// Cargar ventas
function cargarVentas(params = {}) {
    const qs = new URLSearchParams(params).toString();

    fetch(baseUrl + 'ventas_ajax.php?' + qs)
        .then(res => res.json())
        .then(data => {
            document.getElementById('ventas-container').innerHTML = data.html;
            document.getElementById('total-general').innerText = data.total_general.toFixed(2);
            activarCambiarFecha();
        });
}

// Cambiar fecha
function activarCambiarFecha() {
    document.querySelectorAll('.fecha-venta').forEach(span => {
        span.addEventListener('click', function(){
            const id = this.getAttribute('data-id');
            const hora = this.getAttribute('data-hora');

            const hoy = new Date();
            const hoyStr = hoy.toISOString().split('T')[0];

            const nuevaFecha = prompt("Ingrese la nueva fecha (YYYY-MM-DD)", hoyStr);
            if(!nuevaFecha) return;

            const regex = /^\d{4}-\d{2}-\d{2}$/;
            if(!regex.test(nuevaFecha)){
                alert("Formato incorrecto. Use: YYYY-MM-DD");
                return;
            }

            const fechaHora = `${nuevaFecha} ${hora}`;

            fetch(baseUrl + 'actualizar_fecha.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, fecha: fechaHora })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    cargarVentas(); // recargar lista
                    alert("Fecha actualizada correctamente");
                } else {
                    alert("Error: " + data.message);
                }
            });
        });
    });
}

// EVENTOS DEL MODAL
document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        const filtro = this.getAttribute('data-filtro');
        if(filtro){
            modal.style.display = 'none';
            cargarVentas({ filtro });
        }
    });
});

// Aplicar personalizado
document.getElementById('btn-personalizado').addEventListener('click', function(){
    const fecha_ini = document.getElementById('fecha_ini').value;
    const fecha_fin = document.getElementById('fecha_fin').value;

    if(!fecha_ini || !fecha_fin){
        alert('Selecciona rango de fechas');
        return;
    }

    modal.style.display = 'none';
    cargarVentas({ filtro: 'personalizado', fecha_ini, fecha_fin });
});

// CARGAR INICIAL
cargarVentas();
</script>
