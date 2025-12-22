<?php
require_once __DIR__ . '/auth.php';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$errors = '';
$success = false;

if (!$token) {
    $errors = 'Token no proporcionado';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if ($password !== $password2) {
            $errors = 'Las contraseñas no coinciden';
        } else {
            $res = reset_password_with_token($token, $password);
            if ($res['success']) {
                $success = true;
            } else {
                $errors = $res['message'] ?? 'Error al resetear la contraseña';
            }
        }
    } else {
        $valid = verify_password_reset_token($token);
        if (!$valid) {
            $errors = 'Token inválido o expirado';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cambiar contraseña - App</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
  <main class="auth-container">
    <div class="card">
      <h1>Cambiar contraseña</h1>
      <?php if ($errors): ?>
        <div class="alert"><?=htmlspecialchars($errors)?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success">Contraseña actualizada. <a href="index.php">Inicia sesión</a></div>
      <?php elseif (!$errors): ?>
      <form method="post" action="password_reset.php" novalidate>
        <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
        <label>Nueva contraseña
          <input type="password" name="password" required>
        </label>
        <label>Confirmar contraseña
          <input type="password" name="password2" required>
        </label>
        <button type="submit" class="btn">Cambiar contraseña</button>
      </form>
      <?php endif; ?>
      <p><a href="index.php">Volver al inicio</a></p>
    </div>
  </main>
</body>
</html>
