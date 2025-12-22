-- SQL: tabla para presentaciones (e.g., frasco, caja, etc.)
CREATE TABLE IF NOT EXISTS presentations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
