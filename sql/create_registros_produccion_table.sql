-- Tabla para registros de producción
CREATE TABLE IF NOT EXISTS registros_produccion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  cantidad INT NOT NULL,
  lote VARCHAR(32) NOT NULL,
  vencimiento DATE NOT NULL,
  responsable_id INT UNSIGNED NOT NULL,
  observaciones TEXT,
  deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME DEFAULT NULL,
  deleted_by INT UNSIGNED DEFAULT NULL,
  justificacion_borrado TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;