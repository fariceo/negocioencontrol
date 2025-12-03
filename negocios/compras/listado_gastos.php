<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/negocioencontrol/core/conexion.php";

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$res = $conexion->query("SELECT id, concepto, monto, fecha FROM gastos ORDER BY fecha DESC");
?>
<div>
    <h3>📋 Listado de Gastos</h3>
    <table border="1" width="100%" cellpadding="6" cellspacing="0">
        <tr style="background:#f4f4f4;">
            <th>ID</th>
            <th>Concepto</th>
            <th>Monto</th>
            <th>Fecha</th>
        </tr>
        <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['concepto'] ?></td>
            <td>$<?= number_format($row['monto'],2) ?></td>
            <td><?= $row['fecha'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
