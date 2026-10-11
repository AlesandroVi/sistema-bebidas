<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requerir_login();

$conn = conectar();
$categorias = $conn->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);

// Con ?id= se edita un producto existente; sin id se crea uno nuevo
$id = (int)($_GET['id'] ?? 0);
$producto = ['nombre' => '', 'precio' => '', 'stock' => '', 'categoria_id' => '', 'emoji' => '', 'activo' => 1];

if ($id > 0) {
    $stmt = $conn->prepare('SELECT nombre, precio, stock, categoria_id, emoji, activo FROM bebidas WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$producto) {
        $conn->close();
        header('Location: index.php');
        exit;
    }
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $producto = [
        'nombre'       => trim($_POST['nombre'] ?? ''),
        'precio'       => $_POST['precio'] ?? '',
        'stock'        => $_POST['stock'] ?? '',
        'categoria_id' => $_POST['categoria_id'] ?? '',
        'emoji'        => trim($_POST['emoji'] ?? ''),
        'activo'       => isset($_POST['activo']) ? 1 : 0,
    ];
    $precio = filter_var($producto['precio'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($producto['stock'], FILTER_VALIDATE_INT);
    $categoria_id = filter_var($producto['categoria_id'], FILTER_VALIDATE_INT);

    if ($producto['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($producto['nombre']) > 150) {
        $errores[] = 'El nombre no puede tener más de 150 caracteres.';
    }
    if ($precio === false || $precio < 0) {
        $errores[] = 'El precio debe ser un número mayor o igual a 0.';
    }
    if ($stock === false || $stock < 0) {
        $errores[] = 'El stock debe ser un número entero mayor o igual a 0.';
    }
    if ($categoria_id === false || !in_array($categoria_id, array_map('intval', array_column($categorias, 'id')), true)) {
        $errores[] = 'Seleccioná una categoría válida.';
    }
    if (mb_strlen($producto['emoji']) > 10) {
        $errores[] = 'El emoji no puede tener más de 10 caracteres.';
    }

    if (empty($errores)) {
        // Sin emoji, se usa uno genérico para que la tarjeta no quede vacía
        $emoji = $producto['emoji'] !== '' ? $producto['emoji'] : '🥤';
        $activo = $producto['activo'];

        if ($id > 0) {
            $stmt = $conn->prepare(
                'UPDATE bebidas SET nombre = ?, precio = ?, stock = ?, categoria_id = ?, emoji = ?, activo = ? WHERE id = ?'
            );
            $stmt->bind_param('sdiisii', $producto['nombre'], $precio, $stock, $categoria_id, $emoji, $activo, $id);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO bebidas (nombre, precio, stock, categoria_id, emoji, activo) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sdiisi', $producto['nombre'], $precio, $stock, $categoria_id, $emoji, $activo);
        }

        try {
            $stmt->execute();
            $stmt->close();
            $conn->close();
            header('Location: index.php?ok=' . ($id > 0 ? 'editado' : 'creado'));
            exit;
        } catch (mysqli_sql_exception $e) {
            $errores[] = 'No se pudo guardar el producto. Intentá de nuevo.';
        }
    }
}

$conn->close();

$titulo = $id > 0 ? 'Editar producto' : 'Nuevo producto';
$seccion = 'productos';
require __DIR__ . '/_encabezado.php';
?>

<main class="container container-chico">
    <a href="index.php" class="link-volver">← Volver a productos</a>
    <h1 class="titulo-pagina"><?= e($titulo) ?></h1>

    <?php if ($errores): ?>
        <div class="alert-error" role="alert">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="formulario-producto">
        <?= campo_csrf() ?>
        <label>
            Nombre
            <input type="text" name="nombre" maxlength="150" value="<?= e($producto['nombre']) ?>" required>
        </label>

        <div class="fila-campos">
            <label>
                Precio ($)
                <input type="number" name="precio" step="0.01" min="0" value="<?= e($producto['precio']) ?>" required>
            </label>
            <label>
                Stock (unidades)
                <input type="number" name="stock" min="0" value="<?= e($producto['stock']) ?>" required>
            </label>
        </div>

        <div class="fila-campos">
            <label>
                Categoría
                <select name="categoria_id" required>
                    <option value="">Seleccioná una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int)$categoria['id'] ?>"
                            <?= (string)$producto['categoria_id'] === (string)$categoria['id'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Emoji
                <input type="text" name="emoji" maxlength="10" value="<?= e($producto['emoji']) ?>" placeholder="Opcional (ej: 🥤)">
            </label>
        </div>

        <label class="label-checkbox">
            <input type="checkbox" name="activo" value="1" <?= $producto['activo'] ? 'checked' : '' ?>>
            Mostrar en la tienda
        </label>

        <div class="acciones-form">
            <button type="submit" class="btn-primario"><?= $id > 0 ? 'Guardar cambios' : 'Agregar producto' ?></button>
            <a href="index.php" class="btn-secundario">Cancelar</a>
        </div>
    </form>
</main>
</body>
</html>
