<?php

require_once __DIR__ . '/sesion.php';

// Minutos sin actividad después de los cuales se cierra la sesión del panel
const MINUTOS_INACTIVIDAD = 30;

// Intentos fallidos permitidos antes de bloquear el inicio de sesión un rato
const MAX_INTENTOS = 5;
const MINUTOS_BLOQUEO = 5;

/** Datos del administrador logueado, o null si no hay nadie. */
function usuario_actual()
{
    if (empty($_SESSION['admin'])) {
        return null;
    }
    if (time() - ($_SESSION['admin']['ultimo_uso'] ?? 0) > MINUTOS_INACTIVIDAD * 60) {
        cerrar_sesion_admin();
        return null;
    }
    $_SESSION['admin']['ultimo_uso'] = time();
    return $_SESSION['admin'];
}

/** Corta la página y manda al login si no hay un administrador logueado. */
function requerir_login()
{
    if (!usuario_actual()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verifica usuario y contraseña. Devuelve true si son correctos.
 * Se usa password_verify, así en la base solo se guarda el hash de la clave.
 */
function intentar_login($conn, $usuario, $clave)
{
    $stmt = $conn->prepare('SELECT id, usuario, clave_hash FROM usuarios WHERE usuario = ?');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fila || !password_verify($clave, $fila['clave_hash'])) {
        return false;
    }

    // Nuevo id de sesión al loguearse, para evitar la fijación de sesión
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'         => (int)$fila['id'],
        'usuario'    => $fila['usuario'],
        'ultimo_uso' => time(),
    ];
    unset($_SESSION['login_fallidos'], $_SESSION['login_bloqueado_hasta']);
    return true;
}

function cerrar_sesion_admin()
{
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}

/** Segundos que faltan para poder volver a intentar, o 0 si no hay bloqueo. */
function segundos_de_bloqueo()
{
    return max(0, ($_SESSION['login_bloqueado_hasta'] ?? 0) - time());
}

function registrar_intento_fallido()
{
    $_SESSION['login_fallidos'] = ($_SESSION['login_fallidos'] ?? 0) + 1;
    if ($_SESSION['login_fallidos'] >= MAX_INTENTOS) {
        $_SESSION['login_bloqueado_hasta'] = time() + MINUTOS_BLOQUEO * 60;
        $_SESSION['login_fallidos'] = 0;
    }
}

/** Token que se incluye en cada formulario del panel para evitar envíos desde otros sitios (CSRF). */
function token_csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf()
{
    return '<input type="hidden" name="csrf" value="' . token_csrf() . '">';
}

function verificar_csrf()
{
    if (!hash_equals(token_csrf(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('La sesión expiró. Volvé atrás y recargá la página.');
    }
}
