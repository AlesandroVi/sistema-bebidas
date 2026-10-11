<?php
session_start();
require_once 'includes/db.php';

$conn = conectar();

// Obtener categorías disponibles
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre");

$mensaje = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $categoria_id = filter_var($_POST['categoria_id'] ?? '', FILTER_VALIDATE_INT);
    $emoji = trim($_POST['emoji'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($nombre) > 150) {
        $errores[] = 'El nombre no puede tener más de 150 caracteres.';
    }
    if (mb_strlen($emoji) > 10) {
        $errores[] = 'El emoji no puede tener más de 10 caracteres.';
    }
    if ($precio === false || $precio < 0) {
        $errores[] = 'El precio debe ser un número válido mayor o igual a 0.';
    }
    if ($stock === false || $stock < 0) {
        $errores[] = 'El stock debe ser un número entero mayor o igual a 0.';
    }
    if ($categoria_id === false || $categoria_id <= 0) {
        $errores[] = 'Seleccioná una categoría válida.';
    }

    if (empty($errores)) {
        // Sin emoji, se usa uno genérico para que la tarjeta no quede vacía
        if ($emoji === '') {
            $emoji = '🥤';
        }
        $stmt = $conn->prepare(
            "INSERT INTO bebidas (nombre, precio, stock, categoria_id, emoji, activo) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sdiisi', $nombre, $precio, $stock, $categoria_id, $emoji, $activo);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: index.php?mensaje=producto_agregado');
            exit;
        }
        $errores[] = 'No se pudo guardar el producto. Intentá de nuevo.';
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar producto — Bebidas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="header">
    <div class="header-inner">
        <a href="index.php" class="btn-volver">← Volver al catálogo</a>
        <div class="header-logo">
            <span class="logo-icon">🍺</span>
            <span class="logo-text">Agregar producto</span>
        </div>
        <div style="width:140px;"></div>
    </div>
</header>

<main class="container container-chico">
    <h1 class="titulo-pagina">Nuevo producto</h1>

    <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="agregar_producto.php" method="POST" class="formulario-producto">
        <label>
            Nombre
            <input type="text" name="nombre" maxlength="150" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
        </label>

        <label>
            Precio
            <input type="number" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>" required>
        </label>

        <label>
            Stock
            <input type="number" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" required>
        </label>

        <label>
            Categoría
            <select name="categoria_id" required>
                <option value="">Seleccioná una categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria['id'] ?>"
                        <?= isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Emoji
            <input type="text" name="emoji" maxlength="10" value="<?= htmlspecialchars($_POST['emoji'] ?? '') ?>" placeholder="Opcional (ej: 🥤)">
        </label>

        <label class="label-checkbox">
            <input type="checkbox" name="activo" value="1" <?= $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['activo']) ? 'checked' : '' ?>>
            Producto activo
        </label>

        <button type="submit" class="btn-primario">Guardar producto</button>
    </form>
</main>
</body>
</html>
