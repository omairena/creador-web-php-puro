<?php
// config_footer.php
// Página para editar el footer y subir el logo de la empresa
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

// Procesar formulario
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $footer = $_POST['footer'] ?? '';
    $logo = $_FILES['logo'] ?? null;
    // Guardar footer en archivo o base de datos (usaremos archivo para simplicidad)
    file_put_contents('footer.html', $footer);
    // Subir logo si se seleccionó
    if ($logo && $logo['tmp_name']) {
        $ext = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            move_uploaded_file($logo['tmp_name'], 'assets/img/logo_empresa.'.$ext);
            file_put_contents('logo_path.txt', 'assets/img/logo_empresa.'.$ext);
            $msg = 'Configuración guardada correctamente.';
        } else {
            $msg = 'Formato de logo no permitido.';
        }
    } else {
        $msg = 'Configuración guardada correctamente.';
    }
}
// Leer valores actuales
$footer_actual = file_exists('footer.html') ? file_get_contents('footer.html') : '';
$logo_actual = file_exists('logo_path.txt') ? file_get_contents('logo_path.txt') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Footer y Logo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <h2>Configuración de Footer y Logo</h2>
    <?php if ($msg) echo '<p style="color:green">'.$msg.'</p>'; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Texto del footer:</label><br>
        <textarea name="footer" rows="4" cols="60"><?php echo htmlspecialchars($footer_actual); ?></textarea><br><br>
        <label>Logo de la empresa:</label><br>
        <?php if ($logo_actual): ?>
            <img src="<?php echo $logo_actual; ?>" alt="Logo actual" style="max-height:80px;"><br>
        <?php endif; ?>
        <input type="file" name="logo" accept="image/*"><br><br>
        <button type="submit">Guardar</button>
    </form>
    <a href="index.php">Volver al menú</a>
</body>
</html>
