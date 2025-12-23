<?php
// welcome.php - página protegida con menú lateral
require_once __DIR__ . '/auth.php';
require_login();

// Current script name for menu state
$current = basename($_SERVER['SCRIPT_NAME']);

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
  <style>
    html, body.layout {
      height: 100%;
      min-height: 100vh;
    }
    body.layout {
      display: flex;
      flex-direction: row;
      min-height: 100vh;
      height: 100vh;
    }
    .main {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      height: 100vh;
      flex: 1;
      margin-left: 260px;
      padding-bottom: 0;
    }
    .content {
      flex: 1 1 auto;
    }
    footer {
      margin-top: auto;
      width: 100%;
      text-align: center;
      padding: 10px 0;
      background: #f2f2f2;
    }
    @media (max-width: 1000px) {
      .main { margin-left: 0; }
    }
  </style>
</head>
<body class="layout">
    <?php include 'sidebar.php'; ?>
  </aside>

  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Bienvenido, <?=htmlspecialchars($_SESSION['user_name'])?></h1>
    </header>
    <div style="text-align:center;margin-bottom:20px;">
      <?php include 'logo_fragment.php'; ?>
    </div>
    <section class="content">
      <div class="card">
        <h2>Panel de inicio</h2>
        <p>Esta es la página de bienvenida. Desde aquí podrás ir a manejar registros, ver usuarios y más.</p>
        <p>Diseñada para verse bien en tablets de 10&quot; (pantallas intermedias).</p>
      </div>
    </section>
    <?php include 'footer.php'; ?>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>