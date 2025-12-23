<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products_model.php';
require_login();

// Current script for menu state
$current = basename($_SERVER['SCRIPT_NAME']);

$errors = '';
$success = '';
// Tabs: 'products' or 'presentations'
$tab = $_GET['tab'] ?? ($_POST['tab'] ?? 'products');

// Editing flags
$editing_product = false;
$editing_presentation = false;
$product = null;
$presentation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Presentations CRUD
    if (isset($_POST['create_presentation'])) {
        $name = $_POST['name'] ?? '';
        $res = create_presentation($name);
        if ($res['success']) {
            $success = 'Presentación creada';
            $tab = 'presentations';
        } else {
            $errors = $res['message'] ?? 'Error al crear presentación';
            $tab = 'presentations';
        }
    } elseif (isset($_POST['update_presentation'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? '';
        $res = update_presentation($id, $name);
        if ($res['success']) {
            $success = 'Presentación actualizada';
            $tab = 'presentations';
        } else {
            $errors = $res['message'] ?? 'Error al actualizar presentación';
            $tab = 'presentations';
        }
    }

    // Products CRUD
    if (isset($_POST['create_product'])) {
        $name = $_POST['name'] ?? '';
        $presentation_id = $_POST['presentation_id'] ?? null;
        $vida_util = $_POST['vida_util'] ?? null;
        $res = create_product($name, $presentation_id, $vida_util);
        if ($res['success']) {
            $success = 'Producto creado con código: ' . htmlspecialchars($res['code']);
            $tab = 'products';
        } else {
            $errors = $res['message'] ?? 'Error al crear producto';
            $tab = 'products';
        }
    } elseif (isset($_POST['update_product'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? '';
        $presentation_id = $_POST['presentation_id'] ?? null;
        $vida_util = $_POST['vida_util'] ?? null;
        $res = update_product($id, $name, $presentation_id, $vida_util);
        if ($res['success']) {
            $success = 'Producto actualizado';
            $tab = 'products';
        } else {
            $errors = $res['message'] ?? 'Error al actualizar producto';
            $tab = 'products';
        }
    }
}

// Edit query params
if (isset($_GET['edit'])) {
    $editing_product = true;
    $product = get_product_by_id(intval($_GET['edit']));
    if (!$product) {
        $errors = 'Producto no encontrado';
        $editing_product = false;
    } else {
        $tab = 'products';
    }
}
if (isset($_GET['edit_presentation'])) {
    $editing_presentation = true;
    $presentation = get_presentation_by_id(intval($_GET['edit_presentation']));
    if (!$presentation) {
        $errors = 'Presentación no encontrada';
        $editing_presentation = false;
    } else {
        $tab = 'presentations';
    }
}

$products = get_products();
$presentations = get_presentations();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Productos</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="layout">
  <?php include 'sidebar.php'; ?>

  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Productos</h1>
    </header>

    <section class="content">
      <div class="card">
        <?php if ($errors): ?>
          <div class="alert"><?=htmlspecialchars($errors)?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <div class="tabs" style="margin-bottom:16px;">
          <a href="products.php?tab=products" class="<?= ($tab === 'products') ? 'active' : '' ?>">Productos</a>
          <a href="products.php?tab=presentations" class="<?= ($tab === 'presentations') ? 'active' : '' ?>">Presentaciones</a>
        </div>

        <?php if ($tab === 'presentations'): ?>

          <?php if ($editing_presentation && $presentation): ?>
            <h2>Editar presentación</h2>
            <form method="post" action="products.php">
              <input type="hidden" name="id" value="<?=htmlspecialchars($presentation['id'])?>">
              <input type="hidden" name="tab" value="presentations">
              <label>Nombre
                <input type="text" name="name" required value="<?=htmlspecialchars($presentation['name'])?>">
              </label>
              <button type="submit" name="update_presentation" class="btn">Guardar</button>
              <a href="products.php?tab=presentations">Cancelar</a>
            </form>
          <?php else: ?>
            <h2>Nueva presentación</h2>
            <form method="post" action="products.php">
              <input type="hidden" name="tab" value="presentations">
              <label>Nombre
                <input type="text" name="name" required>
              </label>
              <button type="submit" name="create_presentation" class="btn">Crear</button>
            </form>
          <?php endif; ?>

          <hr style="margin:20px 0;">

          <h3>Presentaciones existentes</h3>
          <?php if (count($presentations) === 0): ?>
            <p>No hay presentaciones aún.</p>
          <?php else: ?>
            <ul>
              <?php foreach ($presentations as $pr): ?>
                <li><?=htmlspecialchars($pr['name'])?> — <a href="products.php?tab=presentations&edit_presentation=<?=htmlspecialchars($pr['id'])?>">Editar</a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        <?php else: ?>

          <?php if ($editing_product && $product): ?>
            <h2>Editar producto</h2>
            <form method="post" action="products.php">
              <input type="hidden" name="id" value="<?=htmlspecialchars($product['id'])?>">
              <label>Nombre
                <input type="text" name="name" required value="<?=htmlspecialchars($product['name'])?>">
              </label>
              <label>Presentación
                <select name="presentation_id">
                  <option value="">-- Ninguna --</option>
                  <?php foreach ($presentations as $pr): ?>
                    <option value="<?=htmlspecialchars($pr['id'])?>" <?=($product['presentation_id']==$pr['id'])? 'selected':''?>><?=htmlspecialchars($pr['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Vida útil (meses)
                <input type="number" min="0" name="vida_util" value="<?=htmlspecialchars($product['vida_util'])?>">
              </label>
              <button type="submit" name="update_product" class="btn">Guardar</button>
              <a href="products.php">Cancelar</a>
            </form>
          <?php else: ?>
            <h2>Nuevo producto</h2>
            <form method="post" action="products.php">
              <label>Nombre
                <input type="text" name="name" required>
              </label>
              <label>Presentación
                <select name="presentation_id">
                  <option value="">-- Ninguna --</option>
                  <?php foreach ($presentations as $pr): ?>
                    <option value="<?=htmlspecialchars($pr['id'])?>"><?=htmlspecialchars($pr['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Vida útil (meses)
                <input type="number" min="0" name="vida_util">
              </label>
              <button type="submit" name="create_product" class="btn">Crear producto</button>
            </form>
          <?php endif; ?>

        <?php endif; ?>

        <hr style="margin:20px 0;">

        <h3>Productos existentes</h3>
        <?php if (count($products) === 0): ?>
          <p>No hay productos aún.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Presentación</th>
                <th>Vida útil (meses)</th>
                <th>Creado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td><?=htmlspecialchars($p['code'])?></td>
                  <td><?=htmlspecialchars($p['name'])?></td>
                  <td><?=htmlspecialchars($p['presentation_name'])?></td>
                  <td><?=htmlspecialchars($p['vida_util'])?></td>
                  <td><?=htmlspecialchars($p['created_at'])?></td>
                  <td><a href="products.php?edit=<?=htmlspecialchars($p['id'])?>">Editar</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </div>
    </section>
  </div>

<script src="assets/js/app.js"></script>
</body>
</html>