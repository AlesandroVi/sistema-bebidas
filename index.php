<?php
require_once 'includes/sesion.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';


$conn = conectar();

// Obtener categorías
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre");

// Obtener bebidas (con filtro de categoría si se aplica)
$filtro_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$stmt = $conn->prepare(
    "SELECT b.*, c.nombre AS categoria FROM bebidas b
     LEFT JOIN categorias c ON b.categoria_id = c.id
     WHERE b.activo = 1 AND (? = 0 OR b.categoria_id = ?)
     ORDER BY c.nombre, b.nombre"
);
$stmt->bind_param("ii", $filtro_cat, $filtro_cat);
$stmt->execute();
$bebidas = $stmt->get_result();
$stmt->close();

$conn->close();

$aviso = $_SESSION['aviso'] ?? '';
unset($_SESSION['aviso']);

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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍺</text></svg>">
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
    <?php if ($aviso): ?>
        <div class="alert-error"><?= htmlspecialchars($aviso) ?></div>
    <?php endif; ?>

    <!-- FILTRO CATEGORÍAS -->
    <div class="filtros">
        <a href="index.php" class="filtro-btn <?= $filtro_cat === 0 ? 'activo' : '' ?>">Todas</a>
        <?php while ($cat = $categorias->fetch_assoc()): ?>
            <a href="?cat=<?= $cat['id'] ?>" 
               class="filtro-btn <?= $filtro_cat === (int)$cat['id'] ? 'activo' : '' ?>">
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
            <div class="card-emoji" aria-hidden="true"><?= e($b['emoji'] ?: '🥤') ?></div>
            <h2 class="card-nombre"><?= htmlspecialchars($b['nombre']) ?></h2>
            <div class="card-categoria"><?= htmlspecialchars($b['categoria']) ?></div>
            <div class="card-precio"><?= precio($b['precio']) ?></div>

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
                    <button type="button" class="btn-cant" onclick="cambiarCant(this, -1)" aria-label="Restar uno">−</button>
                    <input type="number" name="cantidad" value="1" min="1" max="<?= $b['stock'] ?>" class="input-cant">
                    <button type="button" class="btn-cant" onclick="cambiarCant(this, 1, <?= $b['stock'] ?>)" aria-label="Sumar uno">+</button>
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

<footer class="pie">
    <span>© <?= date('Y') ?> Bebidas · Pedidos online</span>
    <a href="admin/login.php" class="pie-acceso" rel="nofollow">Acceso del personal</a>
</footer>

<!-- BARRA DE TOTAL FLOTANTE -->
<?php if ($total_carrito > 0): ?>
<div class="barra-total">
    <span><?= $items_carrito ?> producto<?= $items_carrito != 1 ? 's' : '' ?></span>
    <strong>Total: <?= precio($total_carrito) ?></strong>
    <a href="carrito.php" class="btn-ver-pedido">Ver pedido →</a>
</div>
<?php endif; ?>

<script>
function cambiarCant(btn, delta, max) {
    const input = btn.parentElement.querySelector('.input-cant');
    let val = (parseInt(input.value) || 1) + delta;
    if (val < 1) val = 1;
    if (max && val > max) val = max;
    input.value = val;
}
</script>
</body>
</html>
