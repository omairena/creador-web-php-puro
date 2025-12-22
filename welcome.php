<?php
// welcome.php - página protegida con menú lateral
require_once __DIR__ . '/auth.php';
require_login();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bienvenido - App</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <h2>Mi App</h2>
    </div>
    <nav class="menu">
      <a href="welcome.php" class="active">Inicio</a>
      <a href="#">Registros</a>
      <a href="#">Usuarios</a>
      <a href="?action=logout">Cerrar sesión</a>
    </nav>
  </aside>

  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <h1>Bienvenido, <?=htmlspecialchars($_SESSION['user_name'])?></h1>
    </header>

    <section class="content">
      <div class="card">
        <h2>Panel de inicio</h2>
        <p>Esta es la página de bienvenida. Desde aquí podrás ir a manejar registros, ver usuarios y más.</p>
        <p>Diseñada para verse bien en tablets de 10&quot; (pantallas intermedias).</p>
      </div>
    </section>
  </div>

<script src="assets/js/app.js"></script>
</body>
</html>
