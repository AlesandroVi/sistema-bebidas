<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

if (usuario_actual()) {
    header('Location: index.php');
    exit;
}

$error = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if (segundos_de_bloqueo() > 0) {
        $error = 'Demasiados intentos. Esperá unos minutos y volvé a probar.';
    } elseif ($usuario === '' || $clave === '') {
        $error = 'Completá usuario y contraseña.';
    } else {
        $conn = conectar();
        $ok = intentar_login($conn, $usuario, $clave);
        $conn->close();
        if ($ok) {
            header('Location: index.php');
            exit;
        }
        registrar_intento_fallido();
        // Mismo mensaje para usuario o clave incorrectos: no revela qué usuarios existen
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso del personal — Bebidas</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍺</text></svg>">
</head>
<body class="pagina-login">

<main class="login-caja">
    <div class="login-logo" aria-hidden="true">🍺</div>
    <h1>Acceso del personal</h1>
    <p class="login-ayuda">Ingresá para administrar productos y pedidos.</p>

    <?php if ($error): ?>
        <div class="alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="formulario-producto login-form">
        <?= campo_csrf() ?>
        <label>
            Usuario
            <input type="text" name="usuario" value="<?= e($usuario) ?>" autocomplete="username" required autofocus>
        </label>
        <label>
            Contraseña
            <input type="password" name="clave" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn-primario">Ingresar</button>
    </form>

    <a href="../index.php" class="login-volver">← Volver a la tienda</a>
</main>

</body>
</html>
