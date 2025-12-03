<?php
$mensaje = "";
include "../core/conexion.php";
$db = new Conexion();
$conexionCentral = $db->central();

// Traer negocios activos
$negocios = [];
$result = $conexionCentral->query("SELECT id_negocio, nombre_negocio, nombre_bd FROM negocios WHERE estado='activo'");
while($row = $result->fetch_assoc()) $negocios[] = $row;

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $id_negocio = $_POST['id_negocio'] ?? '';
    $rol = $_POST['rol'] ?? 'empleado';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Introduce un correo válido.";
    } elseif (!$id_negocio) {
        $mensaje = "Negocio no existe. <a href='../negocios/registro_negocio.php'>Registrar nuevo negocio</a>";
    } elseif ($correo && $contraseña && $id_negocio) {

        // Buscar BD del negocio
        $negocio = array_filter($negocios, fn($n) => $n['id_negocio'] == $id_negocio);
        $negocio = array_values($negocio)[0] ?? null;

        if (!$negocio) {
            $mensaje = "Negocio no encontrado. Registro imposible.";
        } else {
            $hash_pass = password_hash($contraseña, PASSWORD_BCRYPT);

            // 1️⃣ Registrar en usuarios_central
            $resCentral = $conexionCentral->query("SELECT id_usuario FROM usuarios_central WHERE correo='$correo'");
            if ($resCentral->num_rows == 0) {
                $stmtCentral = $conexionCentral->prepare(
                    "INSERT INTO usuarios_central (correo, contraseña, id_negocio, rol, estado, creado_en) VALUES (?, ?, ?, ?, 'activo', NOW())"
                );
                $stmtCentral->bind_param("ssis", $correo, $hash_pass, $id_negocio, $rol);
                $stmtCentral->execute();
            } else {
                $mensaje = "El correo ya está registrado en la central.";
                goto FIN;
            }

            // 2️⃣ Registrar en BD del negocio
            $conexionNegocio = $db->negocio($negocio['nombre_bd']);
            $resCheck = $conexionNegocio->query("SELECT id_usuario FROM usuarios WHERE correo='$correo'");
            if ($resCheck->num_rows == 0) {
                $stmtNegocio = $conexionNegocio->prepare(
                    "INSERT INTO usuarios (nombre, correo, contraseña, rol, creado_en) VALUES (?, ?, ?, ?, NOW())"
                );
                $stmtNegocio->bind_param("ssss", $correo, $correo, $hash_pass, $rol); // nombre temporal = correo
                $stmtNegocio->execute();

                $mensaje = $stmtNegocio->error 
                    ? "Error al registrar usuario en la BD del negocio: ".$stmtNegocio->error 
                    : "Usuario registrado correctamente en central y en la BD del negocio.";
            } else {
                $mensaje = "Usuario ya existe en la BD del negocio, pero se registró en central.";
            }
        }
    } else {
        $mensaje = "Completa todos los campos.";
    }
}

FIN:
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta charset="UTF-8" name="viewport" content="width=device-width">
<title>Registro de Usuario</title>
<style>
body { font-family: Arial; margin: 50px; }
form { max-width: 450px; margin: auto; }
input, button, select { width: 100%; padding: 10px; margin: 5px 0; }
.mensaje { text-align: center; color: green; margin-bottom: 20px; }
.mensaje a { color: blue; text-decoration: underline; }
ul#lista_negocios { list-style: none; padding: 0; border: 1px solid #ccc; max-height: 100px; overflow-y: auto; }
ul#lista_negocios li { padding: 5px; cursor: pointer; }
ul#lista_negocios li:hover { background-color: #eee; }
</style>
</head>
<body>
<button onclick="window.history.back()" class="btn-atras">← Atrás</button>

<style>
.btn-atras {
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.3s;
}
.btn-atras:hover {
  background-color: #d32f2f;
}
</style>

<div class="mensaje"><?php echo $mensaje; ?></div>

<form id="registroForm" method="POST">
<h2>Registrar Usuario</h2>
<input type="email" name="correo" placeholder="Correo del usuario" required>
<input type="password" name="contraseña" placeholder="Contraseña" required>

<label>Selecciona el negocio</label>
<input type="text" id="negocioInput" placeholder="Escribe el negocio..." autocomplete="off" required>
<input type="hidden" name="id_negocio" id="id_negocio">
<ul id="lista_negocios"></ul>

<label>Rol</label>
<select name="rol">
    <option value="empleado">Empleado</option>
    <option value="cajero">Cajero</option>
    <option value="admin">Admin</option>
</select>

<div class="form-buttons">
  <button type="submit" class="btn-primary">Registrar Usuario</button>
  
  <a href="registro_negocio.php" class="btn-secondary">+ Nuevo Negocio</a>
  <a href="login.php" class="btn-outline">Iniciar Sesión</a>
</div>

<style>
.form-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  margin-top: 20px;
}

/* Botón principal */
.btn-primary {
  background-color: #4CAF50;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 12px 24px;
  font-size: 16px;
  cursor: pointer;
  width: 80%;
  max-width: 300px;
  transition: all 0.3s ease;
}
.btn-primary:hover {
  background-color: #43a047;
  transform: scale(1.03);
}

/* Botón secundario */
.btn-secondary {
  background-color: #2196F3;
  color: #fff;
  text-decoration: none;
  border-radius: 10px;
  padding: 10px 20px;
  font-size: 15px;
  display: inline-block;
  width: 80%;
  max-width: 300px;
  text-align: center;
  transition: all 0.3s ease;
}
.btn-secondary:hover {
  background-color: #1976D2;
  transform: scale(1.03);
}

/* Botón con borde (para login) */
.btn-outline {
  color: #333;
  border: 2px solid #555;
  background: transparent;
  text-decoration: none;
  border-radius: 10px;
  padding: 10px 20px;
  font-size: 15px;
  display: inline-block;
  width: 80%;
  max-width: 300px;
  text-align: center;
  transition: all 0.3s ease;
}
.btn-outline:hover {
  background-color: #555;
  color: white;
  transform: scale(1.03);
}
</style>

</form>

<script>
const negocios = <?php echo json_encode($negocios); ?>;
const input = document.getElementById('negocioInput');
const hiddenInput = document.getElementById('id_negocio');
const lista = document.getElementById('lista_negocios');

input.addEventListener('input', function() {
    const val = this.value.toLowerCase();
    lista.innerHTML = '';
    const matches = negocios.filter(n => n.nombre_negocio.toLowerCase().includes(val));
    if (matches.length > 0) {
        matches.forEach(n => {
            const li = document.createElement('li');
            li.textContent = n.nombre_negocio;
            li.dataset.id = n.id_negocio;
            li.addEventListener('click', () => {
                input.value = n.nombre_negocio;
                hiddenInput.value = n.id_negocio;
                lista.innerHTML = '';
            });
            lista.appendChild(li);
        });
    } else {
        hiddenInput.value = '';
        const li = document.createElement('li');
        li.textContent = 'Negocio no existente';
        li.style.color = 'red';
        lista.appendChild(li);
    }
});

document.getElementById('registroForm').addEventListener('submit', function(e){
    if (!hiddenInput.value) {
        alert("Selecciona un negocio válido o crea uno nuevo.");
        e.preventDefault();
    }
});
</script>

</body>
</html>
