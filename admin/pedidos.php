<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requerir_login();

$conn = conectar();

$pedidos = $conn->query(
    'SELECT id, fecha, total, estado FROM pedidos ORDER BY fecha DESC, id DESC LIMIT 200'
)->fetch_all(MYSQLI_ASSOC);

// Detalle de todos los pedidos listados en una sola consulta, agrupado por pedido
$detalles = [];
if ($pedidos) {
    $ids = implode(',', array_map('intval', array_column($pedidos, 'id')));
    $filas = $conn->query(
        "SELECT pd.pedido_id, pd.cantidad, pd.subtotal, b.nombre, b.emoji
         FROM pedido_detalle pd JOIN bebidas b ON pd.bebida_id = b.id
         WHERE pd.pedido_id IN ($ids)
         ORDER BY b.nombre"
    );
    foreach ($filas as $fila) {
        $detalles[$fila['pedido_id']][] = $fila;
    }
}

$conn->close();

$titulo = 'Pedidos';
$seccion = 'pedidos';
require __DIR__ . '/_encabezado.php';
?>

<main class="container">
    <h1 class="titulo-pagina">Pedidos</h1>

    <?php if (!$pedidos): ?>
        <div class="carrito-vacio">
            <p>Todavía no hay pedidos.</p>
        </div>
    <?php else: ?>
        <div class="tabla-admin-contenedor">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th scope="col">N.º</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Productos</th>
                        <th scope="col" class="num">Total</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td>#<?= (int)$pedido['id'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($pedido['fecha']))) ?></td>
                        <td>
                            <ul class="lista-detalle">
                                <?php foreach ($detalles[$pedido['id']] ?? [] as $item): ?>
                                    <li><?= (int)$item['cantidad'] ?> × <?= e($item['emoji']) ?> <?= e($item['nombre']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td class="num"><?= precio($pedido['total']) ?></td>
                        <td><?= e(ucfirst($pedido['estado'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
