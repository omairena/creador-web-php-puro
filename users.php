<?php
require_once __DIR__ . '/auth.php';
require_login();

// Current script name for menu state
$current = basename($_SERVER['SCRIPT_NAME']);

$user = get_user_by_id($_SESSION['user_id']);
if (!$user) {
    // Usuario no encontrado (raro) — cerrar sesión
    logout();
    header('Location: index.php');
    exit;
}

$errors = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password2'] ?? '';

        if ($new !== $new2) {
            $errors = 'Las contraseñas nuevas no coinciden';
        } else {
            $res = change_user_password($user['id'], $current, $new);
            if ($res['success']) {
                $success = 'Contraseña actualizada correctamente';
            } else {
                $errors = $res['message'] ?? 'Error al cambiar la contraseña';
            }
        }
    } elseif (isset($_POST['update_name'])) {
        $new_name = trim($_POST['name'] ?? '');
        $res = update_user_name($user['id'], $new_name);
        if ($res['success']) {
            $success = 'Nombre actualizado';
            // recargar datos
            $user = get_user_by_id($_SESSION['user_id']);
        } else {
            $errors = $res['message'] ?? 'Error al actualizar el nombre';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Perfil - Usuarios</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="layout">
  <?php include 'sidebar.php'; ?>

  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Perfil</h1>
    </header>

    <section class="content">
      <div class="card">
        <h2>Información del usuario</h2>

        <?php if ($errors): ?>
          <div class="alert"><?=htmlspecialchars($errors)?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <form method="post" action="users.php" style="margin-bottom:20px;">
          <label>Nombre
            <input type="text" name="name" required value="<?=htmlspecialchars($user['name'])?>">
          </label>
          <label>Email
            <input type="email" value="<?=htmlspecialchars($user['email'])?>" disabled>
          </label>
          <p>Miembro desde: <?=htmlspecialchars($user['created_at'])?></p>
          <button type="submit" name="update_name" class="btn">Guardar nombre</button>
        </form>

        <hr style="margin:20px 0;">

        <h3>Cambiar contraseña</h3>
        <form method="post" action="users.php" novalidate>
          <label>Contraseña actual
            <input type="password" name="current_password" required>
          </label>
          <label>Nueva contraseña
            <input type="password" name="new_password" required>
          </label>
          <label>Confirmar nueva contraseña
            <input type="password" name="new_password2" required>
          </label>
          <button type="submit" name="change_password" class="btn">Cambiar contraseña</button>
        </form>
      </div>
    </section>
  </div>

<script src="assets/js/app.js"></script>
</body>
</html>