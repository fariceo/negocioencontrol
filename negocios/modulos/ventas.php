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
    <p>
        <strong id="titulo-total">Total general de ventas (Todos):</strong> 
        $<span id="total-general">0.00</span>
    </p>
</div>

<!-- BOTÓN PARA ABRIR MODAL -->
<div style="text-align:center; margin-bottom:15px;">
    <button id="btn-open-modal" class="btn-pag">Abrir filtros</button>
</div>

<!-- CONTENEDOR DE VENTAS -->
<div class="ventas-container" id="ventas-container">
    <p style="text-align:center; color:#777;">Cargando ventas...</p>
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

        <h4>Buscar por día exacto</h4>
        <input type="date" id="fecha_unica">
        <button id="btn-fecha-unica" class="filtro-btn">Buscar día</button>

        <hr>

        <h4>Rango personalizado</h4>
        <input type="date" id="fecha_ini">
        <input type="date" id="fecha_fin">
        <button id="btn-personalizado" class="filtro-btn">Aplicar rango</button>
    </div>
</div>

<style>
.btn-pag {
    background:#1f4e5f;
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    font-weight:bold;
    transition:0.2s;
}
.btn-pag:hover {
    background:#163844;
}

.ventas-container {
    display:flex;
    flex-direction:column;
    gap:15px;
    padding:10px;
}
.venta-card {
    background:#fff;
    border-radius:14px;
    padding:15px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    display:flex;
    flex-direction:column;
    gap:10px;
    border-left:5px solid #1f4e5f;
}
.venta-header, .venta-footer {
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:8px;
    font-size:0.95em;
    color:#333;
}
.venta-productos {
    display:flex;
    flex-direction:column;
    gap:6px;
    padding:8px 0;
    border-top:1px solid #eee;
    border-bottom:1px solid #eee;
}
.producto-item {
    font-size:0.92em;
    color:#555;
    word-break:break-word;
}
.badge {
    padding:5px 10px;
    border-radius:999px;
    color:#fff;
    font-size:0.8em;
    font-weight:bold;
}
.badge-efectivo { background:#27ae60; }
.badge-transferencia { background:#2980b9; }
.badge-credito { background:#f39c12; }
.badge-otro { background:#7f8c8d; }

.venta-total {
    margin-top:8px;
    font-weight:bold;
    text-align:right;
    color:#16a085;
    font-size:1.05em;
}
.fecha-venta {
    cursor:pointer;
    text-decoration:underline;
    color:#2980b9;
    font-weight:bold;
}

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
    padding:15px;
}
.modal-content {
    background:#fff;
    padding:20px;
    border-radius:16px;
    width:100%;
    max-width:420px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
}
.close-modal {
    float:right;
    font-size:22px;
    cursor:pointer;
    font-weight:bold;
}
.filtro-btn {
    background:#1f4e5f;
    color:#fff;
    padding:10px 14px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    margin:6px;
    font-weight:bold;
    transition:0.2s;
}
.filtro-btn:hover {
    background:#163844;
}
input[type="date"] {
    width:90%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
    margin:8px 0;
    font-size:14px;
}
.sin-resultados {
    text-align:center;
    padding:30px 15px;
    color:#777;
    background:#fff;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
}
</style>

<script>
(function(){
    const baseUrl = '/negocioencontrol/negocios/modulos/ventas/';

    function iniciarVentasModulo() {
        const modal = document.getElementById('modal-filtros');
        const btnOpen = document.getElementById('btn-open-modal');
        const btnClose = document.querySelector('.close-modal');
        const ventasContainer = document.getElementById('ventas-container');
        const totalGeneral = document.getElementById('total-general');

        if (!modal || !btnOpen || !ventasContainer || !totalGeneral) {
            console.warn("Módulo ventas no cargó correctamente.");
            return;
        }

        btnOpen.onclick = () => modal.style.display = 'flex';
        if (btnClose) btnClose.onclick = () => modal.style.display = 'none';

        function cargarVentas(params = {}) {
            const qs = new URLSearchParams(params).toString();

            ventasContainer.innerHTML = `
                <p style="text-align:center; color:#777;">Cargando ventas...</p>
            `;

            fetch(baseUrl + 'ventas_ajax.php?' + qs)
                .then(res => res.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);

                       ventasContainer.innerHTML = data.html || '<div class="sin-resultados">No hay ventas registradas.</div>';
totalGeneral.innerText = parseFloat(data.total_general || 0).toFixed(2);

const tituloTotal = document.getElementById('titulo-total');
if (tituloTotal) {
    tituloTotal.innerText = `Total general de ventas (${data.rango_texto || 'Todos'}):`;
}

activarCambiarFecha();
                    } catch (error) {
                        console.error("Respuesta inválida ventas_ajax:", text);
                        ventasContainer.innerHTML = `
                            <div class="sin-resultados">
                                Error al cargar ventas.
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    ventasContainer.innerHTML = `
                        <div class="sin-resultados">
                            No se pudo conectar con el servidor.
                        </div>
                    `;
                });
        }

        function activarCambiarFecha() {
            document.querySelectorAll('.fecha-venta').forEach(span => {
                span.addEventListener('click', function(){
                    const id = this.getAttribute('data-id');
                    const hora = this.getAttribute('data-hora') || '00:00:00';

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
                            cargarVentas();
                            alert("Fecha actualizada correctamente");
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(() => {
                        alert("No se pudo actualizar la fecha");
                    });
                });
            });
        }

        document.querySelectorAll('.filtro-btn[data-filtro]').forEach(btn => {
            btn.onclick = function(){
                const filtro = this.getAttribute('data-filtro');
                if(filtro){
                    modal.style.display = 'none';
                    cargarVentas({ filtro });
                }
            };
        });

        const btnFechaUnica = document.getElementById('btn-fecha-unica');
        if (btnFechaUnica) {
            btnFechaUnica.onclick = function(){
                const fecha = document.getElementById('fecha_unica').value;

                if(!fecha){
                    alert('Selecciona una fecha');
                    return;
                }

                modal.style.display = 'none';
                cargarVentas({ filtro: 'dia', fecha });
            };
        }

        const btnPersonalizado = document.getElementById('btn-personalizado');
        if (btnPersonalizado) {
            btnPersonalizado.onclick = function(){
                const fecha_ini = document.getElementById('fecha_ini').value;
                const fecha_fin = document.getElementById('fecha_fin').value;

                if(!fecha_ini || !fecha_fin){
                    alert('Selecciona rango de fechas');
                    return;
                }

                modal.style.display = 'none';
                cargarVentas({ filtro: 'personalizado', fecha_ini, fecha_fin });
            };
        }

        window.addEventListener('click', function(e){
            if(e.target === modal){
                modal.style.display = 'none';
            }
        });

        cargarVentas();
    }

    setTimeout(iniciarVentasModulo, 50);
})();
</script>