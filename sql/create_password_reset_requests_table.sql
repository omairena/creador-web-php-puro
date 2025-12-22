-- SQL: tabla para registrar solicitudes de reseteo (rate-limiting)
CREATE TABLE IF NOT EXISTS password_reset_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
