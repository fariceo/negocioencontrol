<?php
/*
  archivo: sistema_opciones_binarias_compuesto_info_v2.php
  Sistema en un solo archivo con:
  - Interés compuesto progresivo (%10 inicial, aumenta según capital)
  - Estrategia de recuperación hasta 2 pérdidas consecutivas
  - Mostrar beneficios guardados vs reinversión completa
  - Proteger un % configurable de los beneficios
  - Frontend + backend (HTML/CSS/JS + PHP + SQL)
*/

// ---------------- CONFIG ----------------
$DB_HOST = 'localhost';
$DB_NAME = 'trading_opciones';
$DB_USER = 'root';
$DB_PASS = 'clave';
$CAPITAL_INICIAL = 100; // capital inicial para torneo
$PORC_BENEFICIOS_GUARDADOS = 40; // porcentaje de beneficios que se guardan automáticamente
// ----------------------------------------

header('Content-Type: text/html; charset=utf-8');

// Conexión PDO
try {
    $pdoRoot = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión MySQL: " . $e->getMessage());
}

// Crear DB si no existe
$pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

// Conectar a la BD específica
$pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Crear tabla operaciones
$createSql = "CREATE TABLE IF NOT EXISTS competicion_trading (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  hora_entrada TIME NOT NULL,
  hora_expiracion TIME NOT NULL,
  activo VARCHAR(50) NOT NULL,
  direccion ENUM('Call','Put') NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  retorno_porc DECIMAL(5,2) NOT NULL,
  resultado ENUM('Win','Loss') NOT NULL,
  ganancia_perdida DECIMAL(12,2) NOT NULL,
  comentario TEXT,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$pdo->exec($createSql);

// ----------------- API -----------------
$action = $_GET['action'] ?? $_POST['action'] ?? null;
if ($action) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if ($action === 'create') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) { echo json_encode(['error'=>'no data']); exit; }
            $required = ['fecha','hora_entrada','hora_expiracion','activo','direccion','monto','retorno_porc','resultado'];
            foreach ($required as $f) if (!isset($data[$f]) || $data[$f]==='') { echo json_encode(['error'=>'missing '.$f]); exit; }

            $monto = (float)$data['monto'];
            $ret = (float)$data['retorno_porc'];
            $resultado = $data['resultado']==='Win'?'Win':'Loss';
            $ganancia = $resultado==='Win' ? round($monto*($ret/100),2) : -round($monto,2);

            $stmt = $pdo->prepare("INSERT INTO competicion_trading (fecha,hora_entrada,hora_expiracion,activo,direccion,monto,retorno_porc,resultado,ganancia_perdida,comentario) VALUES (:fecha,:he,:hx,:activo,:direccion,:monto,:ret,:resultado,:ganancia,:comentario)");
            $stmt->execute([
                ':fecha'=>$data['fecha'],
                ':he'=>$data['hora_entrada'],
                ':hx'=>$data['hora_expiracion'],
                ':activo'=>substr($data['activo'],0,50),
                ':direccion'=>$data['direccion'],
                ':monto'=>$monto,
                ':ret'=>$ret,
                ':resultado'=>$resultado,
                ':ganancia'=>$ganancia,
                ':comentario'=>$data['comentario']??null
            ]);
            echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId(),'ganancia'=>$ganancia]);
            exit;
        }

        if ($action === 'list') {
            $stmt = $pdo->query('SELECT * FROM competicion_trading ORDER BY creado_en DESC');
            echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll()]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM competicion_trading WHERE id=:id');
            $stmt->execute([':id'=>$id]);
            echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]);
            exit;
        }

        if ($action==='stats') {
            $res = $pdo->query('SELECT SUM(CASE WHEN resultado="Win" THEN 1 ELSE 0 END) AS wins,
                SUM(CASE WHEN resultado="Loss" THEN 1 ELSE 0 END) AS losses,
                SUM(ganancia_perdida) AS net
                FROM competicion_trading')->fetch();
            $wins = (int)$res['wins'];
            $losses = (int)$res['losses'];
            $total = $wins+$losses;
            $winrate = $total?round($wins/$total*100,2):0;
            $capital = $CAPITAL_INICIAL + (float)$res['net'];
            echo json_encode([
                'ok'=>true,
                'wins'=>$wins,
                'losses'=>$losses,
                'total'=>$total,
                'winrate'=>$winrate,
                'net'=>(float)$res['net'],
                'capital'=>$capital,
                'beneficios_guardados'=>round((float)$res['net']*($PORC_BENEFICIOS_GUARDADOS/100),2)
            ]);
            exit;
        }

        echo json_encode(['error'=>'unknown action']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error'=>'server error','msg'=>$e->getMessage()]);
    }
    exit;
}

