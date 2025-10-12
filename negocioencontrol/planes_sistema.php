<?php
// procesar_formulario.php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plan = $_POST['plan'] ?? 'No seleccionado';
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nombre && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Aquí podrías guardar en base de datos, enviar email, etc.
        $mensaje = "Gracias, $nombre. Hemos recibido tu interés en el plan '$plan'. Pronto nos pondremos en contacto.";
    } else {
        $error = "Por favor, ingresa un nombre y un correo válido.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Planes para Negocios en Control</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f8fa;
            margin: 0; padding: 0;
            color: #333;
        }
        header {
            background: #0d6efd;
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        header h1 {
            margin: 0 0 .5rem 0;
            font-size: 2rem;
        }
        header p {
            font-size: 1.2rem;
            font-weight: 500;
        }
        main {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .plan {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
            flex: 1 1 280px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }
        .plan:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgb(0 0 0 / 0.15);
        }
        .plan h2 {
            margin-top: 0;
            font-size: 1.8rem;
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: .3rem;
            margin-bottom: 1rem;
        }
        .price {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #212529;
        }
        ul {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }
        ul li {
            padding-left: 1.2rem;
            margin-bottom: .8rem;
            position: relative;
        }
        ul li::before {
            content: "✓";
            color: #198754;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0;
        }
        button {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 0.75rem 1.2rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            align-self: center;
            width: 100%;
        }
        button:hover {
            background-color: #084298;
        }
        form {
            max-width: 400px;
            margin: 3rem auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgb(0 0 0 / 0.1);
        }
        form h3 {
            margin-top: 0;
            text-align: center;
            color: #0d6efd;
        }
        form label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }
        form input, form select {
            width: 100%;
            padding: 0.6rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        form input:focus, form select:focus {
            outline-color: #0d6efd;
            border-color: #0d6efd;
        }
        .message {
            text-align: center;
            font-weight: 600;
            margin-top: 1rem;
            color: green;
        }
        .error {
            color: #dc3545;
        }
        @media (max-width: 650px) {
            main {
                flex-direction: column;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Planes Mensuales para Gestionar tu Negocio</h1>
        <p>El mejor control y administración para pequeños negocios. ¡Haz crecer tu tienda con nosotros!</p>
    </header>
    <main>

    <section class="plan" id="gratuito">
    <h2>Plan Gratuito</h2>
    <div class="price">Gratis <small>/ ideal para probar</small></div>
    <ul>
        <li><strong>Productos en catálogo:</strong> Hasta <strong>10 productos</strong>. Perfecto para empezar y probar.</li>
        <li><strong>Ventas o pedidos guardados:</strong> Hasta <strong>5 ventas activas</strong>. Controla el uso; ideal para pruebas. <em>Exporta a PDF antes de borrar.</em></li>
        <li><strong>Usuarios conectados:</strong> <strong>1 usuario</strong> a la vez. Pensado para el dueño o uso personal.</li>
        <li><strong>Almacenamiento total:</strong> <strong>100 MB</strong> para imágenes y archivos. Mantén las fotos optimizadas.</li>
        <li><strong>Acceso a reportes:</strong> Reportes muy básicos (sin exportación). Útil para ver resultados simples.</li>
        <li><strong>Soporte:</strong> Solo documentación y foro (autoayuda).</li>
        <li><strong>Ventas almacenadas por tiempo:</strong> Máximo <strong>12 horas</strong>; luego se eliminan automáticamente. <em>Recuerda exportar o anotar tu información.</em></li>
    </ul>
    <p style="font-size:.95rem; color:#555; margin-top: .5rem;">
        Ideal para probar la plataforma. Exporta tus ventas regularmente o sube a un plan pago para historial ilimitado y funciones avanzadas.
    </p>
    <button onclick="seleccionarPlan('Gratuito')">Comenzar gratis</button>
</section>


        <section class="plan" id="basico">
    <h2>Plan Básico</h2>
    <div class="price">$19.99 <small>/ mes</small></div>
    <ul>
        <li><strong>Agregar productos ilimitados:</strong> Crea y administra tu catálogo con nombre, descripción, precio y stock.</li>
        <li><strong>Gestión simple de pedidos y carrito:</strong> Registra pedidos fácilmente y administra un carrito para ventas rápidas.</li>
        <li><strong>Registro de ventas diarias:</strong> Controla tus ventas diarias para entender el rendimiento de tu negocio.</li>
        <li><strong>Reportes básicos:</strong> Resúmenes claros de ventas y productos más vendidos para tomar mejores decisiones.</li>
        <li><strong>Interfaz intuitiva:</strong> Fácil de usar, sin complicaciones técnicas.</li>
        <li>Soporte vía correo electrónico</li>
    </ul>
    <button onclick="seleccionarPlan('Básico')">Quiero este plan</button>
</section>
        <section class="plan" id="estandar">
            <h2>Plan Estándar</h2>
            <div class="price">$29.99 <small>/ mes</small></div>
            <ul>
                <li>Todo lo del Plan Básico</li>
                <li>Gestión de proveedores y compras</li>
                <li>Control de gastos</li>
                <li>Reportes detallados y gráficos</li>
                <li>Soporte vía chat</li>
            </ul>
            <button onclick="seleccionarPlan('Estándar')">Quiero este plan</button>
        </section>
        <section class="plan" id="premium">
            <h2>Plan Premium</h2>
            <div class="price">$99.99 <small>/ mes</small></div>
            <ul>
                <li>Todo lo del Plan Estándar</li>
                <li>Integración con sistemas contables</li>
                <li>Acceso multiusuario y permisos</li>
                <li>Análisis avanzado y pronósticos</li>
                <li>Soporte 24/7 prioritario</li>
                <li>Capacitación personalizada</li>
            </ul>
            <button onclick="seleccionarPlan('Premium')">Quiero este plan</button>
        </section>
    </main>

    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="form-contacto">
        <h3>Solicita más información o compra tu plan</h3>
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required />
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" placeholder="tu@email.com" required />
        <label for="plan">Selecciona un plan</label>
        <select name="plan" id="plan" required>
            <option value="" disabled selected>-- Elige un plan --</option>
            <option value="Básico">Plan Básico - $19.99</option>
            <option value="Estándar">Plan Estándar - $29.99</option>
            <option value="Premium">Plan Premium - $99.99</option>
        </select>
        <button type="submit">Enviar</button>
        <?php if (!empty($mensaje)) : ?>
            <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php elseif (!empty($error)) : ?>
            <p class="message error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </form>

    <script>
        function seleccionarPlan(plan) {
            const select = document.getElementById('plan');
            select.value = plan;
            window.scrollTo({
                top: document.getElementById('form-contacto').offsetTop - 20,
                behavior: 'smooth'
            });
            document.getElementById('nombre').focus();
        }
    </script>
</body>
</html>
