<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requerir_login();

$conn = conectar();

// Activar o desactivar un producto. No se borran: los pedidos viejos los siguen referenciando.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
    verificar_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare('UPDATE bebidas SET activo = 1 - activo WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: index.php');
    exit;
}

$productos = $conn->query(
    'SELECT b.id, b.nombre, b.emoji, b.precio, b.stock, b.activo, c.nombre AS categoria
     FROM bebidas b LEFT JOIN categorias c ON b.categoria_id = c.id
     ORDER BY b.activo DESC, c.nombre, b.nombre'
)->fetch_all(MYSQLI_ASSOC);

$resumen = $conn->query(
    'SELECT
        (SELECT COUNT(*) FROM bebidas WHERE activo = 1) AS activos,
        (SELECT COUNT(*) FROM bebidas WHERE activo = 1 AND stock <= 5) AS stock_bajo,
        (SELECT COUNT(*) FROM pedidos WHERE DATE(fecha) = CURDATE()) AS pedidos_hoy,
        (SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(fecha) = CURDATE()) AS ventas_hoy'
)->fetch_assoc();

$conn->close();

$mensajes = [
    'creado'     => 'Producto agregado correctamente.',
    'editado'    => 'Cambios guardados.',
];
$mensaje = $mensajes[$_GET['ok'] ?? ''] ?? '';

$titulo = 'Productos';
$seccion = 'productos';
require __DIR__ . '/_encabezado.php';
?>

<main class="container">

    <?php if ($mensaje): ?>
        <div class="alert-success" role="status"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <section class="tarjetas-resumen" aria-label="Resumen del día">
        <div class="tarjeta-dato">
            <span class="dato-valor"><?= (int)$resumen['activos'] ?></span>
            <span class="dato-label">Productos a la venta</span>
        </div>
        <div class="tarjeta-dato <?= $resumen['stock_bajo'] > 0 ? 'alerta' : '' ?>">
            <span class="dato-valor"><?= (int)$resumen['stock_bajo'] ?></span>
            <span class="dato-label">Con stock bajo (5 o menos)</span>
        </div>
        <div class="tarjeta-dato">
            <span class="dato-valor"><?= (int)$resumen['pedidos_hoy'] ?></span>
            <span class="dato-label">Pedidos de hoy</span>
        </div>
        <div class="tarjeta-dato">
            <span class="dato-valor"><?= precio($resumen['ventas_hoy']) ?></span>
            <span class="dato-label">Vendido hoy</span>
        </div>
    </section>

    <div class="barra-titulo">
        <h1 class="titulo-pagina">Productos</h1>
        <a href="producto.php" class="btn-primario">+ Agregar producto</a>
    </div>

    <div class="tabla-admin-contenedor">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th scope="col">Producto</th>
                    <th scope="col">Categoría</th>
                    <th scope="col" class="num">Precio</th>
                    <th scope="col" class="num">Stock</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="solo-lectores">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                <tr class="<?= $p['activo'] ? '' : 'inactivo' ?>">
                    <td><span aria-hidden="true"><?= e($p['emoji']) ?></span> <?= e($p['nombre']) ?></td>
                    <td><?= e($p['categoria'] ?? '—') ?></td>
                    <td class="num"><?= precio($p['precio']) ?></td>
                    <td class="num">
                        <?php if ($p['stock'] == 0): ?>
                            <span class="badge-stock rojo">Sin stock</span>
                        <?php elseif ($p['stock'] <= 5): ?>
                            <span class="badge-stock amarillo"><?= (int)$p['stock'] ?></span>
                        <?php else: ?>
                            <?= (int)$p['stock'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $p['activo'] ? 'A la venta' : 'Oculto' ?></td>
                    <td class="acciones-fila">
                        <a href="producto.php?id=<?= (int)$p['id'] ?>" class="btn-secundario btn-chico">Editar</a>
                        <form method="POST">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn-secundario btn-chico">
                                <?= $p['activo'] ? 'Ocultar' : 'Publicar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>
</body>
</html>
