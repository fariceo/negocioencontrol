<?php include 'conexion_encontrol.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustes - Negocio en Control</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; }
        .sidebar { min-height: 100vh; background: #343a40; padding: 20px; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px; border-radius: 5px; cursor:pointer; }
        .sidebar a:hover { background: #495057; }
        .sidebar a.active { background: #28a745; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <a href='negocioencontrol.php'>inicio</a>
<div class="container-fluid">
    <div class="row">
        <!-- Menú lateral -->
        <nav class="col-md-3 col-lg-2 sidebar">
            <h4 class="text-white">⚙️ Ajustes</h4>  
            <a data-modulo="general_encontrol.php" class="active">📌 General</a>
            <a href="ajustes_encontrol/imagen.php">🖼️ Imagen Corporativa</a>
            <a data-modulo="usuarios.php">👤 Usuarios</a>
            <a data-modulo="ventas.php">💰 Ventas</a>
            <a data-modulo="notificaciones.php">🔔 Notificaciones</a>
            <a data-modulo="delivery.php">🚚 Pedidos & Delivery</a>
            <a data-modulo="inventario.php">📦 Inventario</a>
            <a data-modulo="pagos.php">💳 Pagos</a>
            <a data-modulo="respaldos.php">🛠️ Respaldos</a>
            <a data-modulo="otros.php">🌍 Otros</a>
        </nav>

        <!-- Contenedor dinámico -->
        <main class="col-md-9 col-lg-10 content">
            <div id="contenedor">
                <h3>Bienvenido a los ajustes</h3>
                <p>Seleccione una opción del menú para empezar.</p>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Cuando se hace clic en un botón del menú
document.querySelectorAll('.sidebar a').forEach(btn => {
    btn.addEventListener('click', function() {
        // Quitar "active" a todos
        document.querySelectorAll('.sidebar a').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Ruta del módulo a cargar
        let modulo = this.getAttribute('data-modulo');

        // Cargar el contenido con fetch
        fetch("ajustes_encontrol/" + modulo)
            .then(res => res.text())
            .then(html => {
                document.getElementById('contenedor').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('contenedor').innerHTML = "<p class='text-danger'>Error cargando el módulo.</p>";
                console.error(err);
            });
    });
});

// Opcional: cargar "General" por defecto al inicio
window.addEventListener("DOMContentLoaded", () => {
    document.querySelector('.sidebar a.active').click();
});
</script>
</body>
</html>
