<?php
session_start();
require_once 'includes/db.php';

$pedido_id = isset($_GET['pedido']) ? (int)$_GET['pedido'] : 0;
if ($pedido_id === 0) {
    header("Location: index.php");
    exit;
}

$conn = conectar();

$res = $conn->query("SELECT * FROM pedidos WHERE id = $pedido_id");
$pedido = $res->fetch_assoc();

if (!$pedido) {
    header("Location: index.php");
    exit;
}

$detalle = $conn->query(
    "SELECT pd.*, b.nombre, b.emoji FROM pedido_detalle pd
     JOIN bebidas b ON pd.bebida_id = b.id
     WHERE pd.pedido_id = $pedido_id"
);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado — Bebidas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="index.php" class="btn-volver">← Volver al catálogo</a>
        <div class="header-logo">
            <span class="logo-icon">🍺</span>
            <span class="logo-text">Bebidas</span>
        </div>
        <div style="width:140px;"></div>
    </div>
</header>

<main class="container container-chico">

    <div class="resumen-confirmado">
        <div class="check-icon">✓</div>
        <h1 class="titulo-confirmado">¡Pedido confirmado!</h1>
        <p class="subtitulo-confirmado">
            Pedido #<?= $pedido_id ?> — 
            <?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?>
        </p>
    </div>

    <div class="tabla-resumen">
        <div class="tabla-header">
            <span>Producto</span>
            <span>Cantidad</span>
            <span>Precio unit.</span>
            <span>Subtotal</span>
        </div>

        <?php while ($item = $detalle->fetch_assoc()): ?>
        <div class="tabla-fila">
            <span class="col-nombre">
                <?= $item['emoji'] ?> <?= htmlspecialchars($item['nombre']) ?>
            </span>
            <span><?= $item['cantidad'] ?></span>
            <span>$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></span>
            <span class="col-subtotal">$<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
        </div>
        <?php endwhile; ?>

        <div class="fila-total-final">
            <span>Total del pedido</span>
            <span>$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="acciones-carrito" style="justify-content:center;">
        <a href="index.php" class="btn-primario">Hacer otro pedido</a>
    </div>

</main>
</body>
</html>
