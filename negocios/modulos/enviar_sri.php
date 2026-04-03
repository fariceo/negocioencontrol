<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include $_SERVER['DOCUMENT_ROOT'].'/negocioencontrol/core/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nombre_bd_negocio'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
    exit;
}

$db = new Conexion();
$conexion = $db->negocio($_SESSION['nombre_bd_negocio']);

$id_factura = intval($_POST['id_factura'] ?? 0);

if ($id_factura <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de factura inválido']);
    exit;
}

try {
    $conexion->begin_transaction();

    // =====================================
    // 1) BUSCAR FACTURA
    // =====================================
    $stmt = $conexion->prepare("
        SELECT *
        FROM facturacion_ventas
        WHERE id_factura = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $resFactura = $stmt->get_result();

    if ($resFactura->num_rows === 0) {
        $stmt->close();
        $conexion->rollback();
        echo json_encode(['ok' => false, 'mensaje' => 'Factura no encontrada']);
        exit;
    }

    $factura = $resFactura->fetch_assoc();
    $stmt->close();

    // =====================================
    // 2) BUSCAR VENTA RELACIONADA
    // =====================================
    $stmt = $conexion->prepare("
        SELECT *
        FROM ventas
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $factura['id_venta']);
    $stmt->execute();
    $resVenta = $stmt->get_result();

    if ($resVenta->num_rows === 0) {
        $stmt->close();
        $conexion->rollback();
        echo json_encode(['ok' => false, 'mensaje' => 'Venta relacionada no encontrada']);
        exit;
    }

    $venta = $resVenta->fetch_assoc();
    $stmt->close();

    $productos = json_decode($venta['productos'] ?? '[]', true);

    if (!is_array($productos) || empty($productos)) {
        $conexion->rollback();
        echo json_encode(['ok' => false, 'mensaje' => 'La venta no tiene productos válidos']);
        exit;
    }

    // =====================================
    // 3) DATOS BÁSICOS SRI
    // =====================================
    $ambiente = '1'; // 1 pruebas, 2 producción
    $tipoEmision = '1';
    $codDoc = '01'; // factura
    $ruc = '9999999999999'; // <-- CAMBIAR POR TU RUC REAL
    $estab = '001';
    $ptoEmi = '001';
    $secuencial = str_pad($id_factura, 9, '0', STR_PAD_LEFT);

    $fechaEmision = date('d/m/Y', strtotime($factura['fecha_emision']));
    $fechaClave = date('dmY', strtotime($factura['fecha_emision']));

    // =====================================
    // 4) GENERAR CÓDIGO NUMÉRICO + CLAVE ACCESO
    // =====================================
    $codigoNumerico = str_pad((string)rand(1, 99999999), 8, '0', STR_PAD_LEFT);

    $claveSinDigito = $fechaClave
        . $codDoc
        . $ruc
        . $ambiente
        . $estab
        . $ptoEmi
        . $secuencial
        . $codigoNumerico
        . $tipoEmision;

    function generarDigitoVerificador($cadena) {
        $baseMultiplicador = 2;
        $multiplicador = 2;
        $total = 0;

        for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
            $total += intval($cadena[$i]) * $multiplicador;
            $multiplicador++;
            if ($multiplicador > 7) $multiplicador = 2;
        }

        $modulo = 11;
        $residuo = $total % $modulo;
        $digito = $modulo - $residuo;

        if ($digito == 11) $digito = 0;
        if ($digito == 10) $digito = 1;

        return $digito;
    }

    $digitoVerificador = generarDigitoVerificador($claveSinDigito);
    $claveAcceso = $claveSinDigito . $digitoVerificador;

    // =====================================
    // 5) ARMAR DETALLES XML
    // =====================================
    $detallesXml = '';
    $subtotalSinImpuestos = 0.00;
    $totalDescuento = 0.00;
    $totalIva = 0.00;

    foreach ($productos as $item) {
        $descripcion = htmlspecialchars($item['producto'] ?? 'Producto', ENT_XML1, 'UTF-8');
        $cantidad = (float)($item['cantidad'] ?? 1);
        $precioUnitarioConIva = (float)($item['precio'] ?? 0);

        // Precio con IVA incluido → convertir a base
        $precioUnitarioSinIva = round($precioUnitarioConIva / 1.15, 6);
        $precioTotalSinImpuesto = round($precioUnitarioSinIva * $cantidad, 2);
        $ivaLinea = round(($precioUnitarioConIva * $cantidad) - $precioTotalSinImpuesto, 2);

        $subtotalSinImpuestos += $precioTotalSinImpuesto;
        $totalIva += $ivaLinea;

        $detallesXml .= "
        <detalle>
            <codigoPrincipal>" . intval($item['id_producto'] ?? 0) . "</codigoPrincipal>
            <descripcion>{$descripcion}</descripcion>
            <cantidad>" . number_format($cantidad, 2, '.', '') . "</cantidad>
            <precioUnitario>" . number_format($precioUnitarioSinIva, 6, '.', '') . "</precioUnitario>
            <descuento>0.00</descuento>
            <precioTotalSinImpuesto>" . number_format($precioTotalSinImpuesto, 2, '.', '') . "</precioTotalSinImpuesto>
            <impuestos>
                <impuesto>
                    <codigo>2</codigo>
                    <codigoPorcentaje>4</codigoPorcentaje>
                    <tarifa>15</tarifa>
                    <baseImponible>" . number_format($precioTotalSinImpuesto, 2, '.', '') . "</baseImponible>
                    <valor>" . number_format($ivaLinea, 2, '.', '') . "</valor>
                </impuesto>
            </impuestos>
        </detalle>";
    }

    $subtotalSinImpuestos = round($subtotalSinImpuestos, 2);
    $totalIva = round($totalIva, 2);
    $importeTotal = round($subtotalSinImpuestos + $totalIva, 2);

    // =====================================
    // 6) ARMAR XML FACTURA
    // =====================================
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<factura id="comprobante" version="1.1.0">
    <infoTributaria>
        <ambiente>' . $ambiente . '</ambiente>
        <tipoEmision>' . $tipoEmision . '</tipoEmision>
        <razonSocial>MI NEGOCIO</razonSocial>
        <nombreComercial>MI NEGOCIO</nombreComercial>
        <ruc>' . $ruc . '</ruc>
        <claveAcceso>' . $claveAcceso . '</claveAcceso>
        <codDoc>' . $codDoc . '</codDoc>
        <estab>' . $estab . '</estab>
        <ptoEmi>' . $ptoEmi . '</ptoEmi>
        <secuencial>' . $secuencial . '</secuencial>
        <dirMatriz>ECUADOR</dirMatriz>
    </infoTributaria>
    <infoFactura>
        <fechaEmision>' . $fechaEmision . '</fechaEmision>
        <dirEstablecimiento>ECUADOR</dirEstablecimiento>
        <obligadoContabilidad>NO</obligadoContabilidad>
        <tipoIdentificacionComprador>' . htmlspecialchars($factura['tipo_identificacion'], ENT_XML1, 'UTF-8') . '</tipoIdentificacionComprador>
        <razonSocialComprador>' . htmlspecialchars($factura['cliente_nombre'], ENT_XML1, 'UTF-8') . '</razonSocialComprador>
        <identificacionComprador>' . htmlspecialchars($factura['cliente_identificacion'], ENT_XML1, 'UTF-8') . '</identificacionComprador>
        <totalSinImpuestos>' . number_format($subtotalSinImpuestos, 2, '.', '') . '</totalSinImpuestos>
        <totalDescuento>' . number_format($totalDescuento, 2, '.', '') . '</totalDescuento>
        <totalConImpuestos>
            <totalImpuesto>
                <codigo>2</codigo>
                <codigoPorcentaje>4</codigoPorcentaje>
                <baseImponible>' . number_format($subtotalSinImpuestos, 2, '.', '') . '</baseImponible>
                <valor>' . number_format($totalIva, 2, '.', '') . '</valor>
            </totalImpuesto>
        </totalConImpuestos>
        <propina>0.00</propina>
        <importeTotal>' . number_format($importeTotal, 2, '.', '') . '</importeTotal>
        <moneda>DOLAR</moneda>
        <pagos>
            <pago>
                <formaPago>' . htmlspecialchars($factura['metodo_pago'], ENT_XML1, 'UTF-8') . '</formaPago>
                <total>' . number_format($importeTotal, 2, '.', '') . '</total>
                <plazo>0</plazo>
                <unidadTiempo>DÍAS</unidadTiempo>
            </pago>
        </pagos>
    </infoFactura>
    <detalles>
        ' . $detallesXml . '
    </detalles>
</factura>';

    // =====================================
    // 7) GUARDAR XML EN DISCO
    // =====================================
    $dirXml = $_SERVER['DOCUMENT_ROOT'] . '/negocioencontrol/negocios/modulos/xml/';
    if (!is_dir($dirXml)) {
        mkdir($dirXml, 0777, true);
    }

    $nombreArchivo = 'factura_' . $id_factura . '_' . $claveAcceso . '.xml';
    $rutaXml = $dirXml . $nombreArchivo;

    if (file_put_contents($rutaXml, $xml) === false) {
        throw new Exception("No se pudo guardar el XML");
    }

    // =====================================
    // 8) ACTUALIZAR FACTURA
    // =====================================
    $estado_sri = 'xml_generado';

    $stmt = $conexion->prepare("
        UPDATE facturacion_ventas
        SET clave_acceso = ?, xml_generado = ?, estado_sri = ?, ambiente = 'pruebas'
        WHERE id_factura = ?
    ");
    $stmt->bind_param("sssi", $claveAcceso, $nombreArchivo, $estado_sri, $id_factura);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo actualizar la factura: " . $stmt->error);
    }

    $stmt->close();
    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => 'XML generado correctamente',
        'id_factura' => $id_factura,
        'clave_acceso' => $claveAcceso,
        'archivo_xml' => $nombreArchivo,
        'estado_sri' => $estado_sri
    ]);

} catch (Exception $e) {
    $conexion->rollback();

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al generar XML para SRI',
        'error' => $e->getMessage()
    ]);
}
?>