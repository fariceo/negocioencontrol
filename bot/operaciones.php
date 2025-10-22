<?php
$config = parse_ini_file("../config.ini", true);
$host = $config['mysql']['host'];
$user = $config['mysql']['user'];
$pass = $config['mysql']['password'];
$db   = $config['mysql']['database'];

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "Error MySQL: " . $conn->connect_error;
    return;
}

$sql = "SELECT * FROM operaciones ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Fecha</th><th>Activo</th><th>Tipo</th><th>Monto</th><th>Resultado</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['fecha']}</td>";
        echo "<td>{$row['activo']}</td>";
        echo "<td>{$row['tipo']}</td>";
        echo "<td>{$row['monto']}</td>";
        echo "<td>{$row['resultado']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No hay operaciones registradas.";
}

$conn->close();
?>
