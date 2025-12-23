<?php
// config_empresa.php - Configuración de datos de la empresa
session_start();
require_once __DIR__ . '/auth.php';
require_login();

// Ruta donde se guardan los datos y el logo
$dataFile = __DIR__ . '/empresa.json';
$logoDir = 'assets/img/';
$logoFile = '';

// Cargar datos actuales
$empresa = [
    'nombre' => '',
    'telefono' => '',
    'correo' => '',
    'direccion' => '',
    'logo' => ''
];
if (file_exists($dataFile)) {
    $empresa = json_decode(file_get_contents($dataFile), true);
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa['nombre'] = $_POST['nombre'] ?? '';
    $empresa['telefono'] = $_POST['telefono'] ?? '';
    $empresa['correo'] = $_POST['correo'] ?? '';
    $empresa['direccion'] = $_POST['direccion'] ?? '';
    // Procesar logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $logoFile = $logoDir . 'logo_empresa.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $logoFile);
            $empresa['logo'] = $logoFile;
            // Actualizar logo_path.txt para el sistema
            file_put_contents('logo_path.txt', $logoFile);
        } else {
            $mensaje = 'Formato de imagen no permitido.';
        }
    }
    file_put_contents($dataFile, json_encode($empresa, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    if (!$mensaje) $mensaje = 'Datos guardados correctamente.';
}
?>
<?php $current = 'config_empresa.php'; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configuración Empresa</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .empresa-form { max-width: 420px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 16px #0001; padding: 28px 22px; }
    .empresa-form label { font-weight: 500; display:block; margin-top: 12px; }
    .empresa-form input, .empresa-form textarea { width:100%; padding:7px 9px; border-radius:5px; border:1px solid #ccc; margin-top:3px; }
    .empresa-form button { margin-top: 18px; width:100%; }
    .empresa-logo-preview { display:block; margin:10px auto 0 auto; max-width:120px; max-height:90px; }
    .empresa-form .alert { background:#eaf7ff; color:#0a3a5a; border-radius:5px; padding:8px 10px; margin-bottom:10px; text-align:center; }
  </style>
</head>
<body class="layout">
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Configuración de empresa</h1>
    </header>
    <section class="content">
      <form class="empresa-form" method="post" enctype="multipart/form-data">
        <h2 style="text-align:center;">Datos de la empresa</h2>
        <?php if ($mensaje): ?><div class="alert"><?=htmlspecialchars($mensaje)?></div><?php endif; ?>
        <label>Nombre
          <input type="text" name="nombre" value="<?=htmlspecialchars($empresa['nombre'])?>" required>
        </label>
        <label>Teléfono
          <input type="text" name="telefono" value="<?=htmlspecialchars($empresa['telefono'])?>">
        </label>
        <label>Correo
          <input type="email" name="correo" value="<?=htmlspecialchars($empresa['correo'])?>">
        </label>
        <label>Dirección
          <textarea name="direccion" rows="2"><?=htmlspecialchars($empresa['direccion'])?></textarea>
        </label>
        <label>Logo
          <input type="file" name="logo" accept="image/*">
        </label>
        <?php if (!empty($empresa['logo']) && file_exists($empresa['logo'])): ?>
          <img src="<?=htmlspecialchars($empresa['logo'])?>" alt="Logo actual" class="empresa-logo-preview">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </form>
    </section>
  </div>
  <script src="assets/js/app.js"></script>
  <?php include 'footer.php'; ?>
</body>
</html>
