-- SQL: tabla para tokens de eliminación de registro de producción
CREATE TABLE IF NOT EXISTS delete_produccion_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registro_id INT UNSIGNED NOT NULL,
  code VARCHAR(50) NOT NULL,
  justification TEXT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (registro_id),
  FOREIGN KEY (registro_id) REFERENCES registros_produccion(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
