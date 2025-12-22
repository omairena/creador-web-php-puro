<?php
// cleanup_password_resets.php - borrar tokens expirados
require_once __DIR__ . '/db.php';

// Borrar tokens expirados
$stmt = $mysqli->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

// Borrar requests muy antiguas (ej. 30 días)
$stmt = $mysqli->prepare("DELETE FROM password_reset_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$deleted2 = $stmt->affected_rows;
$stmt->close();

echo "Deleted expired tokens: $deleted, old requests: $deleted2\n";
?>
