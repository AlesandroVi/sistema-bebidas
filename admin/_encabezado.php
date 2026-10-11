<?php
/**
 * Encabezado común del panel. Antes de incluirlo hay que definir:
 *   $titulo  → título de la pestaña
 *   $seccion → 'productos' o 'pedidos', para marcar el menú activo
 */
$admin = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo) ?> — Panel Bebidas</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍺</text></svg>">
</head>
<body>

<header class="header header-admin">
    <div class="header-inner">
        <div class="header-logo">
            <span class="logo-icon" aria-hidden="true">🍺</span>
            <span class="logo-text">Panel de administración</span>
        </div>
        <nav class="nav-admin" aria-label="Secciones del panel">
            <a href="index.php" class="<?= $seccion === 'productos' ? 'activo' : '' ?>">Productos</a>
            <a href="pedidos.php" class="<?= $seccion === 'pedidos' ? 'activo' : '' ?>">Pedidos</a>
            <a href="../index.php" target="_blank" rel="noopener">Ver tienda ↗</a>
        </nav>
        <form action="logout.php" method="POST" class="form-logout">
            <?= campo_csrf() ?>
            <span class="admin-usuario"><?= e($admin['usuario']) ?></span>
            <button type="submit" class="btn-secundario btn-chico">Cerrar sesión</button>
        </form>
    </div>
</header>
