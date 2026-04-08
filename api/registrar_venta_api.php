<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../core/conexion.php";
require_once "/var/www/elpollovolantuso/vendor/autoload.php";

use Google\Auth\Credentials\ServiceAccountCredentials;

/* =========================
   LOG
========================= */
function logFCM($mensaje) {
    file_put_contents(
        "/var/www/elpollovolantuso/log_fcm.txt",
        date("Y-m-d H:i:s") . " | " . $mensaje . PHP_EOL,
        FILE_APPEND
    );
}

/* =========================
   ACCESS TOKEN FCM HTTP v1
========================= */
function obtenerAccessTokenFCM() {
    $rutaJson = "/var/www/elpollovolantuso/negocioencontrol-b8e9f-firebase-adminsdk-fbsvc-494c414a03.json";
    $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

    try {
        if (!file_exists($rutaJson)) {
            logFCM("ERROR JSON no existe: " . $rutaJson);
            return null;
        }

        if (!is_readable($rutaJson)) {
            logFCM("ERROR JSON no legible: " . $rutaJson);
            return null;
        }

        $credentials = new ServiceAccountCredentials($scopes, $rutaJson);
        $token = $credentials->fetchAuthToken();

        if (!empty($token['access_token'])) {
            return $token['access_token'];
        }

        logFCM("ERROR access token vacío: " . json_encode($token));
        return null;

    } catch (Throwable $e) {
        logFCM("ERROR obtenerAccessTokenFCM: " . $e->getMessage());
        return null;
    }
}

/* =========================
   ENVIAR PUSH A UN TOKEN
========================= */
function enviarPushFCM($tokenDispositivo, $titulo, $mensaje, $tipo, $negocio, $accessToken) {
    $projectId = "negocioencontrol-b8e9f";
    $url = "https://fcm.googleapis.com/v1/projects/" . $projectId . "/messages:send";

    $payload = [
        "message" => [
            "token" => $tokenDispositivo,
            "notification" => [
                "title" => $titulo,
                "body"  => $mensaje
            ],
            "data" => [
                "title"   => $titulo,
                "body"    => $mensaje,
                "tipo"    => $tipo,
                "negocio" => $negocio
            ],
            "android" => [
                "priority" => "high"
            ]
        ]
    ];

    $headers = [
        "Authorization: Bearer " . $accessToken,
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    logFCM("TOKEN: $tokenDispositivo | HTTP: $httpCode | CURL: $curlError | RESPUESTA: $respuesta");

    return ($httpCode >= 200 && $httpCode < 300);
}

/* =========================
   DATOS POST
========================= */
$negocio     = $_POST['negocio'] ?? '';
$vendedor    = $_POST['vendedor'] ?? '';
$cliente     = $_POST['cliente'] ?? '';
$productos   = $_POST['productos'] ?? '';
$total       = floatval($_POST['total'] ?? 0);
$metodo_pago = $_POST['metodo_pago'] ?? '';

$correo    = $_POST['correo'] ?? '';
$telefono  = $_POST['telefono'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$ruc       = $_POST['ruc'] ?? '';
$timezone  = $_POST['timezone'] ?? 'UTC';

if (in_array($timezone, timezone_identifiers_list())) {
    date_default_timezone_set($timezone);
} else {
    date_default_timezone_set('UTC');
    logFCM("WARNING timezone inválida recibida: " . $timezone);
}

$fechaHora = date("Y-m-d H:i:s");

if ($negocio == '' || $productos == '' || $total == 0) {
    echo json_encode([
        "success" => false,
        "msg" => "Datos incompletos"
    ]);
    exit;
}

$conexionObj = new Conexion();

/* =========================
   CONEXION BD NEGOCIO
========================= */
$conn = $conexionObj->negocio($negocio);

if (!$conn) {
    echo json_encode([
        "success" => false,
        "msg" => "Error conexión BD"
    ]);
    exit;
}

/* =========================
   REGISTRAR VENTA
========================= */
$sql = "INSERT INTO ventas
(
    negocio,
    vendedor,
    cliente,
    correo,
    telefono,
    direccion,
    ruc,
    productos,
    total,
    metodo_pago,
    fecha_hora
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?
)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "msg" => "Error prepare venta",
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "ssssssssdss",
    $negocio,
    $vendedor,
    $cliente,
    $correo,
    $telefono,
    $direccion,
    $ruc,
    $productos,
    $total,
    $metodo_pago,
    $fechaHora
);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "msg" => "Error al registrar venta",
        "error" => $stmt->error
    ]);
    exit;
}

