<?php
// Fragmento para incluir el logo en login.php y menú
$logo = file_exists('logo_path.txt') ? file_get_contents('logo_path.txt') : 'assets/img/logo_default.png';
echo '<img src="'.htmlspecialchars($logo).'" alt="Logo empresa" style="max-height:80px;">';
