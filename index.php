<?php
session_start();
require_once 'includes/db.php';

// Inicializar carrito en sesión
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$conn = conectar();

// Obtener categorías
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre");

// Obtener bebidas (con filtro de categoría si se aplica)
$filtro_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sql = "SELECT b.*, c.nombre AS categoria FROM bebidas b 
        LEFT JOIN categorias c ON b.categoria_id = c.id 
        WHERE b.activo = 1";
if ($filtro_cat > 0) {
    $sql .= " AND b.categoria_id = $filtro_cat";
}
$sql .= " ORDER BY c.nombre, b.nombre";
$bebidas = $conn->query($sql);

$conn->close();

// Calcular total del carrito
$total_carrito = 0;
$items_carrito = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total_carrito += $item['subtotal'];
    $items_carrito += $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bebidas — Pedidos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-inner">
        <div class="header-logo">
            <span class="logo-icon">🍺</span>
            <span class="logo-text">Bebidas</span>
        </div>
        <div class="header-actions">
            <a href="agregar_producto.php" class="btn-primario">+ Agregar producto</a>
            <a href="carrito.php" class="btn-carrito">
                🛒 Ver pedido
                <?php if ($items_carrito > 0): ?>
                    <span class="badge"><?= $items_carrito ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>

<main class="container">
    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'producto_agregado'): ?>
        <div class="alert-success">Producto agregado correctamente.</div>
    <?php endif; ?>

    <!-- FILTRO CATEGORÍAS -->
    <div class="filtros">
        <a href="index.php" class="filtro-btn <?= $filtro_cat === 0 ? 'activo' : '' ?>">Todas</a>
        <?php while ($cat = $categorias->fetch_assoc()): ?>
            <a href="?cat=<?= $cat['id'] ?>" 
               class="filtro-btn <?= $filtro_cat === $cat['id'] ? 'activo' : '' ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </a>
        <?php endwhile; ?>
    </div>

    <!-- GRID DE BEBIDAS -->
    <div class="grid-bebidas">
        <?php while ($b = $bebidas->fetch_assoc()): 
            $sin_stock = $b['stock'] == 0;
            $stock_bajo = $b['stock'] > 0 && $b['stock'] <= 5;
            $en_carrito = isset($_SESSION['carrito'][$b['id']]) ? $_SESSION['carrito'][$b['id']]['cantidad'] : 0;
        ?>
        <div class="card-bebida <?= $sin_stock ? 'sin-stock' : '' ?>">
            <div class="card-emoji"><?= $b['emoji'] ?></div>
            <h2 class="card-nombre"><?= htmlspecialchars($b['nombre']) ?></h2>
            <div class="card-categoria"><?= htmlspecialchars($b['categoria']) ?></div>
            <div class="card-precio">$<?= number_format($b['precio'], 0, ',', '.') ?></div>

            <?php if ($sin_stock): ?>
                <span class="badge-stock rojo">Sin stock</span>
            <?php elseif ($stock_bajo): ?>
                <span class="badge-stock amarillo">Quedan <?= $b['stock'] ?></span>
            <?php else: ?>
                <span class="badge-stock verde">Stock: <?= $b['stock'] ?></span>
            <?php endif; ?>

            <?php if (!$sin_stock): ?>
            <form action="carrito.php" method="POST" class="form-agregar">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="bebida_id" value="<?= $b['id'] ?>">
                <div class="control-cantidad">
                    <button type="button" class="btn-cant" onclick="cambiarCant(this, -1)">−</button>
                    <input type="number" name="cantidad" value="1" min="1" max="<?= $b['stock'] ?>" class="input-cant">
                    <button type="button" class="btn-cant" onclick="cambiarCant(this, 1, <?= $b['stock'] ?>)">+</button>
                </div>
                <button type="submit" class="btn-agregar <?= $en_carrito > 0 ? 'en-carrito' : '' ?>">
                    <?= $en_carrito > 0 ? "✓ En pedido ($en_carrito)" : 'Agregar' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

</main>

<!-- BARRA DE TOTAL FLOTANTE -->
<?php if ($total_carrito > 0): ?>
<div class="barra-total">
    <span><?= $items_carrito ?> producto<?= $items_carrito != 1 ? 's' : '' ?></span>
    <strong>Total: $<?= number_format($total_carrito, 0, ',', '.') ?></strong>
    <a href="carrito.php" class="btn-ver-pedido">Ver pedido →</a>
</div>
<?php endif; ?>

<script>
function cambiarCant(btn, delta, max) {
    const input = btn.parentElement.querySelector('.input-cant');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (max && val > max) val = max;
    input.value = val;
}
</script>
</body>
</html>
