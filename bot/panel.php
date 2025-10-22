<?php
$mode_file = "bot_mode.txt";
$status_file = "bot_status.txt";
$csv_file = "operaciones.csv";

// Inicializa archivos si no existen
if (!file_exists($mode_file)) file_put_contents($mode_file, "DEMO");
if (!file_exists($status_file)) file_put_contents($status_file, "ON");

// Cambiar estado del bot
if (isset($_POST['status'])) {
    file_put_contents($status_file, $_POST['status']);
}

// Cambiar modo del bot
if (isset($_POST['mode'])) {
    file_put_contents($mode_file, $_POST['mode']);
}

// Leer últimos registros
$operaciones = [];
if (file_exists($csv_file)) {
    $fp = fopen($csv_file, 'r');
    $header = fgetcsv($fp);
    while (($row = fgetcsv($fp)) !== false) {
        $operaciones[] = $row;
    }
    fclose($fp);
}

// Leer estado y modo actual
$status = file_get_contents($status_file);
$mode = file_get_contents($mode_file);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Bot IQ Option DEMO</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
button { margin-right: 5px; padding: 5px 10px; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
th { background-color: #eee; }
</style>
</head>
<body>
<h1>Panel Bot IQ Option DEMO</h1>
<p>Estado del Bot: <strong><?php echo $status; ?></strong></p>
<p>Modo del Bot: <strong><?php echo $mode; ?></strong></p>

<form method="post">
    <button name="status" value="ON">ON</button>
    <button name="status" value="PAUSE">PAUSE</button>
    <button name="status" value="OFF">OFF</button>
    <br><br>
    <button name="mode" value="DEMO">DEMO</button>
    <button name="mode" value="REAL">REAL</button>
</form>

<h2>Últimas Operaciones</h2>
<table>
<tr>
<?php foreach($header as $col) echo "<th>$col</th>"; ?>
</tr>
<?php
$ultimas = array_slice($operaciones, -10); // últimas 10 operaciones
foreach($ultimas as $op) {
    echo "<tr>";
    foreach($op as $val) echo "<td>$val</td>";
    echo "</tr>";
}
?>
</table>
</body>
</html>
