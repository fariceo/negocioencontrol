<!-- index.html -->
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Enviar WhatsApp</title></head>
<body>
  <button id="sendBtn">Enviar WhatsApp sin abrir app</button>

  <script>
    document.getElementById('sendBtn').addEventListener('click', async () => {
      try {
        const resp = await fetch('/send_whatsapp.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            to: '5939XXXXXXXX', // número destino en formato internacional sin +
            // puedes enviar datos de plantilla si tu plantilla tiene variables
            template_variables: ['Pedido #123', 'En camino']
          })
        });
        const json = await resp.json();
        alert(JSON.stringify(json));
      } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
      }
    });
  </script>
</body>
</html>
