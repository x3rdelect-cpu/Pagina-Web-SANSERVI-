<?php
declare(strict_types=1);

require_once __DIR__ . '/formulario_config.php';

exigirMetodoPost();

$nombre = campoPost('nombre', 120);
$correo = validarCorreo(campoPost('correo', 180));
$servicio = campoPost('servicio', 40);
$mensaje = campoPost('mensaje', 3000);

exigirCampos([$nombre, $correo, $servicio]);
exigirOpcion($servicio, ['limpieza', 'saneamiento', 'ambos', 'destacamiento'], 'servicio');

$asunto = 'Nueva solicitud de contacto desde la web - ' . $servicio;
$cuerpo = "Se recibió una nueva solicitud de contacto desde la web.\n\n";
$cuerpo .= "Nombre / Empresa: $nombre\n";
$cuerpo .= "Correo: $correo\n";
$cuerpo .= "Servicio: $servicio\n";
$cuerpo .= 'Detalles: ' . ($mensaje !== '' ? $mensaje : 'No especificado') . "\n";

if (!enviarCorreo($asunto, $cuerpo, cabecerasCorreo($correo))) {
    responderError(500, 'No pudimos enviar tu solicitud en este momento. Intenta nuevamente más tarde.');
}

redirigir('gracias.html');
