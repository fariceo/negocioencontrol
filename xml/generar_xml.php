<?php
session_start();
date_default_timezone_set('America/Guayaquil');
header('Content-Type: application/json; charset=utf-8');

include $_SERVER['DOCUMENT_ROOT'] . '/negocioencontrol/core/conexion.php';

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión expirada'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$id_factura = isset($_POST['id_factura']) ? (int)$_POST['id_factura'] : 0;

if ($id_factura <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de factura inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================
   CONFIGURACIÓN NEGOCIO / EMISOR
========================================================= */
$configSri = $conexion->query("SELECT * FROM configuracion_sri ORDER BY id_config DESC LIMIT 1");

if (!$configSri || $configSri->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No existe configuración SRI del negocio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cfg = $configSri->fetch_assoc();

$ruc_emisor           = preg_replace('/\D/', '', trim($cfg['ruc_emisor'] ?? ''));
$razon_social         = trim($cfg['razon_social'] ?? '');
$nombre_comercial     = trim($cfg['nombre_comercial'] ?? '');
$dir_matriz           = trim($cfg['dir_matriz'] ?? '');
$dir_establecimiento  = trim($cfg['dir_establecimiento'] ?? '');
$obligadoContabilidad = strtoupper(trim($cfg['obligado_contabilidad'] ?? 'NO'));
$ambiente_config      = trim($cfg['ambiente'] ?? '1');
$estab                = str_pad(trim($cfg['estab'] ?? '001'), 3, '0', STR_PAD_LEFT);
$ptoEmi               = str_pad(trim($cfg['pto_emi'] ?? '001'), 3, '0', STR_PAD_LEFT);
$tipoEmision          = trim($cfg['tipo_emision'] ?? '1');
$ivaPorcentaje        = round((float)($cfg['iva_porcentaje'] ?? 15.00), 2);

/* =========================================================
   VALIDACIONES BÁSICAS CONFIG SRI
========================================================= */
if ($ruc_emisor === '' || strlen($ruc_emisor) !== 13) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El RUC del emisor no es válido. Debe tener 13 dígitos.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($razon_social === '' || $dir_matriz === '' || $dir_establecimiento === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan datos obligatorios en la configuración SRI (razón social o direcciones).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($obligadoContabilidad !== 'SI' && $obligadoContabilidad !== 'NO') {
    $obligadoContabilidad = 'NO';
}

/* =========================================================
   CONFIG SRI
========================================================= */
$codDoc = "01"; // Factura

/* =========================================================
   CONFIG IVA
   15% => código 4
   12% => código 2
   0%  => código 0
========================================================= */
$tarifaIva = $ivaPorcentaje;

if ($tarifaIva == 15.00) {
    $codigoPorcentajeIva = "4";
} elseif ($tarifaIva == 12.00) {
    $codigoPorcentajeIva = "2";
} else {
    $codigoPorcentajeIva = "0";
}

/* =========================================================
   FUNCIONES AUXILIARES
========================================================= */
function limpiarXml($texto) {
    return htmlspecialchars(trim((string)$texto), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function generarCodigoNumerico($longitud = 8) {
    return str_pad((string)random_int(1, 99999999), $longitud, "0", STR_PAD_LEFT);
}

function modulo11($clave) {
    $baseMultiplicador = 2;
    $maxMultiplicador = 7;
    $total = 0;
    $multiplicador = $baseMultiplicador;

    for ($i = strlen($clave) - 1; $i >= 0; $i--) {
        $total += intval($clave[$i]) * $multiplicador;
        $multiplicador++;
        if ($multiplicador > $maxMultiplicador) {
            $multiplicador = $baseMultiplicador;
        }
    }

    $modulo = 11 - ($total % 11);
    if ($modulo == 11) return 0;
    if ($modulo == 10) return 1;
    return $modulo;
}

function generarClaveAcceso($fecha, $codDoc, $ruc, $ambiente, $estab, $ptoEmi, $secuencial, $codigoNumerico, $tipoEmision) {
    $fechaFormateada = date('dmY', strtotime($fecha));
    $base = $fechaFormateada . $codDoc . $ruc . $ambiente . $estab . $ptoEmi . $secuencial . $codigoNumerico . $tipoEmision;
    $digito = modulo11($base);
    return $base . $digito;
}

function tipoIdentificacionSri($tipo, $identificacion) {
    $tipo = strtolower(trim((string)$tipo));
    $identificacion = preg_replace('/\D/', '', (string)$identificacion);

    if ($tipo === 'ruc' || strlen($identificacion) === 13) return '04';
    if ($tipo === 'cedula' || strlen($identificacion) === 10) return '05';
    if ($tipo === 'pasaporte') return '06';
    return '07'; // consumidor final / exterior
}

function formaPagoSri($metodo) {
    $metodo = strtolower(trim((string)$metodo));

    if (strpos($metodo, 'efectivo') !== false) return '01';
    if (strpos($metodo, 'transfer') !== false) return '20';
    if (strpos($metodo, 'tarjeta') !== false) return '19';
    if (strpos($metodo, 'credito') !== false) return '15';
    return '01';
}

/* =========================================================
   CONSULTAR FACTURA + VENTA
========================================================= */
$sql = "
    SELECT 
        f.*,
        v.productos,
        v.fecha_hora
    FROM facturacion_ventas f
    LEFT JOIN ventas v ON f.id_venta = v.id
    WHERE f.id_factura = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Factura no encontrada'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = $res->fetch_assoc();

$estadoActual = trim((string)($row['estado_sri'] ?? 'pendiente'));
if ($estadoActual !== 'pendiente' && $estadoActual !== '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Esta factura ya fue procesada o ya tiene XML generado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================
   DATOS PRINCIPALES
========================================================= */
$ambiente = (!empty($row['ambiente']) && $row['ambiente'] == '2') ? '2' : $ambiente_config;
$fechaEmision = !empty($row['fecha_emision']) ? $row['fecha_emision'] : date('Y-m-d');
$secuencial = str_pad((string)$id_factura, 9, "0", STR_PAD_LEFT);
$codigoNumerico = generarCodigoNumerico();

$claveAcceso = generarClaveAcceso(
    $fechaEmision,
    $codDoc,
    $ruc_emisor,
    $ambiente,
    $estab,
    $ptoEmi,
    $secuencial,
    $codigoNumerico,
    $tipoEmision
);

$clienteNombre = trim((string)($row['cliente_nombre'] ?? 'Consumidor Final'));
if ($clienteNombre === '') {
    $clienteNombre = 'Consumidor Final';
}

$clienteId = preg_replace('/\D/', '', (string)($row['cliente_identificacion'] ?? '9999999999999'));
if ($clienteId === '') {
    $clienteId = '9999999999999';
}

$tipoIdComprador = tipoIdentificacionSri($row['tipo_identificacion'] ?? '', $clienteId);
$metodoPagoSri = formaPagoSri($row['metodo_pago'] ?? 'efectivo');

$subtotal0   = round((float)($row['subtotal_0'] ?? 0), 2);
$subtotalIVA = round((float)($row['subtotal_iva'] ?? 0), 2);
$ivaTotal    = round((float)($row['iva_total'] ?? 0), 2);
$total       = round((float)($row['total'] ?? 0), 2);

$productos = json_decode($row['productos'] ?? '[]', true);
if (!is_array($productos) || empty($productos)) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se encontraron productos para esta factura'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================
   ARMAR DETALLES
========================================================= */
$detallesXml = "";
$totalSinImpuestosCalculado = 0.00;
$contadorCodigo = 1;

foreach ($productos as $item) {
    $nombre = limpiarXml($item['producto'] ?? 'Producto');
    $cantidad = round((float)($item['cantidad'] ?? 1), 2);
    $precioUnitario = round((float)($item['precio'] ?? 0), 2);

    if ($cantidad <= 0) $cantidad = 1;
    if ($precioUnitario < 0) $precioUnitario = 0;

    $precioTotalSinImpuesto = round($cantidad * $precioUnitario, 2);

    // Por ahora asumimos que si existe subtotal IVA, los ítems son gravados
    $usaIVA = ($subtotalIVA > 0);

    if ($usaIVA && $tarifaIva > 0) {
        $codigoPorcentaje = $codigoPorcentajeIva;
        $tarifa = $tarifaIva;
        $baseImponible = $precioTotalSinImpuesto;
        $valorIvaItem = round($baseImponible * ($tarifa / 100), 2);
    } else {
        $codigoPorcentaje = "0";
        $tarifa = 0.00;
        $baseImponible = $precioTotalSinImpuesto;
        $valorIvaItem = 0.00;
    }

    $totalSinImpuestosCalculado += $precioTotalSinImpuesto;

    $codigoItem = str_pad((string)$contadorCodigo, 3, "0", STR_PAD_LEFT);

    $detallesXml .= "
    <detalle>
        <codigoPrincipal>{$codigoItem}</codigoPrincipal>
        <descripcion>{$nombre}</descripcion>
        <cantidad>" . number_format($cantidad, 2, '.', '') . "</cantidad>
        <precioUnitario>" . number_format($precioUnitario, 2, '.', '') . "</precioUnitario>
        <descuento>0.00</descuento>
        <precioTotalSinImpuesto>" . number_format($precioTotalSinImpuesto, 2, '.', '') . "</precioTotalSinImpuesto>
        <impuestos>
            <impuesto>
                <codigo>2</codigo>
                <codigoPorcentaje>{$codigoPorcentaje}</codigoPorcentaje>
                <tarifa>" . number_format($tarifa, 2, '.', '') . "</tarifa>
                <baseImponible>" . number_format($baseImponible, 2, '.', '') . "</baseImponible>
                <valor>" . number_format($valorIvaItem, 2, '.', '') . "</valor>
            </impuesto>
        </impuestos>
    </detalle>";

    $contadorCodigo++;
}

$totalSinImpuestosXml = round($subtotal0 + $subtotalIVA, 2);

/* =========================================================
   TOTAL CON IMPUESTOS
========================================================= */
$totalConImpuestosXml = "";

if ($subtotal0 > 0) {
    $totalConImpuestosXml .= "
    <totalImpuesto>
        <codigo>2</codigo>
        <codigoPorcentaje>0</codigoPorcentaje>
        <baseImponible>" . number_format($subtotal0, 2, '.', '') . "</baseImponible>
        <valor>0.00</valor>
    </totalImpuesto>";
}

if ($subtotalIVA > 0) {
    $totalConImpuestosXml .= "
    <totalImpuesto>
        <codigo>2</codigo>
        <codigoPorcentaje>{$codigoPorcentajeIva}</codigoPorcentaje>
        <baseImponible>" . number_format($subtotalIVA, 2, '.', '') . "</baseImponible>
        <valor>" . number_format($ivaTotal, 2, '.', '') . "</valor>
    </totalImpuesto>";
}

/* =========================================================
   XML FACTURA
========================================================= */
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<factura id="comprobante" version="1.1.0">
    <infoTributaria>
        <ambiente>' . $ambiente . '</ambiente>
        <tipoEmision>' . $tipoEmision . '</tipoEmision>
        <razonSocial>' . limpiarXml($razon_social) . '</razonSocial>
        <nombreComercial>' . limpiarXml($nombre_comercial) . '</nombreComercial>
        <ruc>' . $ruc_emisor . '</ruc>
        <claveAcceso>' . $claveAcceso . '</claveAcceso>
        <codDoc>' . $codDoc . '</codDoc>
        <estab>' . $estab . '</estab>
        <ptoEmi>' . $ptoEmi . '</ptoEmi>
        <secuencial>' . $secuencial . '</secuencial>
        <dirMatriz>' . limpiarXml($dir_matriz) . '</dirMatriz>
    </infoTributaria>

    <infoFactura>
        <fechaEmision>' . date('d/m/Y', strtotime($fechaEmision)) . '</fechaEmision>
        <dirEstablecimiento>' . limpiarXml($dir_establecimiento) . '</dirEstablecimiento>
        <obligadoContabilidad>' . $obligadoContabilidad . '</obligadoContabilidad>
        <tipoIdentificacionComprador>' . $tipoIdComprador . '</tipoIdentificacionComprador>
        <razonSocialComprador>' . limpiarXml($clienteNombre) . '</razonSocialComprador>
        <identificacionComprador>' . $clienteId . '</identificacionComprador>
        <totalSinImpuestos>' . number_format($totalSinImpuestosXml, 2, '.', '') . '</totalSinImpuestos>
        <totalDescuento>0.00</totalDescuento>
        <totalConImpuestos>
            ' . $totalConImpuestosXml . '
        </totalConImpuestos>
        <propina>0.00</propina>
        <importeTotal>' . number_format($total, 2, '.', '') . '</importeTotal>
        <moneda>DOLAR</moneda>
        <pagos>
            <pago>
                <formaPago>' . $metodoPagoSri . '</formaPago>
                <total>' . number_format($total, 2, '.', '') . '</total>
                <plazo>0</plazo>
                <unidadTiempo>dias</unidadTiempo>
            </pago>
        </pagos>
    </infoFactura>

    <detalles>
        ' . $detallesXml . '
    </detalles>
</factura>';

/* =========================================================
   VALIDAR XML
========================================================= */
libxml_use_internal_errors(true);
$dom = new DOMDocument();
if (!$dom->loadXML($xml)) {
    $errores = libxml_get_errors();
    $mensajeError = 'XML inválido';
    if (!empty($errores)) {
        $mensajeError .= ': ' . trim($errores[0]->message);
    }

    echo json_encode([
        'ok' => false,
        'mensaje' => $mensajeError
    ], JSON_UNESCAPED_UNICODE);

    libxml_clear_errors();
    exit;
}
libxml_clear_errors();

/* =========================================================
   GUARDAR XML
========================================================= */
$carpetaXml = __DIR__ . '/generados/';

if (!is_dir($carpetaXml)) {
    if (!mkdir($carpetaXml, 0775, true)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo crear la carpeta de XML'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!is_writable($carpetaXml)) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'La carpeta no tiene permisos de escritura'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombreArchivo = $claveAcceso . '.xml';
$rutaArchivo = $carpetaXml . $nombreArchivo;

if (file_put_contents($rutaArchivo, $xml) === false) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo guardar el archivo XML'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================
   ACTUALIZAR BD
========================================================= */
$estadoNuevo = 'xml_generado';

$update = $conexion->prepare("
    UPDATE facturacion_ventas 
    SET clave_acceso = ?, xml_generado = ?, estado_sri = ?
    WHERE id_factura = ?
");

$update->bind_param("sssi", $claveAcceso, $nombreArchivo, $estadoNuevo, $id_factura);

if (!$update->execute()) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'XML generado, pero no se pudo actualizar la base de datos',
        'archivo_xml' => $nombreArchivo
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'mensaje' => 'XML generado correctamente',
    'id_factura' => $id_factura,
    'clave_acceso' => $claveAcceso,
    'archivo_xml' => $nombreArchivo,
    'ruta_relativa' => '/negocioencontrol/negocios/modulos/xml/generados/' . $nombreArchivo
], JSON_UNESCAPED_UNICODE);

exit;
?>