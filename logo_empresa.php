<?php
// logo_empresa.php
// Devuelve la ruta del logo actual o un valor por defecto
$logo = file_exists('logo_path.txt') ? file_get_contents('logo_path.txt') : 'assets/img/logo_default.png';
echo $logo;
