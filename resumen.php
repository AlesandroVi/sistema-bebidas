<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/funciones.php';

$pedido_id = isset($_GET['pedido']) ? (int)$_GET['pedido'] : 0;
if ($pedido_id === 0) {
    header("Location: index.php");
    exit;
}

$conn = conectar();

$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT pd.*, b.nombre, b.emoji FROM pedido_detalle pd
     JOIN bebidas b ON pd.bebida_id = b.id
     WHERE pd.pedido_id = ?"
);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$detalle = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado — Bebidas</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍺</text></svg>">
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="index.php" class="btn-volver">← Volver al catálogo</a>
        <div class="header-logo">
            <span class="logo-icon">🍺</span>
            <span class="logo-text">Bebidas</span>
        </div>
        <div class="espaciador" aria-hidden="true"></div>
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
                <?= htmlspecialchars($item['emoji']) ?> <?= htmlspecialchars($item['nombre']) ?>
            </span>
            <span><?= $item['cantidad'] ?></span>
            <span><?= precio($item['precio_unitario']) ?></span>
            <span class="col-subtotal"><?= precio($item['subtotal']) ?></span>
        </div>
        <?php endwhile; ?>

        <div class="fila-total-final">
            <span>Total del pedido</span>
            <span><?= precio($pedido['total']) ?></span>
        </div>
    </div>

    <div class="acciones-carrito acciones-centradas">
        <a href="index.php" class="btn-primario">Hacer otro pedido</a>
    </div>

</main>
</body>
</html>
