<?php
session_start();

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo "<p>No autorizado</p>";
    exit;
}
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="cobros-container">
    <h2>💳 Cobros</h2>

    <!-- FORMULARIO QUE NAVEGA IGUAL QUE <a href> -->
    <form 
        action="/negocioencontrol/negocios/modulos/credito/ficha_credito.php"
        method="GET"
    >
        <input
            type="text"
            name="usuario"
            placeholder="Ingrese usuario"
            required
            autofocus
        >

        <button type="submit">Ver ficha de crédito</button>
    </form>
</div>

<style>
.cobros-container {
    max-width: 360px;
    margin: 40px auto;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}

.cobros-container h2 {
    margin-bottom: 15px;
}

.cobros-container input {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.cobros-container button {
    width: 100%;
    padding: 10px;
    background: #f39c12;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.cobros-container button:hover {
    background: #d68910;
}
</style>

<script>
function initModulo(){
    console.log("Módulo Cobros cargado");
}
</script>