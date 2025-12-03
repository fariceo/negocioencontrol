<?php
session_start();
// Destruir todas las variables de sesión
$_SESSION = [];
session_destroy();
// Redirigir al login u otra página
header("Location: /negocioencontrol/usuarios/login.php");
exit;
?>
