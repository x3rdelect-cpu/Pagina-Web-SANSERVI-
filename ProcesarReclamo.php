<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

    // ── DATOS DEL TIPO DE SOLICITUD ──
    $tipo = trim((string) ($_POST['tipo'] ?? ''));

    // ── DATOS DEL CONSUMIDOR ──
    $nombres    = trim((string) ($_POST['nombres'] ?? ''));
    $documento  = trim((string) ($_POST['documento'] ?? ''));
    $telefono   = trim((string) ($_POST['telefono'] ?? ''));
    $correo     = trim((string) ($_POST['correo'] ?? ''));
    $domicilio  = trim((string) ($_POST['domicilio'] ?? ''));

    // ── DATOS DEL APODERADO (SI EL RECLAMANTE ES MENOR DE EDAD) ──
    $esMenor          = isset($_POST['es_menor']) ? 'Sí' : 'No';
    $apoderadoNombre  = trim((string) ($_POST['apoderado_nombre'] ?? ''));
    $apoderadoDoc     = trim((string) ($_POST['apoderado_documento'] ?? ''));

    // ── DETALLE DE LA CONTRATACIÓN ──
    $servicio = trim((string) ($_POST['servicio'] ?? ''));
    $monto    = trim((string) ($_POST['monto'] ?? '')) ?: 'No especificado';

    // ── DETALLE DEL RECLAMO ──
    $detalle = trim((string) ($_POST['detalle'] ?? ''));
    $pedido  = trim((string) ($_POST['pedido'] ?? '')) ?: 'No especificado';

    if ($tipo === '' || $nombres === '' || $documento === '' || $telefono === '' ||
        $correo === '' || $domicilio === '' || $servicio === '' || $detalle === '') {
        http_response_code(400);
        exit('Por favor completa todos los campos obligatorios.');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit('Por favor ingresa un correo electrónico válido.');
    }

    // Número de referencia simple basado en fecha/hora
    $numeroReferencia = 'SANSERVI-' . date('Ymd-His');
    $fecha = date('d/m/Y H:i');

    // ── DESTINATARIO ──
    // Cambia este correo por el que se use oficialmente para atender reclamos
    $para = 'ventas@sanservi.com';
    $asunto = "[$tipo] Libro de Reclamaciones Web - $numeroReferencia";

    $cuerpo  = "Se ha recibido una nueva solicitud a través del Libro de Reclamaciones virtual de la web.\n\n";
    $cuerpo .= "N° de Referencia: $numeroReferencia\n";
    $cuerpo .= "Fecha y hora: $fecha\n";
    $cuerpo .= "Tipo de solicitud: $tipo\n\n";

    $cuerpo .= "── DATOS DEL CONSUMIDOR ──\n";
    $cuerpo .= "Nombres y Apellidos: $nombres\n";
    $cuerpo .= "Documento de Identidad: $documento\n";
    $cuerpo .= "Teléfono: $telefono\n";
    $cuerpo .= "Correo: $correo\n";
    $cuerpo .= "Domicilio: $domicilio\n";
    $cuerpo .= "¿Es menor de edad?: $esMenor\n";

    if ($esMenor === 'Sí') {
        $cuerpo .= "Nombre del Apoderado: $apoderadoNombre\n";
        $cuerpo .= "Documento del Apoderado: $apoderadoDoc\n";
    }

    $cuerpo .= "\n── DETALLE DE LA CONTRATACIÓN ──\n";
    $cuerpo .= "Servicio: $servicio\n";
    $cuerpo .= "Monto Reclamado: $monto\n";

    $cuerpo .= "\n── DETALLE DEL RECLAMO / QUEJA ──\n";
    $cuerpo .= "Descripción de los hechos:\n$detalle\n\n";
    $cuerpo .= "Pedido del consumidor:\n$pedido\n\n";

    $cuerpo .= "Recuerda: el plazo de respuesta es de 30 días calendario conforme al D.S. N° 101-2022-PCM.";

    $headers = "From: ventas@sanservi.com\r\n";
    $headers .= "Reply-To: " . $correo . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    if (mail($para, $asunto, $cuerpo, $headers)) {
        // Redirige a una página de confirmación con el número de referencia
        header("Location: gracias-reclamo.html?ref=" . urlencode($numeroReferencia), true, 303);
        exit;
    } else {
        http_response_code(500);
        echo "Hubo un error al registrar tu reclamación en nuestro sistema. Por favor, inténtalo escribiendo directamente a ventas@sanservi.com";
    }
?>