// ---------------- FRONTEND HTML ----------------
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Opciones Binarias - Beneficios Guardados</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7fb;color:#222}
.container{max-width:980px;margin:20px auto;padding:18px;background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
h1{margin-top:0}
.row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:8px}
label{display:flex;flex-direction:column;font-size:14px}
input,select,textarea{padding:8px;border-radius:4px;border:1px solid #ccc}
.actions{margin-top:10px}
button{padding:8px 12px;border-radius:6px;border:0;background:#2b62ff;color:#fff;cursor:pointer}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ddd;padding:8px;text-align:left}
.small{font-size:13px;color:#666}
.sugerido{background:#e8f0fe;border:1px solid #2b62ff;padding:6px;border-radius:4px;margin-bottom:6px;}
.win { background-color: #d4edda; color: #155724; font-weight: bold; }
.loss { background-color: #f8d7da; color: #721c24; font-weight: bold; }
.info-beneficios{background:#fff3cd;border:1px solid #ffeeba;color:#856404;padding:8px;margin-top:6px;border-radius:4px;}
</style>
</head>
<body>
<main class="container">
<h1>Opciones Binarias - Beneficios Guardados</h1>

<section id="form-section">
<form id="tradeForm">
<div class="sugerido">Monto sugerido inicial: $<span id="montoInicial">0.00</span></div>
<div class="sugerido">Monto a invertir para recuperación: $<span id="montoRecuperar">0.00</span></div>
<div class="sugerido">Simulación inversión:</div>
<div class="info-beneficios">
  Con beneficios guardados: $<span id="conBeneficios">0.00</span><br>
  Sin guardar beneficios: $<span id="sinBeneficios">0.00</span>
</div>

<div class="row">
<label>Fecha <input type="date" name="fecha" required></label>
<label>Hora Entrada <input type="time" name="hora_entrada" required></label>
<label>Hora Expiración <input type="time" name="hora_expiracion" required></label>
</div>
<div class="row">
<label>Activo <input name="activo" required placeholder="EUR/USD"></label>
<label>Dirección<select name="direccion"><option>Call</option><option>Put</option></select></label>
</div>
<div class="row">
<label>Monto ($) <input type="number" step="0.01" name="monto" required id="montoForm"></label>
<label>Retorno (%) <input type="number" step="0.01" name="retorno_porc" value="85" required></label>
<label>Resultado<select name="resultado"><option>Win</option><option>Loss</option></select></label>
</div>
<label>Comentario <textarea name="comentario" rows="2" placeholder="Nota"></textarea></label>
<div class="actions"><button type="submit">Guardar operación</button><button type="button" id="btnStats">Actualizar estadísticas</button></div>
</form>
</section>

<section id="stats">
<h2>Estadísticas</h2>
<div id="statsContent" class="small">Cargando...</div>
</section>

<section id="list">
<h2>Últimas operaciones</h2>
<div id="tableContainer">Cargando...</div>
</section>
</main>

<script>
const apiUrl = location.pathname+'?action=';
let capitalActual = 0;
let perdidasConsecArray = [];
let beneficiosGuardados = 0;

// Función para calcular porcentaje progresivo
function calcularPorcentajeDinamico(capitalActual, capitalInicial){
    let porcentaje = 0.10; // inicio 10%
    const incremento = 0.05; // 5% por cada múltiplo sobre capital inicial
    if(capitalActual > capitalInicial){
        const multiples = Math.floor((capitalActual - capitalInicial) / capitalInicial);
        porcentaje += multiples * incremento;
    }
    if(porcentaje > 0.5) porcentaje = 0.5; // máximo 50%
    return porcentaje;
}

async function fetchJSON(url, opts){const res = await fetch(url, opts); return res.json();}

async function loadStats(){
  const r = await fetchJSON(apiUrl+'stats');
  if(!r.ok){document.getElementById('statsContent').innerText='Error'; return;}
  capitalActual = r.capital;
  beneficiosGuardados = r.beneficios_guardados;

  document.getElementById('statsContent').innerHTML=
    `Total: ${r.total} operaciones<br>`+
    `Wins: ${r.wins} | Losses: ${r.losses} | Winrate: ${r.winrate}%<br>`+
    `Resultado neto: ${r.net}<br>`+
    `Capital actual: ${capitalActual}<br>`+
    `Beneficios guardados (${<?=$PORC_BENEFICIOS_GUARDADOS?>}%): ${beneficiosGuardados}`;

  // porcentaje progresivo
  let porcentaje = calcularPorcentajeDinamico(capitalActual, <?=$CAPITAL_INICIAL?>);
  let montoIni = capitalActual * porcentaje;
  if(montoIni < 1) montoIni = 1;
  document.getElementById('montoInicial').innerText = montoIni.toFixed(2);

  // Simulación inversión
  let montoConBeneficios = montoIni + beneficiosGuardados;
  let montoSinBeneficios = <?=$CAPITAL_INICIAL?> * porcentaje;
  document.getElementById('conBeneficios').innerText = montoConBeneficios.toFixed(2);
  document.getElementById('sinBeneficios').innerText = montoSinBeneficios.toFixed(2);

  // obtener retorno directamente del input
  const retorno = parseFloat(document.querySelector('[name="retorno_porc"]').value) || 80;

  // monto a invertir para recuperación
  let montoRec = 0;
  if(perdidasConsecArray.length > 0){
      const totalPerdidas = perdidasConsecArray.reduce((a,b)=>a+b, 0);
      const beneficioDeseado = 10;
      montoRec = (totalPerdidas + beneficioDeseado) / (retorno/100);
      if(montoRec > capitalActual*0.5) montoRec = capitalActual*0.5;
  }
  document.getElementById('montoRecuperar').innerText = montoRec.toFixed(2);
  document.getElementById('montoForm').value = perdidasConsecArray.length>0 ? montoRec.toFixed(2) : montoIni.toFixed(2);
}

async function loadList(){
  const res = await fetchJSON(apiUrl+'list');
  if(!res.ok){document.getElementById('tableContainer').innerText='Error'; return;}
  let html='<table><thead><tr><th>#</th><th>Fecha</th><th>Activo</th><th>Dirección</th><th>Monto</th><th>Resultado</th><th>Ganancia</th><th>Comentario</th><th>Acción</th></tr></thead><tbody>';
  for(const r of res.data){
    const claseRes = r.resultado==='Win'?'win':'loss';
    html+=`<tr>
      <td>${r.id}</td>
      <td>${r.fecha} ${r.hora_entrada}</td>
      <td>${r.activo}</td>
      <td>${r.direccion}</td>
      <td>${r.monto}</td>
      <td class="${claseRes}">${r.resultado}</td>
      <td>${r.ganancia_perdida}</td>
      <td>${r.comentario||''}</td>
      <td><button data-id='${r.id}' class='del'>Borrar</button></td>
    </tr>`;
  }
  html+='</tbody></table>';
  document.getElementById('tableContainer').innerHTML=html;
  document.querySelectorAll('.del').forEach(b=>b.addEventListener('click',async()=>{
    if(!confirm('Borrar operación?')) return;
    const id=b.dataset.id;
    const r=await fetchJSON(apiUrl+'delete&id='+id);
    if(r.ok){loadList();loadStats();}
  }));
}

document.getElementById('tradeForm').addEventListener('submit',async(e)=>{
  e.preventDefault();
  const f=e.target;
  const data=Object.fromEntries(new FormData(f));
  data.monto=parseFloat(data.monto);
  const r=await fetchJSON(apiUrl+'create',{method:'POST',body:JSON.stringify(data)});
  if(r.ok){
    f.reset();
    if(r.ganancia<0){
        perdidasConsecArray.push(Math.abs(data.monto));
        if(perdidasConsecArray.length>2) perdidasConsecArray = [];
    } else {
        perdidasConsecArray = [];
        // guardar un porcentaje de beneficios
        beneficiosGuardados += r.ganancia*<?=$PORC_BENEFICIOS_GUARDADOS?>/100;
    }
    loadList();
    loadStats();
  }else alert('Error: '+JSON.stringify(r));
});

// recalcular monto al cambiar retorno
document.querySelector('[name="retorno_porc"]').addEventListener('input', loadStats);
document.getElementById('btnStats').addEventListener('click',()=>loadStats());
loadStats();
loadList();
</script>
</body>
</html>
