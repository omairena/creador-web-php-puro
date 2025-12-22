<?php
// index.php - formulario de login (actualizado para incluir "Olvidé mi contraseña")
require_once __DIR__ . '/auth.php';

$errors = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = login_user($email, $password);
    if ($res['success']) {
        header('Location: welcome.php');
        exit;
    } else {
        $errors = $res['message'] ?? 'Error al iniciar sesión';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - App</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
  <main class="auth-container">
    <div class="card">
      <h1>Iniciar sesión</h1>
      <?php if ($errors): ?>
        <div class="alert"><?=htmlspecialchars($errors)?></div>
      <?php endif; ?>
      <form method="post" action="index.php" novalidate>
        <label>Email
          <input type="email" name="email" required>
        </label>
        <label>Contraseña
          <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn">Entrar</button>
      </form>
      <p><a href="password_reset_request.php">¿Olvidaste tu contraseña?</a></p>
      <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    </div>
  </main>
  <script src="assets/js/app.js"></script>
</body>
</html>
