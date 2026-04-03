<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>";
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
?>

<style>
.compras-wrapper {
    background: linear-gradient(145deg, #f9fafb 0%, #e5e7eb 100%);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    max-width: 950px;
    margin: auto;
}

.card-modern {
    border-radius: 20px;
    border: none;
    overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s;
}

.card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.card-modern .card-header {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    padding: 22px;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.6px;
    text-align: center;
    color: #ffffff;
    border-bottom: 1px solid rgba(255,255,255,0.15);
}

.card-modern .card-header div {
    font-size: 0.95rem;
    opacity: 0.8;
    margin-top: 6px;
}

#btnMostrarGastos {
    padding: 14px 24px;
    font-size: 1.15rem;
    border-radius: 14px;
    transition: all 0.25s;
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.25);
}

#btnMostrarGastos:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
}
/* === FORMULARIO MODERNO === */
#formGasto {
    background: #ffffff;
    padding: 45px 40px;
    border-radius: 25px;
    box-shadow: 0 20px 45px rgba(0,0,0,0.08);
    max-width: 700px;
    margin: auto;
    transition: all 0.3s;
}

#formGasto:hover {
    box-shadow: 0 25px 60px rgba(0,0,0,0.12);
}

#formGasto .mb-4 {
    margin-bottom: 1.8rem !important;
}

#formGasto label {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    display: block;
    margin-bottom: 8px;
}

#formGasto input,
#formGasto select,
#formGasto textarea {
    width: 100%;
    border-radius: 16px;
    border: 1px solid #cbd5e1;
    padding: 14px 16px;
    font-size: 1rem;
    color: #1e293b;
    transition: all 0.3s ease;
    text-align: left;
}

#formGasto input:focus,
#formGasto select:focus,
#formGasto textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    outline: none;
}

#formGasto textarea {
    resize: none;
    min-height: 80px;
}

#formGasto button {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    border: none;
    border-radius: 20px;
    font-size: 1.15rem;
    font-weight: 600;
    padding: 14px 50px;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 8px 25px rgba(34,197,94,0.25);
}

#formGasto button:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 35px rgba(34,197,94,0.45);
}

/* Inputs centrados para números */
#formGasto input[type="number"] {
    text-align: center;
}
</style>

<div class="compras-wrapper container mt-3">

    <div class="card card-modern shadow-lg mb-4">

        <!-- HEADER -->
      <div class="card-header text-white text-center fw-bold">
    🧾 Registro de Compras y Gastos
    <div style="font-size:.9rem; opacity:.85;">
        Control claro y ordenado
    </div>
</div>

        <!-- BODY -->
        <div class="card-body">

            <div id="msg" class="mb-3"></div>

            <!-- FORMULARIO -->
            <form id="formGasto" class="mx-auto" style="max-width:700px;">

                <!-- USUARIO -->
                <div class="mb-4">
                    <label class="form-label fw-bold">👤 Usuario</label>
                    <input type="text" name="usuario" class="form-control form-control-lg text-center" required>
                </div>

                <!-- TIPO -->
                <div class="mb-4">
                    <label class="form-label fw-bold">📌 Tipo</label>
                    <select name="tipo" class="form-select form-select-lg text-center" required>
                        <option value="">Seleccione</option>
                        <option value="gasto">Gasto</option>
                        <option value="compra">Compra</option>
                    </select>
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="mb-4">
                    <label class="form-label fw-bold">📝 Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                </div>

                <!-- CANTIDAD / PRECIO -->
                <div class="row g-4 mb-4">

                    <div class="col-md-6">
                        <label class="form-label fw-bold">🔢 Cantidad</label>
                        <input type="number" name="cantidad" class="form-control form-control-lg text-center" min="1" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">💵 Precio unitario</label>
                        <input type="number" step="0.01" name="precio" class="form-control form-control-lg text-center" required>
                    </div>
                </div>

                <!-- BOTÓN -->
                <div class="d-flex justify-content-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-3 shadow" style="font-size:1.1rem;">
                        💾 Guardar registro
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

    <div class="d-flex justify-content-end mb-3">
        <button id="btnMostrarGastos" class="btn btn-primary shadow">
            📋 Ver lista de gastos
        </button>
    </div>

    <!-- MODAL LISTADO -->
    <div id="tablaGastos">

        <h3 class="text-center mb-4 text-white">🧾 Gastos recientes</h3>

        <div style="
            background:#fff;
            padding:25px;
            border-radius:15px;
            max-width:1100px;
            margin:auto;
            position:relative;
            overflow: visible;
        ">
            <button id="btnCerrarGastos" class="btn btn-danger">
                ✖
            </button>

            <div id="contenidoGastos" class="row g-4 mt-2"></div>
        </div>

    </div>

</div>

<style>
/* === MODAL OSCURO === */
#tablaGastos {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(6px);
    overflow-y: auto;
    z-index: 9999;
    padding: 30px;
}

/* BOTÓN CERRAR (CORREGIDO PARA MÓVIL) */
#btnCerrarGastos {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10001;
    padding: 8px 12px;
    border-radius: 50%;
    border: none;
    background: #ef4444;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

#btnCerrarGastos:hover {
    background: #dc2626;
    transform: scale(1.05);
}

/* Ajuste móvil */
@media (max-width: 576px) {
    #btnCerrarGastos {
        top: 8px;
        right: 8px;
        padding: 6px 10px;
        font-size: 14px;
    }
}

/* Tarjetas */
.gasto-card {
    background: #334155;
    color: #f1f5f9;
    border-radius: 12px;
    padding: 18px;
    transition: 0.2s;
    box-shadow: 0 0 10px rgba(0,0,0,.25);
}

.gasto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,.35);
}
</style>

<script>
(function(){

    const basePath = "/negocioencontrol/negocios/compras";

    document.getElementById("formGasto").addEventListener("submit", function(e){
        e.preventDefault();
        const datos = new FormData(this);

        fetch(basePath + "/guardar_gasto_accion.php", {
            method: "POST",
            body: datos
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById("msg").innerHTML =
                `<div class="alert alert-${data.ok ? "success" : "danger"} text-center">${data.msg}</div>`;
            if(data.ok){
                this.reset();
                cargarGastos();
            }
        });
    });

    document.getElementById("btnMostrarGastos").addEventListener("click", () => {
        document.getElementById("tablaGastos").style.display = "block";
        cargarGastos();
    });

    document.getElementById("btnCerrarGastos").addEventListener("click", () => {
        document.getElementById("tablaGastos").style.display = "none";
    });

    function cargarGastos(){
        fetch(basePath + "/listado_gastos.php")
        .then(res => res.json())
        .then(data => {
            const cont = document.getElementById("contenidoGastos");
            cont.innerHTML = "";

            if(!data.length){
                cont.innerHTML = "<p class='text-center text-muted'>⚠️ No hay gastos registrados.</p>";
                return;
            }

            data.forEach(g => {
                cont.innerHTML += `
                    <div class="col-md-4">
                        <div class="card shadow gasto-card">
                            <div class="card-header bg-primary text-white fw-bold">
                                ${g.usuario} — ${g.tipo}
                            </div>
                            <div class="card-body">
                                <p><strong>Descripción:</strong> ${g.descripcion}</p>
                                <p><strong>Cantidad:</strong> ${g.cantidad}</p>
                                <p><strong>Precio:</strong> $${parseFloat(g.precio).toFixed(2)}</p>
                                <hr>
                                <p class="fw-bold">Total: $${(g.cantidad * g.precio).toFixed(2)}</p>
                            </div>
                            <div class="card-footer text-muted text-end">
                                <small>${g.fecha}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
    }

})();
</script>
