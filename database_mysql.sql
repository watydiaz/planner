-- ============================================
-- Base de datos para Planificador Kanban
-- Sistema de gestión de tareas con Sprints
-- Compatible con MySQL 5.7+ / MariaDB 10.3+
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
  INDEX idx_fecha_entrega (fecha_entrega),
  INDEX idx_created (created_at)
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
  archivo_tamano BIGINT DEFAULT NULL COMMENT 'Tamaño en bytes',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
  INDEX idx_card (card_id),
  INDEX idx_tipo (tipo),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Tablero por defecto
INSERT INTO boards (nombre, descripcion, color) VALUES 
  ('Mi Tablero Principal', 'Tablero de trabajo general', '#3b82f6');

-- Obtener el ID del tablero recién creado
SET @board_id = LAST_INSERT_ID();

-- Listas predefinidas del Kanban
INSERT INTO lists (board_id, title, position) VALUES 
  (@board_id, 'Por Hacer', 0),
  (@board_id, 'En Progreso', 1),
  (@board_id, 'En Revisión', 2),
  (@board_id, 'Completado', 3);

-- Sprint inicial (2 semanas)
INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) VALUES 
  (
    @board_id, 
    'Sprint 1', 
    CURDATE(), 
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    'Sprint inicial de configuración y primeras tareas',
    'activo'
  );

-- ============================================
-- TARJETAS DE EJEMPLO (OPCIONAL)
-- Comenta estas líneas si no quieres datos de ejemplo
-- ============================================

SET @list_pendiente = (SELECT id FROM lists WHERE board_id = @board_id AND title = 'Por Hacer' LIMIT 1);
SET @list_progreso = (SELECT id FROM lists WHERE board_id = @board_id AND title = 'En Progreso' LIMIT 1);
SET @list_revision = (SELECT id FROM lists WHERE board_id = @board_id AND title = 'En Revisión' LIMIT 1);
SET @list_completado = (SELECT id FROM lists WHERE board_id = @board_id AND title = 'Completado' LIMIT 1);
SET @sprint_id = (SELECT id FROM sprints WHERE board_id = @board_id LIMIT 1);

-- Tarjetas de ejemplo
INSERT INTO cards (list_id, title, description, position, story_points, categoria, sprint_id, fecha_entrega) VALUES
  (
    @list_pendiente, 
    'Configurar entorno de desarrollo', 
    'Instalar dependencias y configurar el ambiente local',
    0, 
    3, 
    'desarrollo',
    @sprint_id,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY)
  ),
  (
    @list_pendiente, 
    'Revisar tickets de soporte pendientes', 
    'Revisar y clasificar los tickets acumulados de la semana',
    1, 
    2, 
    'soporte',
    @sprint_id,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY)
  ),
  (
    @list_progreso, 
    'Implementar nueva funcionalidad', 
    'Desarrollar el módulo de reportes solicitado por el cliente',
    0, 
    5, 
    'desarrollo',
    @sprint_id,
    DATE_ADD(CURDATE(), INTERVAL 10 DAY)
  ),
  (
    @list_revision, 
    'Corregir bug en login', 
    'Error al iniciar sesión con usuarios especiales',
    0, 
    1, 
    'bug',
    @sprint_id,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY)
  ),
  (
    @list_completado, 
    'Reunión de planificación semanal', 
    'Reunión del equipo para definir prioridades',
    0, 
    1, 
    'reunion',
    @sprint_id,
    CURDATE()
  );

-- Actividades de ejemplo en una tarjeta
SET @card_ejemplo = (SELECT id FROM cards WHERE title = 'Implementar nueva funcionalidad' LIMIT 1);

INSERT INTO card_activities (card_id, tipo, contenido) VALUES
  (@card_ejemplo, 'comentario', 'Iniciando análisis de requerimientos'),
  (@card_ejemplo, 'comentario', 'Mockups aprobados por el cliente'),
  (@card_ejemplo, 'comentario', 'Completada la estructura base del módulo');

-- ============================================
-- VISTAS ÚTILES (OPCIONAL)
-- ============================================