$stmt->close();
$conn->close();

logFCM("VENTA REGISTRADA | NEGOCIO: $negocio | VENDEDOR: $vendedor | TOTAL: $total");

/* =========================
   CONEXION BD CENTRAL
========================= */
$connCentral = $conexionObj->central();

if (!$connCentral) {
    echo json_encode([
        "success" => true,
        "msg" => "Venta registrada correctamente, pero no se pudo conectar a BD central"
    ]);
    exit;
}

/* =========================
   GUARDAR NOTIFICACION
========================= */
$titulo  = "Nueva venta registrada";
$mensaje = "Venta por $" . number_format($total, 2) . " realizada por " . $vendedor;
$tipo    = "venta";

$sqlNoti = "INSERT INTO notificaciones
(
    negocio,
    usuario,
    titulo,
    mensaje,
    tipo,
    leido
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    0
)";

$stmtNoti = $connCentral->prepare($sqlNoti);

if ($stmtNoti) {
    $stmtNoti->bind_param(
        "sssss",
        $negocio,
        $vendedor,
        $titulo,
        $mensaje,
        $tipo
    );

    if (!$stmtNoti->execute()) {
        logFCM("ERROR guardar notificación BD: " . $stmtNoti->error);
    }

    $stmtNoti->close();
} else {
    logFCM("ERROR prepare notificaciones: " . $connCentral->error);
}

/* =========================
   OBTENER TOKENS
========================= */
$tokens = [];
$correosAdmin = [];

/* volver a abrir conexión a la BD del negocio para consultar usuarios admin */
$connNegocioUsuarios = $conexionObj->negocio($negocio);

if ($connNegocioUsuarios) {
    $sqlAdmins = "SELECT correo FROM usuarios WHERE rol = 'admin'";
    $stmtAdmins = $connNegocioUsuarios->prepare($sqlAdmins);

    if ($stmtAdmins) {
        $stmtAdmins->execute();
        $resultAdmins = $stmtAdmins->get_result();

        while ($rowAdmin = $resultAdmins->fetch_assoc()) {
            if (!empty($rowAdmin['correo'])) {
                $correosAdmin[] = trim($rowAdmin['correo']);
            }
        }

        $stmtAdmins->close();
    } else {
        logFCM("ERROR prepare usuarios admin: " . $connNegocioUsuarios->error);
    }

    $connNegocioUsuarios->close();
} else {
    logFCM("ERROR reconexión BD negocio para consultar admins");
}

if (count($correosAdmin) > 0) {
    $sqlToken = "SELECT token FROM tokens_fcm WHERE negocio=? AND usuario=?";
    $stmtToken = $connCentral->prepare($sqlToken);

    if ($stmtToken) {
        foreach ($correosAdmin as $correoAdmin) {
            $stmtToken->bind_param("ss", $negocio, $correoAdmin);
            $stmtToken->execute();
            $resultToken = $stmtToken->get_result();

            while ($rowToken = $resultToken->fetch_assoc()) {
                if (!empty($rowToken['token'])) {
                    $tokens[] = $rowToken['token'];
                }
            }
        }

        $stmtToken->close();
    } else {
        logFCM("ERROR prepare tokens_fcm por admin: " . $connCentral->error);
    }
}

$tokens = array_values(array_unique(array_filter($tokens)));

logFCM("NEGOCIO RECIBIDO: $negocio | ADMINS: " . count($correosAdmin) . " | TOKENS ENCONTRADOS: " . count($tokens));

/* =========================
   ENVIAR PUSH FIREBASE HTTP v1
========================= */
$enviadas = 0;
$fallidas = 0;

if (count($tokens) > 0) {
    $accessToken = obtenerAccessTokenFCM();

    if (!empty($accessToken)) {
        foreach ($tokens as $token) {
            $ok = enviarPushFCM(
                $token,
                $titulo,
                $mensaje,
                $tipo,
                $negocio,
                $accessToken
            );

            if ($ok) {
                $enviadas++;
            } else {
                $fallidas++;
            }
        }
    } else {
        logFCM("ERROR no se pudo obtener access token para enviar push");
    }
} else {
    logFCM("AVISO sin tokens para negocio: $negocio");
}

/* =========================
   RESPUESTA FINAL
========================= */
echo json_encode([
    "success" => true,
    "msg" => "Venta registrada correctamente",
    "tokens_encontrados" => count($tokens),
    "notificaciones_enviadas" => $enviadas,
    "notificaciones_fallidas" => $fallidas
]);

$connCentral->close();