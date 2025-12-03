<div>
    <h3>➕ Ingresar Gasto</h3>
    <form method="post" action="compras/guardar_gasto.php">
        <label>Concepto:</label><br>
        <input type="text" name="concepto" required><br><br>
        <label>Monto:</label><br>
        <input type="number" step="0.01" name="monto" required><br><br>
        <button type="submit" style="padding:6px 12px; background:#3498db; color:#fff; border:none; border-radius:4px;">Guardar</button>
    </form>
</div>
