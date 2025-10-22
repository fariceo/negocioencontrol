<?php
if(isset($_POST['estado'])){
    $estado = strtoupper($_POST['estado']);
    $archivo = "bot_status.txt";
    file_put_contents($archivo, $estado);
}
header("Location: panel.php");
exit;
