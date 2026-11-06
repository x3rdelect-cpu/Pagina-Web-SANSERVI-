<?php
declare(strict_types=1);

require_once __DIR__ . '/formulario_config.php';

exigirMetodoPost();

$nombre = campoPost('nombre', 160);
$telefono = campoPost('telefono', 30);
$correo = validarCorreo(campoPost('correo', 180));
$puesto = campoPost('puesto', 50);
$mensaje = campoPost('mensaje', 3000);

exigirCampos([$nombre, $telefono, $correo, $puesto]);
exigirOpcion(
    $puesto,
    ['operario-limpieza', 'tecnico-saneamiento', 'supervisor-campo', 'administrativo'],
    'puesto'
);

if (!preg_match('/^[0-9+()\s-]{7,30}$/', $telefono)) {
    responderError(400, 'El teléfono ingresado no es válido.');
}

$asunto = 'Nueva postulación web - ' . $puesto;
$cuerpo = "Detalles del postulante recibido a través de la web:\n\n";
$cuerpo .= "Nombre completo: $nombre\n";
$cuerpo .= "Teléfono / Celular: $telefono\n";
$cuerpo .= "Correo de contacto: $correo\n";
$cuerpo .= "Puesto al que postula: $puesto\n";
$cuerpo .= "Mensaje adicional: " . ($mensaje !== '' ? $mensaje : 'No especificado') . "\n";

$cabeceras = cabecerasCorreo($correo, null);

$archivo = $_FILES['cv'] ?? null;
if (is_array($archivo) && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        responderError(400, 'No se pudo recibir el CV. Intenta nuevamente.');
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        responderError(400, 'El CV supera el tamaño máximo permitido de 5 MB.');
    }

    $nombreOriginal = basename((string) $archivo['name']);
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $extensionesPermitidas = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $extensionesPermitidas, true)) {
        responderError(400, 'El CV debe estar en formato PDF, DOC o DOCX.');
    }

    $nombreArchivo = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreOriginal) ?: "curriculum.$extension";

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
        responderError(400, 'El tipo de archivo del CV no es válido.');
    }

    $limite = '=_Sanservi_' . bin2hex(random_bytes(12));
    $contenidoArchivo = file_get_contents((string) $archivo['tmp_name']);
    if ($contenidoArchivo === false) {
        responderError(400, 'No se pudo leer el CV enviado.');
    }

    $cabeceras .= "MIME-Version: 1.0\r\n";
    $cabeceras .= "Content-Type: multipart/mixed; boundary=\"$limite\"\r\n";
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
    $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";
}

if (!enviarCorreo($asunto, $cuerpo, $cabeceras)) {
    responderError(500, 'Hubo un error al procesar tu postulación. Intenta nuevamente más tarde.');
}

redirigir('gracias.html');