-- Vista: Tarjetas con información completa
CREATE OR REPLACE VIEW v_cards_full AS
SELECT 
  c.id,
  c.title,
  c.description,
  c.story_points,
  c.categoria,
  c.fecha_entrega,
  c.es_proyecto_largo,
  c.fecha_inicio,
  c.asignado_a,
  c.position,
  c.created_at,
  l.title AS lista_nombre,
  l.id AS lista_id,
  b.nombre AS tablero_nombre,
  b.id AS tablero_id,
  s.nombre AS sprint_nombre,
  s.id AS sprint_id,
  (SELECT COUNT(*) FROM card_activities WHERE card_id = c.id) AS num_actividades,
  CASE 
    WHEN c.fecha_entrega IS NULL THEN 'sin_fecha'
    WHEN c.fecha_entrega < CURDATE() THEN 'vencida'
    WHEN c.fecha_entrega = CURDATE() THEN 'hoy'
    WHEN c.fecha_entrega <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'urgente'
    ELSE 'normal'
  END AS prioridad
FROM cards c
INNER JOIN lists l ON c.list_id = l.id
INNER JOIN boards b ON l.board_id = b.id
LEFT JOIN sprints s ON c.sprint_id = s.id;

-- Vista: Sprints con estadísticas
CREATE OR REPLACE VIEW v_sprints_stats AS
SELECT 
  s.id,
  s.nombre,
  s.fecha_inicio,
  s.fecha_fin,
  s.objetivo,
  s.estado,
  b.nombre AS tablero_nombre,
  b.id AS tablero_id,
  COUNT(c.id) AS total_tareas,
  SUM(c.story_points) AS total_puntos,
  SUM(CASE WHEN l.title = 'Completado' THEN 1 ELSE 0 END) AS tareas_completadas,
  SUM(CASE WHEN l.title = 'Completado' THEN c.story_points ELSE 0 END) AS puntos_completados,
  DATEDIFF(s.fecha_fin, CURDATE()) AS dias_restantes
FROM sprints s
INNER JOIN boards b ON s.board_id = b.id
LEFT JOIN cards c ON c.sprint_id = s.id
LEFT JOIN lists l ON c.list_id = l.id
GROUP BY s.id, s.nombre, s.fecha_inicio, s.fecha_fin, s.objetivo, s.estado, b.nombre, b.id;

-- Vista: Tableros con estadísticas
CREATE OR REPLACE VIEW v_boards_stats AS
SELECT 
  b.id,
  b.nombre,
  b.descripcion,
  b.color,
  b.created_at,
  COUNT(DISTINCT l.id) AS num_listas,
  COUNT(DISTINCT c.id) AS num_tareas,
  COUNT(DISTINCT s.id) AS num_sprints,
  COUNT(DISTINCT CASE WHEN s.estado = 'activo' THEN s.id END) AS sprints_activos
FROM boards b
LEFT JOIN lists l ON l.board_id = b.id
LEFT JOIN cards c ON c.list_id = l.id
LEFT JOIN sprints s ON s.board_id = b.id
GROUP BY b.id, b.nombre, b.descripcion, b.color, b.created_at;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX idx_cards_list_sprint ON cards(list_id, sprint_id);
CREATE INDEX idx_cards_categoria_fecha ON cards(categoria, fecha_entrega);

-- ============================================
-- TRIGGERS ÚTILES
-- ============================================

-- Trigger: Actualizar timestamp al modificar tarjeta
DELIMITER //
CREATE TRIGGER trg_cards_update_timestamp
BEFORE UPDATE ON cards
FOR EACH ROW
BEGIN
  SET NEW.updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- ============================================

-- Procedimiento: Completar sprint y crear uno nuevo
DELIMITER //
CREATE PROCEDURE sp_completar_sprint(
  IN p_sprint_id INT,
  IN p_nuevo_nombre VARCHAR(255),
  IN p_nueva_fecha_inicio DATE,
  IN p_nueva_fecha_fin DATE,
  IN p_nuevo_objetivo TEXT
)
BEGIN
  DECLARE v_board_id INT;
  
  -- Obtener board_id del sprint actual
  SELECT board_id INTO v_board_id FROM sprints WHERE id = p_sprint_id;
  
  -- Completar sprint actual
  UPDATE sprints SET estado = 'completado' WHERE id = p_sprint_id;
  
  -- Crear nuevo sprint
  INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado)
  VALUES (v_board_id, p_nuevo_nombre, p_nueva_fecha_inicio, p_nueva_fecha_fin, p_nuevo_objetivo, 'activo');
  
  SELECT LAST_INSERT_ID() AS nuevo_sprint_id;
