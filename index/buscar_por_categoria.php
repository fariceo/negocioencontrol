<?php
include("../conexion.php");
$c = $conexion->real_escape_string($_POST['categoria'] ?? '');
$sql = $c
  ? "SELECT producto, precio, detalles, tiempo_aprox, img FROM menu WHERE categoria='$c'"
  : "SELECT producto, precio, detalles, tiempo_aprox, img FROM menu LIMIT 10";

$res = $conexion->query($sql);

echo "<div style='display:flex; flex-wrap:wrap; justify-content:center; gap:15px;'>";

while ($f = $res->fetch_assoc()) {

    $p = htmlspecialchars($f['producto']);
    $det = htmlspecialchars($f['detalles']);
    $pr = floatval($f['precio']);
    $tiempo = htmlspecialchars($f['tiempo_aprox']);
    $img = htmlspecialchars($f['img']);

    $ruta = "../imagenes/" . $img;

    $imgTag = file_exists($ruta)
        ? "<img src='$ruta' class='productoImagen' 
              data-producto='$p'
              data-detalles='$det'
              data-tiempo='$tiempo'
              data-img='$img'
              style='width:150px; height:120px; object-fit:cover; border-radius:10px; cursor:pointer;'>"
        : "<div class='productoImagen' 
              data-producto='$p'
              data-detalles='$det'
              data-tiempo='$tiempo'
              data-img=''
              style='width:150px; height:120px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:10px; cursor:pointer;'>Sin imagen</div>";

    echo "
<div class='itemProducto' 
style='
    text-align:center; 
    border:2px solid #ffc107; 
    border-radius:10px; 
    padding:10px; 
    width:170px; 
    background:white; 
    transition: transform 0.3s, box-shadow 0.3s;
    cursor:pointer;
    box-shadow:2px 2px 6px rgba(0,0,0,0.1);
' 
data-producto='$p'
data-detalles='$det'
data-tiempo='$tiempo'
data-precio='$pr'
data-img='$img'
>

    <h4 style='margin:5px 0; color:#ff9800;'>$p</h4>
    $imgTag
    <p style='margin:8px 0; font-weight:bold; color:#4caf50;'>$$pr</p>

    <button class='agregarBtn' 
        data-producto='$p' 
        data-precio='$pr'
        style='
            color:#ffc107; 
            background:white; 
            border:2px solid #ffc107; 
            padding:5px 10px; 
            border-radius:5px; 
            cursor:pointer;
            font-weight:bold;
            transition: all 0.3s;
        '
    >
        ➕ 🛒 Añadir
    </button>
</div>
";
}

echo "</div>";
?>
