<?php
require_once __DIR__ . '/../includes/auth.php';

// Solo por POST y con token, para que un link externo no pueda cerrar la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    cerrar_sesion_admin();
}

header('Location: login.php');
exit;
