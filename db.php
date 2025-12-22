<?php
// db.php - configuración de la base de datos (usa variables de entorno si están disponibles)
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'creador_web_php_puro';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die('Error de conexión a la base de datos: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
