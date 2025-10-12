<?php
// send_whatsapp.php
header('Content-Type: application/json');

// LEE BODY
$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['to'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>'Faltan parámetros']);
  exit;
}

// CONFIGURA ESTO
$phone_number_id = '0981770519';   // ej: 115555... (obténlo en Meta dev)
$access_token = 'EAA...';               // token de acceso (no exponer al cliente)
$to = $body['to'];
$template_name = 'my_template_name';    // nombre de tu plantilla aprobada
$language_code = 'es';

// Construye payload para enviar una plantilla (si tu plantilla tiene variables las pasas en components)
$data = [
  "messaging_product" => "whatsapp",
  "to" => $to,
  "type" => "template",
  "template" => [
    "name" => $template_name,
    "language" => ["code" => $language_code],
    // Si tu plantilla necesita variables:
    "components" => [
      [
        "type" => "body",
        "parameters" => array_map(function($v){ return ["type"=>"text","text"=>$v]; }, $body['template_variables'] ?? [])
      ]
    ]
  ]
];

$url = "https://graph.facebook.com/v15.0/{$phone_number_id}/messages";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer {$access_token}",
  "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$err]);
} else {
  http_response_code($httpcode ?: 200);
  echo $result;
}
