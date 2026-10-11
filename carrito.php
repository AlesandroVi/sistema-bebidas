<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$conn = conectar();

// Mensaje que se muestra una sola vez después de redirigir
function avisar($texto)
{
    $_SESSION['aviso'] = $texto;
}

// Datos actuales de una bebida activa, o null si no existe
function buscar_bebida($conn, $id)
{
    $stmt = $conn->prepare("SELECT id, nombre, emoji, precio, stock FROM bebidas WHERE id = ? AND activo = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $bebida = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $bebida;
}

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['bebida_id'] ?? 0);
    $qty    = max(1, (int)($_POST['cantidad'] ?? 1));

    // Agregar producto al carrito (se suma a lo que ya había, sin superar el stock)
    if ($accion === 'agregar') {
        $bebida = buscar_bebida($conn, $id);
        if ($bebida && $bebida['stock'] > 0) {
            $previa = $_SESSION['carrito'][$id]['cantidad'] ?? 0;
            $nueva  = min($previa + $qty, (int)$bebida['stock']);
            if ($nueva < $previa + $qty) {
                avisar("Solo hay {$bebida['stock']} unidades de {$bebida['nombre']}.");
            }
            $_SESSION['carrito'][$id] = [
                'id'       => (int)$bebida['id'],
                'nombre'   => $bebida['nombre'],
                'emoji'    => $bebida['emoji'],
                'precio'   => (float)$bebida['precio'],
                'cantidad' => $nueva,
                'subtotal' => $bebida['precio'] * $nueva,
            ];
        }
        header("Location: index.php");
        exit;
    }

    // Actualizar cantidad desde el carrito (sin superar el stock)
    if ($accion === 'actualizar') {
        if (isset($_SESSION['carrito'][$id])) {
            $bebida = buscar_bebida($conn, $id);
            if (!$bebida || $bebida['stock'] <= 0) {
                unset($_SESSION['carrito'][$id]);
                avisar("Ese producto ya no está disponible y se quitó del pedido.");
            } else {
                if ($qty > $bebida['stock']) {
                    $qty = (int)$bebida['stock'];
                    avisar("Solo hay {$bebida['stock']} unidades de {$bebida['nombre']}.");
                }
                $_SESSION['carrito'][$id]['cantidad'] = $qty;
                $_SESSION['carrito'][$id]['precio']   = (float)$bebida['precio'];
                $_SESSION['carrito'][$id]['subtotal'] = $bebida['precio'] * $qty;
            }
        }
        header("Location: carrito.php");
        exit;
    }

    // Quitar un producto
    if ($accion === 'quitar') {
        unset($_SESSION['carrito'][$id]);
        header("Location: carrito.php");
        exit;
    }

    // Vaciar el pedido
    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        header("Location: carrito.php");
        exit;
    }

    // Confirmar pedido: se guarda todo o nada, dentro de una transacción
    if ($accion === 'confirmar' && !empty($_SESSION['carrito'])) {
        $conn->begin_transaction();
        try {
            $items = [];
            $total = 0;

            // Se vuelven a leer precio y stock desde la base (bloqueando las filas)
            // para no confiar en datos viejos guardados en la sesión
            $stmt = $conn->prepare("SELECT nombre, precio, stock FROM bebidas WHERE id = ? AND activo = 1 FOR UPDATE");
            foreach ($_SESSION['carrito'] as $item) {
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $bebida = $stmt->get_result()->fetch_assoc();
                if (!$bebida || $bebida['stock'] < $item['cantidad']) {
                    $disponible = $bebida ? $bebida['stock'] : 0;
                    throw new RuntimeException("No hay stock suficiente de {$item['nombre']} (quedan $disponible).");
                }
                $subtotal = $bebida['precio'] * $item['cantidad'];
                $items[]  = [$item['id'], $item['cantidad'], (float)$bebida['precio'], $subtotal];
                $total   += $subtotal;
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO pedidos (total) VALUES (?)");
            $stmt->bind_param("d", $total);
            $stmt->execute();
            $pedido_id = $stmt->insert_id;
            $stmt->close();

            $detalle = $conn->prepare(
                "INSERT INTO pedido_detalle (pedido_id, bebida_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $descontar = $conn->prepare("UPDATE bebidas SET stock = stock - ? WHERE id = ?");
            foreach ($items as [$bebida_id, $cantidad, $precio, $subtotal]) {
                $detalle->bind_param("iiidd", $pedido_id, $bebida_id, $cantidad, $precio, $subtotal);
                $detalle->execute();
                $descontar->bind_param("ii", $cantidad, $bebida_id);
                $descontar->execute();
            }
            $detalle->close();
            $descontar->close();

            $conn->commit();
            $_SESSION['carrito'] = [];
            header("Location: resumen.php?pedido=$pedido_id");
            exit;
        } catch (RuntimeException $e) {
            $conn->rollback();
            avisar($e->getMessage());
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            avisar("No se pudo confirmar el pedido. Intentá de nuevo.");
        }
        header("Location: carrito.php");
        exit;
    }

    header("Location: carrito.php");
    exit;
}

$aviso = $_SESSION['aviso'] ?? '';
unset($_SESSION['aviso']);

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

    <?php if ($aviso): ?>
        <div class="alert-error"><?= htmlspecialchars($aviso) ?></div>
    <?php endif; ?>

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
                    <?= htmlspecialchars($item['emoji']) ?> <?= htmlspecialchars($item['nombre']) ?>
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
                        <button type="submit" class="btn-quitar" title="Quitar" aria-label="Quitar <?= htmlspecialchars($item['nombre']) ?>">✕</button>
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
            <form action="carrito.php" method="POST" onsubmit="return confirm('¿Vaciar el pedido?')">
                <input type="hidden" name="accion" value="vaciar">
                <button type="submit" class="btn-secundario">Vaciar pedido</button>
            </form>
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
    let val = (parseInt(input.value) || 1) + delta;
    if (val < 1) val = 1;
    input.value = val;
    input.form.submit();
}
</script>
</body>
</html>
