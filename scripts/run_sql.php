<?php
// scripts/run_sql.php
// Ejecuta todos los archivos SQL en la carpeta `sql/` usando la conexión de `db.php`.
// Uso (desde la raíz del proyecto): php scripts/run_sql.php

require_once __DIR__ . '/../db.php';

if (php_sapi_name() !== 'cli') {
    echo "Ejecuta este script desde la línea de comandos: php scripts/run_sql.php\n";
    exit(1);
}

$sqlDir = __DIR__ . '/../sql';
$expectedOrder = [
    'create_users_table.sql',
    'create_password_resets_table.sql',
    'create_password_reset_requests_table.sql',
    'create_presentations_table.sql',
    'create_products_table.sql'
];

$files = [];
foreach ($expectedOrder as $f) {
    $path = $sqlDir . '/' . $f;
    if (file_exists($path)) $files[] = $path;
}
// add any other .sql files (not duplicating)
foreach (glob($sqlDir . '/*.sql') as $f) {
    if (!in_array($f, $files)) $files[] = $f;
}

if (count($files) === 0) {
    echo "No SQL files found in $sqlDir\n";
    exit(1);
}

echo "Conectando a la BD...\n";
if (!($mysqli instanceof mysqli)) {
    echo "Error: la conexión de db.php no parece estar disponible. Revisa DB_HOST/DB_USER/DB_PASS/DB_NAME en tu entorno.\n";
    exit(1);
}

foreach ($files as $file) {
    echo "\n--- Ejecutando: " . basename($file) . " ---\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        echo "No se pudo leer $file\n";
        continue;
    }

    if (!$mysqli->multi_query($sql)) {
        echo "Error al ejecutar " . basename($file) . ": " . $mysqli->error . "\n";
        // consumir cualquier resultado pendiente
        while ($mysqli->more_results() && $mysqli->next_result()) {
            if ($res = $mysqli->store_result()) $res->free();
        }
        continue;
    }

    // Consumir resultados para permitir próximas consultas
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    echo "OK: " . basename($file) . "\n";
}

echo "\nProceso terminado. Verifica las tablas en tu base de datos (phpMyAdmin / mysql cli).\n";
