<?php
declare(strict_types=1);

/**
 * CONFIGURACIÓN DE LOS FORMULARIOS
 *
 * 1. Cambia FORMULARIO_DESTINO por el correo que recibirá los mensajes.
 * 2. FORMULARIO_REMITENTE debe pertenecer al dominio del sitio y existir en el
 *    servidor. No uses aquí el correo escrito por el visitante.
 * 3. La función mail() requiere que el hosting tenga habilitado el envío de
 *    correo. En XAMPP local debes configurar [mail function] en php.ini; en
 *    producción es preferible instalar PHPMailer y conectarlo por SMTP.
 * 4. Los formularios HTML ya están conectados mediante sus atributos action:
 *      index.html                -> contacto.php
 *      Contratacion.html         -> Conexion.php
 *      Libro-de-reclamo.html     -> ProcesarReclamo.php
 *
 * CONEXIÓN OPCIONAL A MYSQL
 * Si necesitas guardar los formularios además de enviarlos por correo, crea
 * las tablas correspondientes y abre una conexión PDO antes de llamar a
 * enviarCorreo():
 *
 * $pdo = new PDO(
 *     'mysql:host=localhost;dbname=sanservi;charset=utf8mb4',
 *     'USUARIO_MYSQL',
 *     'CONTRASENA_MYSQL',
 *     [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
 * );
 *
 * Usa siempre $pdo->prepare(...) con parámetros; nunca concatenes datos del
 * visitante dentro del SQL. Guarda las credenciales fuera de la carpeta
 * pública o en variables de entorno.
 */

const FORMULARIO_DESTINO = 'ventas@sanservi.com';
const FORMULARIO_REMITENTE = 'ventas@sanservi.com';

function responderError(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($mensaje);
}

function exigirMetodoPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        responderError(405, 'Método no permitido.');
    }
}

function campoPost(string $nombre, int $maximo = 2000): string
{
    $valor = trim((string) ($_POST[$nombre] ?? ''));
    if (strlen($valor) > $maximo) {
        responderError(400, "El campo {$nombre} supera la longitud permitida.");
    }

    return $valor;
}

function exigirCampos(array $campos): void
{
    foreach ($campos as $valor) {
        if ($valor === '') {
            responderError(400, 'Por favor completa todos los campos obligatorios.');
        }
    }
}

function validarCorreo(string $correo): string
{
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderError(400, 'Por favor ingresa un correo electrónico válido.');
    }

    return $correo;
}

function exigirOpcion(string $valor, array $permitidas, string $campo): void
{
    if (!in_array($valor, $permitidas, true)) {
        responderError(400, "La opción seleccionada para {$campo} no es válida.");
    }
}

function limpiarLinea(string $valor): string
{
    return str_replace(["\r", "\n"], '', $valor);
}

function cabecerasCorreo(string $responderA, ?string $tipoContenido = 'text/plain; charset=UTF-8'): string
{
    $cabeceras = 'From: ' . FORMULARIO_REMITENTE . "\r\n";
    $cabeceras .= 'Reply-To: ' . limpiarLinea($responderA) . "\r\n";
    $cabeceras .= 'X-Mailer: PHP/' . PHP_VERSION . "\r\n";

    if ($tipoContenido !== null) {
        $cabeceras .= "Content-Type: {$tipoContenido}\r\n";
    }

    return $cabeceras;
}

function enviarCorreo(string $asunto, string $cuerpo, string $cabeceras): bool
{
    return mail(FORMULARIO_DESTINO, limpiarLinea($asunto), $cuerpo, $cabeceras);
}

function redirigir(string $destino): never
{
    header('Location: ' . $destino, true, 303);
    exit;
}
