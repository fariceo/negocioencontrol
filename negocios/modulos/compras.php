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
<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white text-center">
            <h3 class="mb-0">📊 Compras / Gastos</h3>
        </div>
        <div class="card-body">
            <div id="msg"></div>

            <!-- Formulario -->
            <form id="formGasto">
                <div class="mb-3">
                    <label class="form-label">👤 Usuario</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">📌 Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="gasto">Gasto</option>
                        <option value="bodega">Bodega</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">📝 Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">🔢 Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">💵 Precio unitario</label>
                        <input type="number" step="0.01" name="precio" class="form-control" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" class="btn btn-success px-4">💾 Guardar</button>
                    <a href="#" class="btn btn-secondary px-4 abrir-modulo" data-ruta="/negocioencontrol/negocios/compras/ingresar_gasto.php">
                        📦 Ir a Bodega
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button id="btnMostrarGastos" class="btn btn-primary">📋 Listado</button>
    </div>

    <div id="tablaGastos" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
         background:rgba(0,0,0,0.7); overflow:auto; z-index:9999; padding:40px;">
         <h3 style="text-align:center; margin-bottom:20px;color:white">🧾 Gastos recientes</h3>
        <div style="background:#fff; padding:20px; border-radius:10px; max-width:1200px; margin:auto; position:relative;">
            <button id="btnCerrarGastos" style="position:absolute; top:-15px; right:-15px; z-index:10000; border-radius:50%; padding:10px 12px;" class="btn btn-danger">✖</button>
            <div id="contenidoGastos" class="row g-3"></div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
            if(data.length === 0){
                cont.innerHTML = "<p class='text-center'>⚠️ No hay gastos registrados.</p>";
                return;
            }
            data.forEach(gasto => {
                const card = document.createElement("div");
                card.className = "col-md-4";
                card.innerHTML = `
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            ${gasto.usuario} - ${gasto.tipo}
                        </div>
                        <div class="card-body">
                            <p><strong>Descripción:</strong> ${gasto.descripcion}</p>
                            <p><strong>Cantidad:</strong> ${gasto.cantidad}</p>
                            <p><strong>Precio:</strong> $${parseFloat(gasto.precio).toFixed(2)}</p>
                            <p><strong>Total:</strong> $${(gasto.cantidad * gasto.precio).toFixed(2)}</p>
                        </div>
                        <div class="card-footer text-muted text-end">
                            ${gasto.fecha}
                        </div>
                    </div>
                `;
                cont.appendChild(card);
            });
        });
    }
})();
</script>
