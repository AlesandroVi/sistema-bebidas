<?php
/**
 * Crea un usuario administrador o cambia su contraseña.
 * Se ejecuta solo desde la consola, nunca desde el navegador:
 *
 *   php crear_admin.php
 *
 * La contraseña no se muestra mientras se escribe y se guarda con
 * password_hash (nunca en texto plano).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/includes/db.php';

// Se averigua una sola vez, antes de leer nada (después PHP ya no puede comprobarlo)
define('ES_CONSOLA', stream_isatty(STDIN));

function preguntar($texto)
{
    echo $texto;
    return trim((string)fgets(STDIN));
}

/** Pide un dato sin mostrarlo en pantalla (si la entrada es una consola). */
function preguntar_oculto($texto)
{
    if (!ES_CONSOLA) {
        return preguntar($texto);
    }
    echo $texto;
    if (PHP_OS_FAMILY === 'Windows') {
        // PowerShell lee la clave sin mostrarla y la devuelve en texto para hashearla
        $comando = 'powershell -NoProfile -Command "$c = Read-Host -AsSecureString; '
            . '[Runtime.InteropServices.Marshal]::PtrToStringAuto('
            . '[Runtime.InteropServices.Marshal]::SecureStringToBSTR($c))"';
        $valor = rtrim((string)shell_exec($comando), "\r\n");
    } else {
        system('stty -echo');
        $valor = trim((string)fgets(STDIN));
        system('stty echo');
        echo "\n";
    }
    return $valor;
}

$usuario = preguntar('Usuario: ');
if ($usuario === '' || mb_strlen($usuario) > 50) {
    exit("El usuario debe tener entre 1 y 50 caracteres.\n");
}

$clave = preguntar_oculto('Contraseña (mínimo 8 caracteres, no se muestra al escribir): ');
if (mb_strlen($clave) < 8) {
    exit("La contraseña debe tener al menos 8 caracteres.\n");
}
if (preguntar_oculto('Repetí la contraseña: ') !== $clave) {
    exit("Las contraseñas no coinciden. No se guardó nada.\n");
}

try {
    $conn = conectar();
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        'INSERT INTO usuarios (usuario, clave_hash) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE clave_hash = VALUES(clave_hash)'
    );
    $stmt->bind_param('ss', $usuario, $hash);
    $stmt->execute();
    echo $stmt->affected_rows === 1
        ? "Listo: usuario \"$usuario\" creado.\n"
        : "Listo: contraseña de \"$usuario\" actualizada.\n";
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    if (strpos($e->getMessage(), "usuarios' doesn't exist") !== false) {
        exit("Falta la tabla de usuarios: importá database.sql en phpMyAdmin y volvé a intentar.\n");
    }
    exit('No se pudo conectar o guardar en la base de datos: ' . $e->getMessage() . "\n");
}
