<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products_model.php';
require_login();

// Current script for menu state
$current = basename($_SERVER['SCRIPT_NAME']);

$errors = '';
$success = '';
$editing = false;
$presentation = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_presentation'])) {
        $name = $_POST['name'] ?? '';
        $res = create_presentation($name);
        if ($res['success']) {
            $success = 'Presentación creada';
        } else {
            $errors = $res['message'] ?? 'Error al crear presentación';
        }
    } elseif (isset($_POST['update_presentation'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? '';
        $res = update_presentation($id, $name);
        if ($res['success']) {
            $success = 'Presentación actualizada';
        } else {
            $errors = $res['message'] ?? 'Error al actualizar';
        }
    }
}

if (isset($_GET['edit'])) {
    $editing = true;
    $presentation = get_presentation_by_id(intval($_GET['edit']));
    if (!$presentation) {
        $errors = 'Presentación no encontrada';
        $editing = false;
    }
}

$presentations = get_presentations();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Presentaciones</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="layout">
  <?php include 'sidebar.php'; ?>

  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Presentaciones</h1>
    </header>

    <section class="content">
      <div class="card">
        <?php if ($errors): ?>
          <div class="alert"><?=htmlspecialchars($errors)?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <?php if ($editing && $presentation): ?>
          <h2>Editar presentación</h2>
          <form method="post" action="presentations.php">
            <input type="hidden" name="id" value="<?=htmlspecialchars($presentation['id'])?>">
            <label>Nombre
              <input type="text" name="name" required value="<?=htmlspecialchars($presentation['name'])?>">
            </label>
            <button type="submit" name="update_presentation" class="btn">Guardar</button>
            <a href="presentations.php">Cancelar</a>
          </form>
        <?php else: ?>
          <h2>Nueva presentación</h2>
          <form method="post" action="presentations.php">
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
            <?php foreach ($presentations as $p): ?>
              <li>
                <?=htmlspecialchars($p['name'])?> — <a href="presentations.php?edit=<?=htmlspecialchars($p['id'])?>">Editar</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      </div>
    </section>
  </div>

<script src="assets/js/app.js"></script>
</body>
</html>