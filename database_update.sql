USE bella_imagen_db;

-- MIGRACIÓN PARA UNA INSTALACIÓN ANTERIOR.
-- Ejecuta este archivo SOLO si ya existe bella_imagen_db y quieres conservar datos.
-- Si instalas desde cero, ejecuta database.sql y omite este archivo.

-- Vinculación opcional entre una cuenta de usuario y su ficha de especialista.
SET @db_name = DATABASE();
SET @has_column = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='usuarios' AND COLUMN_NAME='especialista_id');
SET @ddl = IF(@has_column=0, 'ALTER TABLE usuarios ADD COLUMN especialista_id INT NULL', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_index = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='usuarios' AND INDEX_NAME='uq_usuario_especialista');
SET @ddl = IF(@has_index=0, 'ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_especialista(especialista_id)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='usuarios' AND CONSTRAINT_NAME='fk_usuario_especialista' AND CONSTRAINT_TYPE='FOREIGN KEY');
SET @ddl = IF(@has_fk=0, 'ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_especialista FOREIGN KEY(especialista_id) REFERENCES especialistas(id)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Tablas auxiliares de inventario y sesiones, por si la instalación original no las tenía.
CREATE TABLE IF NOT EXISTS movimientos_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  tipo ENUM('Entrada','Salida','Ajuste') NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  motivo VARCHAR(200),
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesiones_tratamiento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_tratamiento_id INT NOT NULL,
  especialista_id INT NOT NULL,
  fecha DATETIME NOT NULL,
  observaciones TEXT,
  estado ENUM('Pendiente','Realizada','Cancelada') DEFAULT 'Pendiente',
  FOREIGN KEY(cliente_tratamiento_id) REFERENCES cliente_tratamientos(id),
  FOREIGN KEY(especialista_id) REFERENCES especialistas(id)
) ENGINE=InnoDB;
