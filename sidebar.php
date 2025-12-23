<?php
// sidebar.php - Fragmento de menú lateral unificado
if (!isset($current)) {
  $current = basename($_SERVER['SCRIPT_NAME']);
}
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <h2>Mi App</h2>
  </div>
  <nav class="menu">
    <a href="welcome.php" class="<?= ($current === 'welcome.php') ? 'active' : '' ?>">Inicio</a>
    <div class="menu-item <?= in_array($current, ['registro_produccion.php','reporte_produccion.php']) ? 'open' : '' ?>">
      <a href="#" class="menu-parent">Registros</a>
      <div class="submenu">
        <a href="registro_produccion.php" class="<?= ($current === 'registro_produccion.php') ? 'active' : '' ?>">Registro Producción</a>
        <a href="reporte_produccion.php" class="<?= ($current === 'reporte_produccion.php') ? 'active' : '' ?>">Reporte Producción</a>
      </div>
    </div>
    <a href="users.php" class="<?= ($current === 'users.php') ? 'active' : '' ?>">Usuario</a>
    <div class="menu-item <?= in_array($current, ['products.php', 'presentations.php']) ? 'open' : '' ?>">
      <a href="#" class="menu-parent">Producto</a>
      <div class="submenu">
        <a href="products.php" class="<?= ($current === 'products.php') ? 'active' : '' ?>">Producto</a>
        <a href="presentations.php" class="<?= ($current === 'presentations.php') ? 'active' : '' ?>">Presentación</a>
      </div>
    </div>
    <a href="config_empresa.php" class="<?= ($current === 'config_empresa.php') ? 'active' : '' ?>">Configuración Empresa</a>
    <a href="?action=logout">Cerrar sesión</a>
  </nav>
</aside>
