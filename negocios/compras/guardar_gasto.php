<?php
session_start();
if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>";
    exit;
}
?>

<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white text-center">
            <h3 class="mb-0">📊 Gestión de Gastos</h3>
        </div>
        <div class="card-body">
            <!-- Mensajes -->
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
      <a href="#" 
   class="btn btn-secondary px-4 abrir-modulo" 
   data-ruta="/negocioencontrol/negocios/modulos/bodega.php">
   📦 Ir a Bodega
</a>

<script>
// Botón que abre módulo en el index.php
document.querySelectorAll('.abrir-modulo').forEach(a => {
    a.addEventListener('click', function(e){
        e.preventDefault();
        const ruta = this.dataset.ruta;

        // Llamamos al index.php (padre) y buscamos el div #contenido
        const contenidoIndex = window.parent.document.getElementById('contenido');
        if(!contenidoIndex){
            alert("❌ No se encontró el div #contenido en index.php");
            return;
        }

        fetch(ruta)
        .then(res => res.text())
        .then(html => {
            contenidoIndex.innerHTML = html;
            // Reiniciar scroll al inicio
            contenidoIndex.scrollTop = 0;

            // Ejecutar scripts internos del módulo
            const scripts = contenidoIndex.querySelectorAll('script');
            scripts.forEach(s => {
                const newScript = document.createElement('script');
                if(s.src) newScript.src = s.src;
                else newScript.textContent = `(function(){ ${s.textContent} })();`;
                document.body.appendChild(newScript);
                s.remove();
            });
        })
        .catch(err => {
            alert("❌ No se pudo cargar el módulo: " + err.message);
            console.error(err);
        });
    });
});
</script>


                </div>
            </form>
        </div>
    </div>

    <!-- Botón para mostrar listado -->
    <div class="d-flex justify-content-end mb-3">
        <button id="btnMostrarGastos" class="btn btn-primary">📋 Ver últimos gastos</button>
    </div>

    <!-- Modal de gastos -->
    <div id="tablaGastos" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
         background:rgba(0,0,0,0.7); overflow:auto; z-index:9999; padding:40px;">
         <h3 style="text-align:center; margin-bottom:20px;color:white">Gastos</h3>
        <div style="background:#fff; padding:20px; border-radius:10px; max-width:1200px; margin:auto; position:relative;">
            <!-- Botón Cerrar siempre encima -->
            <button id="btnCerrarGastos" style="
                position:absolute; 
                top:-15px; 
                right:-15px; 
                z-index:10000; 
                border-radius:50%; 
                padding:10px 12px;" 
                class="btn btn-danger">✖</button>

            <div id="contenidoGastos" class="row g-3">
                <!-- Aquí se cargarán las tarjetas con JS -->
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<script>
// Enviar formulario por fetch
document.getElementById("formGasto").addEventListener("submit", function(e){
    e.preventDefault();

    const datos = new FormData(this);

    fetch("compras/guardar_gasto_accion.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("msg").innerHTML =
            `<div class="alert alert-${data.ok ? "success" : "danger"} text-center">${data.msg}</div>`;

        if(data.ok){
            this.reset();
            cargarGastos(); // refresca listado si modal está abierto
        }
    })
    .catch(err => {
        document.getElementById("msg").innerHTML =
            `<div class="alert alert-danger text-center">❌ Error: ${err.message}</div>`;
    });
});

// Abrir modal
document.getElementById("btnMostrarGastos").addEventListener("click", function(){
    document.getElementById("tablaGastos").style.display = "block";
    cargarGastos();
});

// Cerrar modal
document.getElementById("btnCerrarGastos").addEventListener("click", function(){
    document.getElementById("tablaGastos").style.display = "none";
});

// Función para cargar los gastos en tarjetas
function cargarGastos(){
    fetch("compras/guardar_gasto_accion.php?listar=1")
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
                        <p><strong>Total:</strong> $${parseFloat(gasto.total).toFixed(2)}</p>
                    </div>
                    <div class="card-footer text-muted text-end">
                        ${gasto.fecha}
                    </div>
                </div>
            `;
            cont.appendChild(card);
        });
    })
    .catch(err => {
        document.getElementById("contenidoGastos").innerHTML =
            `<p style="color:red; text-align:center;">❌ Error al cargar gastos: ${err.message}</p>`;
    });
}
</script>

<script>// Botones que abren módulos en index.php
document.querySelectorAll('.abrir-modulo').forEach(a => {
    a.addEventListener('click', function(e){
        e.preventDefault();
        const ruta = this.dataset.ruta;

        // Usamos la misma función que index.php para cargar módulos
        fetch(ruta)
        .then(res => res.text())
        .then(html => {
            // Buscamos el div #contenido del index.php y reemplazamos su contenido
            const contenidoIndex = window.parent.document.getElementById('contenido');
            contenidoIndex.innerHTML = html;

            // Ejecutar scripts internos del módulo
            const scripts = contenidoIndex.querySelectorAll('script');
            scripts.forEach(s => {
                const newScript = document.createElement('script');
                if(s.src) newScript.src = s.src;
                else newScript.textContent = `(function(){ ${s.textContent} })();`;
                document.body.appendChild(newScript);
                s.remove();
            });
        })
        .catch(err => {
            alert("❌ No se pudo cargar el módulo: " + err.message);
            console.error(err);
        });
    });
});
</script>