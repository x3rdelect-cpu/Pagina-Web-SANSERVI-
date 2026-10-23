<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$puesto = trim((string) ($_POST['puesto'] ?? ''));
$mensaje = trim((string) ($_POST['mensaje'] ?? ''));

if ($nombre === '' || $telefono === '' || $correo === '' || $puesto === '') {
    http_response_code(400);
    exit('Por favor completa todos los campos obligatorios.');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Por favor ingresa un correo electrónico válido.');
}

$para = 'ventas@sanservi.com';
$asunto = 'Nueva postulación web - ' . str_replace(["\r", "\n"], '', $puesto);
$cuerpo = "Detalles del postulante recibido a través de la web:\n\n";
$cuerpo .= "Nombre completo: $nombre\n";
$cuerpo .= "Teléfono / Celular: $telefono\n";
$cuerpo .= "Correo de contacto: $correo\n";
$cuerpo .= "Puesto al que postula: $puesto\n";
$cuerpo .= "Mensaje adicional: " . ($mensaje !== '' ? $mensaje : 'No especificado') . "\n";

$headers = "From: ventas@sanservi.com\r\n";
$headers .= "Reply-To: $correo\r\n";
$headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

$archivo = $_FILES['cv'] ?? null;
if (is_array($archivo) && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('No se pudo recibir el CV. Intenta nuevamente.');
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        exit('El CV supera el tamaño máximo permitido de 5 MB.');
    }

    $nombreArchivo = basename((string) $archivo['name']);
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    $extensionesPermitidas = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $extensionesPermitidas, true)) {
        http_response_code(400);
        exit('El CV debe estar en formato PDF, DOC o DOCX.');
    }

    $tipoArchivo = (new finfo(FILEINFO_MIME_TYPE))->file((string) $archivo['tmp_name']);
    $tiposPermitidos = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
    ];
    if (!in_array($tipoArchivo, $tiposPermitidos[$extension], true)) {
        http_response_code(400);
        exit('El tipo de archivo del CV no es válido.');
    }

    $limite = '=_Sanservi_' . bin2hex(random_bytes(12));
    $contenidoArchivo = file_get_contents((string) $archivo['tmp_name']);
    if ($contenidoArchivo === false) {
        http_response_code(400);
        exit('No se pudo leer el CV enviado.');
    }

    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$limite\"\r\n";
    $cuerpoMultipart = "--$limite\r\n";
    $cuerpoMultipart .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $cuerpoMultipart .= $cuerpo;
    $cuerpoMultipart .= "\r\n--$limite\r\n";
    $cuerpoMultipart .= "Content-Type: $tipoArchivo; name=\"$nombreArchivo\"\r\n";
    $cuerpoMultipart .= "Content-Disposition: attachment; filename=\"$nombreArchivo\"\r\n";
    $cuerpoMultipart .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $cuerpoMultipart .= chunk_split(base64_encode($contenidoArchivo));
    $cuerpoMultipart .= "--$limite--\r\n";
    $cuerpo = $cuerpoMultipart;
} else {
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
}

if (!mail($para, $asunto, $cuerpo, $headers)) {
    http_response_code(500);
    exit('Hubo un error al procesar tu postulación. Intenta nuevamente más tarde.');
}

header('Location: gracias.html', true, 303);
exit;
