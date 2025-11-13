-- ============================================
-- CREAR BASE DE DATOS Y TABLAS
-- Planificador Kanban con Sprints
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS planificador_kanban 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE planificador_kanban;

-- ============================================
-- TABLA: boards (Tableros)
-- ============================================
CREATE TABLE IF NOT EXISTS boards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  color VARCHAR(50) DEFAULT '#3b82f6',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: sprints (Sprints/Iteraciones)
-- ============================================
CREATE TABLE IF NOT EXISTS sprints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  board_id INT NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  objetivo TEXT DEFAULT NULL,
  estado ENUM('activo', 'completado', 'cancelado') DEFAULT 'activo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
  INDEX idx_board (board_id),
  INDEX idx_estado (estado),
  INDEX idx_fechas (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: lists (Listas/Columnas del Kanban)
-- ============================================
CREATE TABLE IF NOT EXISTS lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  board_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
  INDEX idx_board (board_id),
  INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: cards (Tarjetas/Tareas)
-- ============================================
CREATE TABLE IF NOT EXISTS cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  list_id INT NOT NULL,
  title VARCHAR(500) NOT NULL,
  description TEXT DEFAULT NULL,
  position INT NOT NULL DEFAULT 0,
  story_points INT DEFAULT 0,
  asignado_a VARCHAR(255) DEFAULT NULL,
  sprint_id INT DEFAULT NULL,
  fecha_entrega DATE DEFAULT NULL,
  categoria ENUM('soporte', 'desarrollo', 'reunion', 'bug', '') DEFAULT '',
  es_proyecto_largo TINYINT(1) DEFAULT 0,
  fecha_inicio DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
  FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE SET NULL,
  INDEX idx_list (list_id),
  INDEX idx_sprint (sprint_id),
  INDEX idx_position (position),
  INDEX idx_categoria (categoria),
  INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: card_activities (Bitácora/Actividades)
-- ============================================
CREATE TABLE IF NOT EXISTS card_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  card_id INT NOT NULL,
  tipo VARCHAR(50) NOT NULL DEFAULT 'comentario',
  contenido TEXT NOT NULL,
  archivo_nombre VARCHAR(500) DEFAULT NULL,
  archivo_ruta VARCHAR(1000) DEFAULT NULL,
  archivo_tipo VARCHAR(100) DEFAULT NULL,
  archivo_tamano BIGINT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
  INDEX idx_card (card_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Tablero por defecto
INSERT INTO boards (nombre, descripcion, color) VALUES 
  ('Mi Tablero Principal', 'Tablero de trabajo general', '#3b82f6');

-- Obtener el ID del tablero
SET @board_id = LAST_INSERT_ID();

-- Listas predefinidas
INSERT INTO lists (board_id, title, position) VALUES 
  (@board_id, 'Por Hacer', 0),
  (@board_id, 'En Progreso', 1),
  (@board_id, 'En Revisión', 2),
  (@board_id, 'Completado', 3);

-- Sprint inicial
INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) VALUES 
  (
    @board_id, 
    'Sprint 1', 
    CURDATE(), 
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    'Sprint inicial de configuración',
    'activo'
  );

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_cards_list_sprint ON cards(list_id, sprint_id);
CREATE INDEX idx_cards_list_position ON cards(list_id, position);
CREATE INDEX idx_cards_sprint_categoria ON cards(sprint_id, categoria);
CREATE INDEX idx_cards_fecha_categoria ON cards(fecha_entrega, categoria);

-- Índices para búsquedas de texto
CREATE INDEX idx_cards_title ON cards(title);
CREATE INDEX idx_boards_nombre ON boards(nombre);
CREATE INDEX idx_sprints_nombre ON sprints(nombre);

-- Índices para ordenamiento
CREATE INDEX idx_lists_board_position ON lists(board_id, position);
CREATE INDEX idx_activities_card_created ON card_activities(card_id, created_at DESC);

-- Índices para filtros comunes
CREATE INDEX idx_sprints_board_estado ON sprints(board_id, estado);
CREATE INDEX idx_sprints_board_fechas ON sprints(board_id, fecha_inicio, fecha_fin);
CREATE INDEX idx_cards_proyecto_largo ON cards(es_proyecto_largo, fecha_inicio);

-- Índices para archivos adjuntos
CREATE INDEX idx_activities_archivo ON card_activities(card_id, archivo_nombre);

-- ============================================
-- VERIFICACIÓN
-- ============================================
SELECT 'Base de datos creada exitosamente' AS mensaje;

SELECT 
  (SELECT COUNT(*) FROM boards) AS tableros,
  (SELECT COUNT(*) FROM sprints) AS sprints,
  (SELECT COUNT(*) FROM lists) AS listas,
  (SELECT COUNT(*) FROM cards) AS tarjetas,
  (SELECT COUNT(*) FROM card_activities) AS actividades;

