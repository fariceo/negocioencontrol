<?php
session_start();
if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>";
    exit;
}

include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
?>

<div class="container mt-3">
    <div class="card shadow-lg border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white text-center">
            <h3 class="mb-0">📦 Gestión de Bodega</h3>
        </div>
        <div class="card-body">
            <div id="msgBodega"></div>
            <form id="formBodega">
                <div class="mb-3">
                    <label class="form-label">📝 Producto</label>
                    <input type="text" name="producto" class="form-control" required>
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
                <button type="submit" class="btn btn-success">💾 Guardar</button>
            </form>
        </div>
    </div>

    <h4>📋 Productos en Bodega</h4>
    <div id="listadoBodega" class="row g-3"></div>
</div>

<script>
// Función para cargar productos de bodega
function cargarBodega() {
    fetch("/negocioencontrol/negocios/modulos/guardar_bodega.php?listar=1")
    .then(res => res.json())
    .then(data => {
        const cont = document.getElementById("listadoBodega");
        cont.innerHTML = "";
        if(data.length === 0){
            cont.innerHTML = "<p class='text-center'>⚠️ No hay productos registrados.</p>";
            return;
        }

        data.forEach(p => {
            const card = document.createElement("div");
            card.className = "col-md-4";
            card.innerHTML = `
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p><strong>Producto:</strong> ${p.producto}</p>
                        <p><strong>Cantidad:</strong> ${p.cantidad}</p>
                        <p><strong>Precio:</strong> $${parseFloat(p.precio).toFixed(2)}</p>
                        <p><strong>Total:</strong> $${parseFloat(p.total).toFixed(2)}</p>
                    </div>
                </div>
            `;
            cont.appendChild(card);
        });
    });
}

// Manejo del submit del formulario
document.getElementById("formBodega").addEventListener("submit", function(e){
    e.preventDefault();
    const datos = new FormData(this);
    fetch("/negocioencontrol/negocios/modulos/bodega.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("msgBodega").innerHTML = 
            `<div class="alert alert-${data.ok ? "success" : "danger"} text-center">${data.msg}</div>`;
        if(data.ok){
            this.reset();
            cargarBodega();
        }
    })
    .catch(err => {
        document.getElementById("msgBodega").innerHTML = 
            `<div class="alert alert-danger text-center">❌ Error: ${err.message}</div>`;
    });
});

// Cargar listado al ab
<?php
session_start();
if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Sesión no válida</p>";
    exit;
}

include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];
?>

<div class="container mt-3">
    <div class="card shadow-lg border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white text-center">
            <h3 class="mb-0">📦 Gestión de Bodega</h3>
        </div>
        <div class="card-body">
            <div id="msgBodega"></div>
            <form id="formBodega">
                <div class="mb-3">
                    <label class="form-label">📝 Producto</label>
                    <input type="text" name="producto" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">📝 Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
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
                <button type="submit" class="btn btn-success">💾 Guardar</button>
            </form>
        </div>
    </div>

    <h4>📋 Productos en Bodega</h4>
    <div id="listadoBodega" class="row g-3"></div>
</div>

<script>
// Cargar productos de bodega
function cargarBodega(){
    fetch("/negocioencontrol/negocios/modulos/guardar_bodega.php?listar=1")
    .then(res=>res.json())
    .then(data=>{
        const cont=document.getElementById("listadoBodega");
        cont.innerHTML="";
        if(data.length===0){
            cont.innerHTML="<p class='text-center'>⚠️ No hay productos registrados.</p>";
            return;
        }
        data.forEach(p=>{
            const card=document.createElement("div");
            card.className="col-md-4";
            card.innerHTML=`
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p><strong>Producto:</strong> ${p.producto}</p>
                        <p><strong>Descripción:</strong> ${p.descripcion}</p>
                        <p><strong>Cantidad:</strong> ${p.cantidad}</p>
                        <p><strong>Precio:</strong> $${parseFloat(p.precio).toFixed(2)}</p>
                        <p><strong>Total:</strong> $${parseFloat(p.cantidad*p.precio).toFixed(2)}</p>
                        <p><strong>Fecha:</strong> ${p.fecha_registro}</p>
                    </div>
                </div>
            `;
            cont.appendChild(card);
        });
    });
}

// Guardar producto
document.getElementById("formBodega").addEventListener("submit", function(e){
    e.preventDefault();
    const datos = new FormData(this);
    fetch("/negocioencontrol/negocios/modulos/guardar_bodega.php", {
        method: "POST",
        body: datos
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("msgBodega").innerHTML =
            `<div class="alert alert-${data.ok?"success":"danger"} text-center">${data.msg}</div>`;
        if(data.ok){
            this.reset();
            cargarBodega();
        }
    })
    .catch(err=>{
        document.getElementById("msgBodega").innerHTML =
            `<div class="alert alert-danger text-center">❌ Error: ${err.message}</div>`;
    });
});

// Cargar listado al abrir módulo
cargarBodega();
</script>
<?php
session_start();
if(!isset($_SESSION['nombre_bd_negocio'])){
    echo json_encode(["ok"=>false,"msg"=>"⚠️ Sesión no válida"]);
    exit;
}

include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";
$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);
$negocio = $_SESSION['nombre_bd_negocio'];

// Listar productos
if(isset($_GET['listar'])){
    $sql="SELECT *, cantidad*precio AS total FROM bodega ORDER BY fecha_registro DESC";
    $res=$conexion->query($sql);
    $data=[];
    while($row=$res->fetch_assoc()){
        $data[]=$row;
    }
    echo json_encode($data);
    exit;
}

// Insertar producto
$producto = $_POST['producto'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$cantidad = $_POST['cantidad'] ?? 0;
$precio = $_POST['precio'] ?? 0;

if($producto && $cantidad>0 && $precio>=0){
    $stmt = $conexion->prepare("INSERT INTO bodega (negocio, producto, descripcion, cantidad, precio, fecha_registro) VALUES (?,?,?,?,?,NOW())");
    $stmt->bind_param("sssid", $negocio, $producto, $descripcion, $cantidad, $precio);
    if($stmt->execute()){
        echo json_encode(["ok"=>true,"msg"=>"Producto agregado correctamente"]);
    } else {
        echo json_encode(["ok"=>false,"msg"=>"Error al guardar producto"]);
    }
} else {
    echo json_encode(["ok"=>false,"msg"=>"Datos incompletos o inválidos"]);
}
?>

