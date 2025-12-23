<?php
// register.php - formulario de registro
require_once __DIR__ . '/auth.php';

$errors = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($password !== $password2) {
        $errors = 'Las contraseñas no coinciden';
    } else {
        $res = register_user($name, $email, $password);
        if ($res['success']) {
            $success = true;
        } else {
            $errors = $res['message'] ?? 'Error al registrar';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro - App</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
  <main class="auth-container">
    <div class="card">
      <h1>Registro</h1>
      <?php if ($errors): ?>
        <div class="alert"><?=htmlspecialchars($errors)?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success">Registro exitoso. <a href="index.php">Inicia sesión</a></div>
      <?php else: ?>
      <form method="post" action="register.php" novalidate>
        <label>Nombre
          <input type="text" name="name" required>
        </label>
        <label>Email
          <input type="email" name="email" required>
        </label>
        <label>Contraseña
          <input type="password" name="password" required>
        </label>
        <label>Confirmar contraseña
          <input type="password" name="password2" required>
        </label>
        <button type="submit" class="btn">Registrarse</button>
      </form>
      <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
      <?php endif; ?>
    </div>
  </main>
  <script src="assets/js/app.js"></script>
</body>
<?php include 'footer.php'; ?>
</html>