END//
DELIMITER ;

-- Procedimiento: Mover tarjeta entre listas
DELIMITER //
CREATE PROCEDURE sp_mover_tarjeta(
  IN p_card_id INT,
  IN p_nueva_lista_id INT,
  IN p_nueva_posicion INT
)
BEGIN
  -- Actualizar posición de las tarjetas afectadas en la lista destino
  UPDATE cards 
  SET position = position + 1 
  WHERE list_id = p_nueva_lista_id 
    AND position >= p_nueva_posicion;
  
  -- Mover la tarjeta
  UPDATE cards 
  SET list_id = p_nueva_lista_id, 
      position = p_nueva_posicion 
  WHERE id = p_card_id;
  
  -- Registrar actividad
  INSERT INTO card_activities (card_id, tipo, contenido)
  VALUES (p_card_id, 'movimiento', CONCAT('Tarjeta movida a nueva lista'));
END//
DELIMITER ;

-- Procedimiento: Obtener resumen del tablero
DELIMITER //
CREATE PROCEDURE sp_resumen_tablero(IN p_board_id INT)
BEGIN
  SELECT 
    l.title AS lista,
    COUNT(c.id) AS num_tareas,
    SUM(c.story_points) AS puntos_totales,
    SUM(CASE WHEN c.categoria = 'bug' THEN 1 ELSE 0 END) AS bugs,
    SUM(CASE WHEN c.fecha_entrega < CURDATE() THEN 1 ELSE 0 END) AS vencidas
  FROM lists l
  LEFT JOIN cards c ON c.list_id = l.id
  WHERE l.board_id = p_board_id
  GROUP BY l.id, l.title, l.position
  ORDER BY l.position;
END//
DELIMITER ;

-- ============================================
-- INFORMACIÓN Y VERIFICACIÓN
-- ============================================

-- Mostrar resumen de la base de datos creada
SELECT 'Base de datos creada exitosamente' AS mensaje;

SELECT 
  (SELECT COUNT(*) FROM boards) AS tableros,
  (SELECT COUNT(*) FROM sprints) AS sprints,
  (SELECT COUNT(*) FROM lists) AS listas,
  (SELECT COUNT(*) FROM cards) AS tarjetas,
  (SELECT COUNT(*) FROM card_activities) AS actividades;

-- ============================================
-- NOTAS DE USO
-- ============================================
/*

INSTRUCCIONES DE USO:

1. Copia todo este archivo SQL
2. Abre phpMyAdmin
3. Ve a la pestaña "SQL"
4. Pega este código completo
5. Haz clic en "Ejecutar"

CONFIGURACIÓN EN PHP:

Modifica el archivo kanban_trello_like.php:

$useMySQL = true;
$mysql = [
  'host' => 'localhost',
  'db'   => 'planificador_kanban',
  'user' => 'tu_usuario',
  'pass' => 'tu_password',
  'charset' => 'utf8mb4'
];

VISTAS DISPONIBLES:
- v_cards_full: Información completa de tarjetas
- v_sprints_stats: Estadísticas de sprints
- v_boards_stats: Estadísticas de tableros

PROCEDIMIENTOS ALMACENADOS:
- sp_completar_sprint(): Completar sprint y crear uno nuevo
- sp_mover_tarjeta(): Mover tarjeta entre listas
- sp_resumen_tablero(): Obtener resumen de un tablero

CONSULTAS ÚTILES:

-- Ver todas las tarjetas con prioridad
SELECT * FROM v_cards_full ORDER BY prioridad DESC, fecha_entrega ASC;

-- Ver sprint activo con estadísticas
SELECT * FROM v_sprints_stats WHERE estado = 'activo';

-- Ver tarjetas vencidas
SELECT * FROM v_cards_full WHERE prioridad = 'vencida';

-- Tareas por categoría
SELECT categoria, COUNT(*) as total 
FROM cards 
WHERE categoria != '' 
GROUP BY categoria;

*/
