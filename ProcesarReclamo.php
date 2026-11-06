<?php
declare(strict_types=1);

require_once __DIR__ . '/formulario_config.php';

exigirMetodoPost();

$tipo = campoPost('tipo', 20);
$nombres = campoPost('nombres', 160);
$documento = campoPost('documento', 30);
$telefono = campoPost('telefono', 30);
$correo = validarCorreo(campoPost('correo', 180));
$domicilio = campoPost('domicilio', 300);
$servicio = campoPost('servicio', 40);
$monto = campoPost('monto', 30);
$detalle = campoPost('detalle', 5000);
$pedido = campoPost('pedido', 3000);
$esMenor = isset($_POST['es_menor']);
$apoderadoNombre = campoPost('apoderado_nombre', 160);
$apoderadoDoc = campoPost('apoderado_documento', 30);

exigirCampos([$tipo, $nombres, $documento, $telefono, $correo, $domicilio, $servicio, $detalle]);
exigirOpcion($tipo, ['Reclamo', 'Queja'], 'tipo de solicitud');
exigirOpcion($servicio, ['limpieza', 'saneamiento', 'ambos', 'otro'], 'servicio');

if (!isset($_POST['declaracion'])) {
    responderError(400, 'Debes aceptar la declaración para enviar la solicitud.');
}

if ($esMenor) {
    exigirCampos([$apoderadoNombre, $apoderadoDoc]);
}

$numeroReferencia = 'SANSERVI-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
$fecha = date('d/m/Y H:i');
$asunto = "[$tipo] Libro de Reclamaciones Web - $numeroReferencia";

$cuerpo = "Se recibió una nueva solicitud mediante el Libro de Reclamaciones virtual.\n\n";
$cuerpo .= "N° de referencia: $numeroReferencia\n";
$cuerpo .= "Fecha y hora: $fecha\n";
$cuerpo .= "Tipo de solicitud: $tipo\n\n";
$cuerpo .= "── DATOS DEL CONSUMIDOR ──\n";
$cuerpo .= "Nombres y apellidos: $nombres\n";
$cuerpo .= "Documento de identidad: $documento\n";
$cuerpo .= "Teléfono: $telefono\n";
$cuerpo .= "Correo: $correo\n";
$cuerpo .= "Domicilio: $domicilio\n";
$cuerpo .= '¿Es menor de edad?: ' . ($esMenor ? 'Sí' : 'No') . "\n";

if ($esMenor) {
    $cuerpo .= "Nombre del apoderado: $apoderadoNombre\n";
    $cuerpo .= "Documento del apoderado: $apoderadoDoc\n";
}

$cuerpo .= "\n── DETALLE DE LA CONTRATACIÓN ──\n";
$cuerpo .= "Servicio: $servicio\n";
$cuerpo .= 'Monto reclamado: ' . ($monto !== '' ? $monto : 'No especificado') . "\n";
$cuerpo .= "\n── DETALLE DEL RECLAMO / QUEJA ──\n";
$cuerpo .= "Descripción de los hechos:\n$detalle\n\n";
$cuerpo .= 'Pedido del consumidor: ' . ($pedido !== '' ? $pedido : 'No especificado') . "\n\n";
$cuerpo .= 'El plazo máximo de respuesta es de 15 días hábiles improrrogables.';

/*
 * Para que “registrado” signifique almacenamiento permanente, inserta aquí el
 * reclamo mediante PDO siguiendo el ejemplo de formulario_config.php. Realiza
 * el INSERT antes del correo y guarda también $numeroReferencia.
 */
if (!enviarCorreo($asunto, $cuerpo, cabecerasCorreo($correo))) {
    responderError(500, 'No pudimos enviar tu solicitud. Escríbenos a ' . FORMULARIO_DESTINO . '.');
}

redirigir('gracias-reclamo.html?ref=' . urlencode($numeroReferencia));
