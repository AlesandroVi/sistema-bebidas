<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$conn = conectar();

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Agregar producto al carrito
    if ($accion === 'agregar') {
        $id  = (int)$_POST['bebida_id'];
        $qty = max(1, (int)$_POST['cantidad']);

        $res = $conn->query("SELECT * FROM bebidas WHERE id = $id AND activo = 1 AND stock > 0");
        if ($bebida = $res->fetch_assoc()) {
            $qty = min($qty, $bebida['stock']);
            $_SESSION['carrito'][$id] = [
                'id'       => $bebida['id'],
                'nombre'   => $bebida['nombre'],
                'emoji'    => $bebida['emoji'],
                'precio'   => $bebida['precio'],
                'cantidad' => $qty,
                'subtotal' => $bebida['precio'] * $qty,
            ];
        }
        header("Location: index.php");
        exit;
    }

    // Actualizar cantidad desde el carrito
    if ($accion === 'actualizar') {
        $id  = (int)$_POST['bebida_id'];
        $qty = max(1, (int)$_POST['cantidad']);
        if (isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad'] = $qty;
            $_SESSION['carrito'][$id]['subtotal']  = $_SESSION['carrito'][$id]['precio'] * $qty;
        }
        header("Location: carrito.php");
        exit;
    }

    // Quitar un producto
    if ($accion === 'quitar') {
        $id = (int)$_POST['bebida_id'];
        unset($_SESSION['carrito'][$id]);
        header("Location: carrito.php");
        exit;
    }

    // Confirmar pedido
    if ($accion === 'confirmar') {
        if (!empty($_SESSION['carrito'])) {
            $total = array_sum(array_column($_SESSION['carrito'], 'subtotal'));

            // Insertar pedido
            $stmt = $conn->prepare("INSERT INTO pedidos (total) VALUES (?)");
            $stmt->bind_param("d", $total);
            $stmt->execute();
            $pedido_id = $stmt->insert_id;
            $stmt->close();

            // Insertar detalle y actualizar stock
            foreach ($_SESSION['carrito'] as $item) {
                $stmt = $conn->prepare(
                    "INSERT INTO pedido_detalle (pedido_id, bebida_id, cantidad, precio_unitario, subtotal) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("iiidd", $pedido_id, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']);
                $stmt->execute();
                $stmt->close();

                // Descontar stock
                $conn->query("UPDATE bebidas SET stock = stock - {$item['cantidad']} WHERE id = {$item['id']}");
            }

            $_SESSION['ultimo_pedido'] = $pedido_id;
            $_SESSION['carrito'] = [];
            header("Location: resumen.php?pedido=$pedido_id");
            exit;
        }
    }
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header("Location: carrito.php");
    exit;
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['subtotal'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu pedido — Bebidas</title>
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

    <h1 class="titulo-pagina">🛒 Tu pedido</h1>

    <?php if (empty($_SESSION['carrito'])): ?>
        <div class="carrito-vacio">
            <p style="font-size:48px; margin:0;">🛒</p>
            <p>No tenés productos en tu pedido.</p>
            <a href="index.php" class="btn-primario">Ver catálogo</a>
        </div>

    <?php else: ?>

        <div class="tabla-carrito">
            <div class="tabla-header">
                <span>Producto</span>
                <span>Precio</span>
                <span>Cantidad</span>
                <span>Subtotal</span>
                <span></span>
            </div>

            <?php foreach ($_SESSION['carrito'] as $id => $item): ?>
            <div class="tabla-fila">
                <span class="col-nombre">
                    <?= $item['emoji'] ?> <?= htmlspecialchars($item['nombre']) ?>
                </span>
                <span class="col-precio">$<?= number_format($item['precio'], 0, ',', '.') ?></span>
                <span class="col-cantidad">
                    <form action="carrito.php" method="POST" class="form-inline">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="bebida_id" value="<?= $id ?>">
                        <button type="button" class="btn-cant-sm" onclick="cambiarCant(this,-1)">−</button>
                        <input type="number" name="cantidad" value="<?= $item['cantidad'] ?>" min="1" class="input-cant-sm" onchange="this.form.submit()">
                        <button type="button" class="btn-cant-sm" onclick="cambiarCant(this,1)">+</button>
                    </form>
                </span>
                <span class="col-subtotal">$<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                <span class="col-quitar">
                    <form action="carrito.php" method="POST">
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="bebida_id" value="<?= $id ?>">
                        <button type="submit" class="btn-quitar" title="Quitar">✕</button>
                    </form>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- RESUMEN TOTAL -->
        <div class="resumen-total">
            <div class="fila-total">
                <span>Cantidad de productos:</span>
                <span><?= array_sum(array_column($_SESSION['carrito'], 'cantidad')) ?></span>
            </div>
            <div class="fila-total grande">
                <span>Total:</span>
                <span>$<?= number_format($total, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- ACCIONES -->
        <div class="acciones-carrito">
            <a href="carrito.php?vaciar=1" class="btn-secundario" 
               onclick="return confirm('¿Vaciar el pedido?')">Vaciar pedido</a>
            <form action="carrito.php" method="POST">
                <input type="hidden" name="accion" value="confirmar">
                <button type="submit" class="btn-primario">Confirmar pedido →</button>
            </form>
        </div>

    <?php endif; ?>

</main>

<script>
function cambiarCant(btn, delta) {
    const input = btn.parentElement.querySelector('input[name="cantidad"]');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
    input.form.submit();
}
</script>
</body>
</html>
