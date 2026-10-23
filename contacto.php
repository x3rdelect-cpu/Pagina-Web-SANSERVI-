<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$servicio = trim((string) ($_POST['servicio'] ?? ''));
$mensaje = trim((string) ($_POST['mensaje'] ?? ''));

if ($nombre === '' || $correo === '' || $servicio === '') {
    http_response_code(400);
    exit('Por favor completa todos los campos obligatorios.');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Por favor ingresa un correo electrónico válido.');
}

$para = 'ventas@sanservi.com';
$asunto = 'Nueva solicitud de contacto desde la web - ' . str_replace(["\r", "\n"], '', $servicio);
$cuerpo = "Se recibió una nueva solicitud de contacto desde la web.\n\n";
$cuerpo .= "Nombre / Empresa: $nombre\n";
$cuerpo .= "Correo: $correo\n";
$cuerpo .= "Servicio: $servicio\n";
$cuerpo .= "Detalles: $mensaje\n";

$headers = "From: ventas@sanservi.com\r\n";
$headers .= "Reply-To: $correo\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= 'X-Mailer: PHP/' . PHP_VERSION;

if (!mail($para, $asunto, $cuerpo, $headers)) {
    http_response_code(500);
    exit('No pudimos enviar tu solicitud en este momento. Intenta nuevamente más tarde.');
}

header('Location: gracias.html', true, 303);
exit;