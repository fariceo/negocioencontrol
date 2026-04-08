<?php

header("Content-Type: application/json; charset=UTF-8");
require_once "/var/www/elpollovolantuso/vendor/autoload.php";

use Google\Auth\Credentials\ServiceAccountCredentials;

function logFCM($mensaje) {
    file_put_contents(
        "/var/www/elpollovolantuso/log_fcm.txt",
        date("Y-m-d H:i:s") . " | " . $mensaje . PHP_EOL,
        FILE_APPEND
    );
}

function obtenerAccessTokenFCM() {
    $rutaJson = "/var/www/elpollovolantuso/negocioencontrol-b8e9f-firebase-adminsdk-fbsvc-494c414a03.json";
    $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

    try {
        if (!file_exists($rutaJson)) {
            logFCM("JSON no existe: " . $rutaJson);
            return null;
        }

        $credentials = new ServiceAccountCredentials($scopes, $rutaJson);
        $token = $credentials->fetchAuthToken();

        if (!empty($token['access_token'])) {
            return $token['access_token'];
        }

        logFCM("No vino access_token: " . json_encode($token));
        return null;

    } catch (Throwable $e) {
        logFCM("ERROR TOKEN: " . $e->getMessage());
        return null;
    }
}

$tokenDispositivo = "c_rJcSzcTwqvJOpUznwPda:APA91bER8-NM5fneIUTO85tdeidNmcnugQEQB-J1laQMWmCnSNgvhA4t0AMDm6S6TTx1FVaLgmBRC9XpxLHfTsbV2uzc-BD5s9EVN3bqC1jFi7n88GsI4Sg"; // pega aquí el token completo
$projectId = "negocioencontrol-b8e9f";

$accessToken = obtenerAccessTokenFCM();

if (empty($accessToken)) {
    echo json_encode([
        "success" => false,
        "msg" => "No se pudo obtener access token"
    ]);
    exit;
}

$url = "https://fcm.googleapis.com/v1/projects/" . $projectId . "/messages:send";

$payload = [
    "message" => [
        "token" => $tokenDispositivo,
        "notification" => [
            "title" => "Prueba directa",
            "body"  => "Mensaje de prueba desde el servidor"
        ],
        "data" => [
            "title" => "Prueba directa",
            "body"  => "Mensaje de prueba desde el servidor",
            "tipo"  => "prueba"
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

$respuesta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

logFCM("PRUEBA DIRECTA | HTTP: $httpCode | CURL: $curlError | RESPUESTA: $respuesta");

echo json_encode([
    "success" => $httpCode >= 200 && $httpCode < 300,
    "http_code" => $httpCode,
    "curl_error" => $curlError,
    "respuesta" => json_decode($respuesta, true) ?: $respuesta
]);