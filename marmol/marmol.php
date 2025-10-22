<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mesones & Muebles de Cocina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    header { background: #222; color: #fff; padding: 20px 0; text-align: center; }
    nav ul { display: flex; justify-content: center; list-style: none; background: #444; }
    nav ul li { margin: 0 15px; }
    nav ul li a { color: white; text-decoration: none; padding: 14px; display: block; }
    nav ul li a:hover { background: #555; }

    section { padding: 40px 20px; max-width: 1100px; margin: auto; }
    h2 { color: #555; margin-bottom: 20px; }
    .hero { background: url('imagenes_marmol/WhatsApp Image 2025-06-22 at 8.00.03 PM.jpeg') no-repeat center center/cover; height: 400px; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; text-shadow: 2px 2px 4px #000; }
    .hero h1 { font-size: 3em; margin-bottom: 10px; }
    .hero p { font-size: 1.2em; }

    .gallery img { width: 100%; max-width: 500px; margin: 10px 0; border-radius: 8px; }

    .contact-form input, .contact-form textarea {
      width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;
    }
    .contact-form button {
      background: #333; color: white; padding: 10px 20px; border: none; border-radius: 5px;
      cursor: pointer;
    }
    .contact-form button:hover { background: #555; }

    footer { background: #222; color: #fff; text-align: center; padding: 20px; margin-top: 40px; }


    .hero {
  background: url('imagenes_marmol/WhatsApp Image 2025-06-22 at 8.00.03 PM.jpeg'') no-repeat center center/cover;
  height: 400px;
  position: relative;
  color: white;
  text-align: center;
}

.hero::before {
  content: "";
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(0, 0, 0, 0.4); /* oscurece un poco el fondo */
  z-index: 1;
}

.hero h1,
.hero p {
  position: relative;
  z-index: 2;
}

  </style>
</head>
<body>

<header>
  <h1>Mesones & Cocinas de Lujo</h1>
  <p>Transformamos espacios con elegancia, durabilidad y diseño personalizado</p>
</header>

<nav>
  <ul>
    <li><a href="#inicio">Inicio</a></li>
    <li><a href="#nosotros">Sobre Nosotros</a></li>
    <li><a href="#servicios">Servicios</a></li>
    <li><a href="#contacto">Contacto</a></li>
  </ul>
</nav>

<div class="hero" id="inicio">
  <h1>Diseña tu cocina soñada</h1>
  <p>Mesones de mármol, granito y muebles a medida</p>
</div>

<section id="nosotros">
  <h2>¿Quiénes somos?</h2>
  <p>
    Somos una empresa con más de 10 años de experiencia especializada en la fabricación e instalación de mesones de mármol y granito, así como en el diseño de muebles de cocina a medida. Nuestro compromiso es brindar calidad, elegancia y funcionalidad a cada uno de nuestros clientes, convirtiendo sus cocinas y baños en espacios únicos.
  </p>
  <div class="gallery">
    <!-- Espacio para tus imágenes locales -->
    <img src="imagenes_marmol/WhatsApp Image 2025-06-22 at 7.57.03 PM.jpeg" alt="Mesón de mármol elegante">
    <img src="imagenes_marmol/WhatsApp Image 2025-06-22 at 7.59.31 PM.jpeg" alt="Mueble de cocina moderno">
  </div>
</section>

<section id="servicios">
  <h2>Lo que ofrecemos</h2>
  <ul>
    <li>✔️ Mesones de mármol y granito para cocina y baño</li>
    <li>✔️ Muebles de cocina personalizados con diseño moderno</li>
    <li>✔️ Instalación profesional con acabados premium</li>
    <li>✔️ Asesoría personalizada para tu proyecto</li>
  </ul>
  <p style="margin-top: 20px;">
    No importa si estás renovando tu cocina o construyendo desde cero, estamos aquí para ayudarte a lograr el espacio ideal. ¡Pide tu cotización ahora y empieza a construir la cocina de tus sueños!
  </p>
</section>

<section id="contacto">
  <h2>Contáctanos</h2>
  <p>¿Tienes preguntas o quieres una cotización? Escríbenos y te responderemos lo antes posible.</p>
  <form class="contact-form" action="enviar_formulario.php" method="POST">
    <input type="text" name="nombre" placeholder="Tu nombre" required>
    <input type="email" name="correo" placeholder="Tu correo electrónico" required>
    <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
    <button type="submit">Enviar Mensaje</button>
  </form>
</section>

<footer>
  <p>&copy; <?php echo date("Y"); ?> Mesones & Muebles de Cocina. Todos los derechos reservados.</p>
</footer>

</body>
</html>
