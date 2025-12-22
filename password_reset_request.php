<?php
require_once __DIR__ . '/auth.php';
$errors = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = 'Introduce un email válido';
    } else {
        $res = create_password_reset($email);
        if ($res['success']) {
            $message = $res['message'] ?? 'Si el email existe, recibirás instrucciones para resetear la contraseña.';
        } else {
            $errors = $res['message'] ?? 'Error al procesar la solicitud';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Restablecer contraseña - App</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
  <main class="auth-container">
    <div class="card">
      <h1>¿Olvidaste tu contraseña?</h1>
      <?php if ($errors): ?>
        <div class="alert"><?=htmlspecialchars($errors)?></div>
      <?php endif; ?>
      <?php if ($message): ?>
        <div class="success"><?=htmlspecialchars($message)?></div>
      <?php else: ?>
      <form method="post" action="password_reset_request.php" novalidate>
        <label>Email
          <input type="email" name="email" required>
        </label>
        <button type="submit" class="btn">Enviar instrucciones</button>
      </form>
      <?php endif; ?>
      <p><a href="index.php">Volver al inicio</a></p>
    </div>
  </main>
</body>
</html>
