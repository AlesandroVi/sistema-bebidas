<?php
/**
 * Crea un usuario administrador o cambia su contraseña.
 * Se ejecuta solo desde la consola, nunca desde el navegador:
 *
 *   php crear_admin.php
 *
 * La contraseña se guarda con password_hash (nunca en texto plano).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/includes/db.php';

function preguntar($texto)
{
    echo $texto;
    return trim((string)fgets(STDIN));
}

$usuario = preguntar('Usuario: ');
$clave = preguntar('Contraseña (mínimo 8 caracteres): ');

if ($usuario === '' || mb_strlen($usuario) > 50) {
    exit("El usuario debe tener entre 1 y 50 caracteres.\n");
}
if (mb_strlen($clave) < 8) {
    exit("La contraseña debe tener al menos 8 caracteres.\n");
}

$hash = password_hash($clave, PASSWORD_DEFAULT);
$conn = conectar();
$stmt = $conn->prepare(
    'INSERT INTO usuarios (usuario, clave_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE clave_hash = VALUES(clave_hash)'
);
$stmt->bind_param('ss', $usuario, $hash);
$stmt->execute();
echo $stmt->affected_rows === 1
    ? "Usuario \"$usuario\" creado.\n"
    : "Contraseña de \"$usuario\" actualizada.\n";
$stmt->close();
$conn->close();
