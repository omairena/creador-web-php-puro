-- SQL: tabla para productos
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) DEFAULT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  presentation_id INT UNSIGNED DEFAULT NULL,
  vida_util INT DEFAULT NULL, -- meses de vida útil
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (presentation_id),
  FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
