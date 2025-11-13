<?php
/******************************************************
 * Kanban "tipo Trello" en PHP + Bootstrap + JS
 * ----------------------------------------------------
 * ✔️ 1 archivo (drop-in) – solo súbelo a tu hosting
 * ✔️ Persiste en SQLite (kanban.db) — sin configuración
 * ✔️ Listas (columnas) + Tarjetas (cards)
 * ✔️ Drag & Drop para mover tarjetas
 * ✔️ CRUD: crear/editar/eliminar listas y tarjetas
 * ✔️ Comentarios en español para aprender rápido
 *
 * Opcional: puedes cambiar a MySQL abajo en la sección CONFIG
 ******************************************************/

// ==========================
// CONFIGURACIÓN BÁSICA
// ==========================
const APP_NAME = 'Planner Karol Diaz';

// Seguridad simple anti-CSRF (token por sesión)
session_start();
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// ---------- Persistencia con SQLite (por defecto) ----------
$useMySQL = false; // Cambia a true si quieres MySQL

// Datos MySQL (si decides usar MySQL)
$mysql = [
  'host' => 'localhost',
  'db'   => 'mi_base',
  'user' => 'mi_usuario',
  'pass' => 'mi_password',
  'charset' => 'utf8mb4'
];

// Conexión PDO (SQLite por defecto)
function db() {
  static $pdo;
  global $useMySQL, $mysql;
  if ($pdo) return $pdo;

  if ($useMySQL) {
    $dsn = "mysql:host={$mysql['host']};dbname={$mysql['db']};charset={$mysql['charset']}";
    $pdo = new PDO($dsn, $mysql['user'], $mysql['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } else {
    $dsn = 'sqlite:' . __DIR__ . '/kanban.db';
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  return $pdo;
}

// Crear tablas si no existen
function bootstrap_schema() {
  $pdo = db();
  
  // Tabla de tableros (boards)
  $pdo->exec("CREATE TABLE IF NOT EXISTS boards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    descripcion TEXT DEFAULT '',
    color TEXT DEFAULT '#3b82f6',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
  );");
  
  // Tabla de sprints
  $pdo->exec("CREATE TABLE IF NOT EXISTS sprints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    board_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    fecha_inicio TEXT NOT NULL,
    fecha_fin TEXT NOT NULL,
    objetivo TEXT DEFAULT '',
    estado TEXT DEFAULT 'activo',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(board_id) REFERENCES boards(id) ON DELETE CASCADE
  );");
  
  // Tablas: listas y tarjetas
  $pdo->exec("CREATE TABLE IF NOT EXISTS lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    board_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY(board_id) REFERENCES boards(id) ON DELETE CASCADE
  );");

  $pdo->exec("CREATE TABLE IF NOT EXISTS cards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT DEFAULT '',
    position INTEGER NOT NULL DEFAULT 0,
    story_points INTEGER DEFAULT 0,
    asignado_a TEXT DEFAULT '',
    sprint_id INTEGER DEFAULT NULL,
    fecha_entrega TEXT DEFAULT NULL,
    categoria TEXT DEFAULT '',
    es_proyecto_largo INTEGER DEFAULT 0,
    fecha_inicio TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(list_id) REFERENCES lists(id) ON DELETE CASCADE,
    FOREIGN KEY(sprint_id) REFERENCES sprints(id) ON DELETE SET NULL
  );");
  
  // Tabla: actividades/comentarios de tarjetas
  $pdo->exec("CREATE TABLE IF NOT EXISTS card_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_id INTEGER NOT NULL,
    tipo TEXT NOT NULL DEFAULT 'comentario',
    contenido TEXT NOT NULL,
    archivo_nombre TEXT DEFAULT NULL,
    archivo_ruta TEXT DEFAULT NULL,
    archivo_tipo TEXT DEFAULT NULL,
    archivo_tamano INTEGER DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(card_id) REFERENCES cards(id) ON DELETE CASCADE
  );");
  
  // MIGRACIÓN: Agregar columnas si no existen (para bases de datos existentes)
  try {
    // Verificar columnas en cards
    $columns = $pdo->query("PRAGMA table_info(cards)")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');
    
    if (!in_array('story_points', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN story_points INTEGER DEFAULT 0");
    }
    if (!in_array('asignado_a', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN asignado_a TEXT DEFAULT ''");
    }
    if (!in_array('sprint_id', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN sprint_id INTEGER DEFAULT NULL");
    }
    if (!in_array('fecha_entrega', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN fecha_entrega TEXT DEFAULT NULL");
    }
    if (!in_array('categoria', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN categoria TEXT DEFAULT ''");
    }
    if (!in_array('es_proyecto_largo', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN es_proyecto_largo INTEGER DEFAULT 0");
    }
    if (!in_array('fecha_inicio', $columnNames)) {
      $pdo->exec("ALTER TABLE cards ADD COLUMN fecha_inicio TEXT DEFAULT NULL");
    }
    
    // Verificar columnas en lists
    $listColumns = $pdo->query("PRAGMA table_info(lists)")->fetchAll(PDO::FETCH_ASSOC);
    $listColumnNames = array_column($listColumns, 'name');
    
    if (!in_array('board_id', $listColumnNames)) {
      // Crear tablero por defecto si no existe
      $boardCount = $pdo->query("SELECT COUNT(*) FROM boards")->fetchColumn();
      if ($boardCount == 0) {
        $pdo->exec("INSERT INTO boards (nombre, descripcion, color) VALUES ('Mi Tablero', 'Tablero principal', '#3b82f6')");
      }
      $defaultBoardId = $pdo->query("SELECT id FROM boards ORDER BY id ASC LIMIT 1")->fetchColumn();
      
      $pdo->exec("ALTER TABLE lists ADD COLUMN board_id INTEGER DEFAULT $defaultBoardId");
    }
    
    // Verificar columnas en sprints
    $sprintColumns = $pdo->query("PRAGMA table_info(sprints)")->fetchAll(PDO::FETCH_ASSOC);
    $sprintColumnNames = array_column($sprintColumns, 'name');
    
    if (!in_array('board_id', $sprintColumnNames)) {
      $defaultBoardId = $pdo->query("SELECT id FROM boards ORDER BY id ASC LIMIT 1")->fetchColumn();
      $pdo->exec("ALTER TABLE sprints ADD COLUMN board_id INTEGER DEFAULT $defaultBoardId");
    }
    
    // Verificar columnas en card_activities
    $activityColumns = $pdo->query("PRAGMA table_info(card_activities)")->fetchAll(PDO::FETCH_ASSOC);
    $activityColumnNames = array_column($activityColumns, 'name');
    
    if (!in_array('archivo_nombre', $activityColumnNames)) {
      $pdo->exec("ALTER TABLE card_activities ADD COLUMN archivo_nombre TEXT DEFAULT NULL");
    }
    if (!in_array('archivo_ruta', $activityColumnNames)) {
      $pdo->exec("ALTER TABLE card_activities ADD COLUMN archivo_ruta TEXT DEFAULT NULL");
    }
    if (!in_array('archivo_tipo', $activityColumnNames)) {
      $pdo->exec("ALTER TABLE card_activities ADD COLUMN archivo_tipo TEXT DEFAULT NULL");
    }
    if (!in_array('archivo_tamano', $activityColumnNames)) {
      $pdo->exec("ALTER TABLE card_activities ADD COLUMN archivo_tamano INTEGER DEFAULT NULL");
    }
  } catch (Exception $e) {
    error_log("Migración: " . $e->getMessage());
  }
  
  // Crear tablero y sprint inicial si no existen
  $boardCount = $pdo->query("SELECT COUNT(*) FROM boards")->fetchColumn();
  if ($boardCount == 0) {
    $pdo->exec("INSERT INTO boards (nombre, descripcion, color) VALUES ('Mi Tablero', 'Tablero principal', '#3b82f6')");
    $boardId = $pdo->lastInsertId();
    
    // Crear listas predefinidas
    $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'Pendiente', 0)");
    $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'En Progreso', 1)");
    $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'Hecho', 2)");
    
    // Crear sprint inicial
    $inicio = date('Y-m-d');
    $fin = date('Y-m-d', strtotime('+14 days'));
    $pdo->exec("INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) 
                VALUES ($boardId, 'Sprint 1', '$inicio', '$fin', 'Organizar tareas iniciales', 'activo')");
  }
}
bootstrap_schema();

// ==========================
// UTILIDADES
// ==========================
function json_response($ok, $data = [], $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => $ok] + $data);
  exit;
}

function require_csrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf'] ?? (json_decode(file_get_contents('php://input'), true)['csrf'] ?? '');
    if (!$sent || $sent !== ($_SESSION['csrf'] ?? '')) {
      json_response(false, ['msg' => 'CSRF token inválido'], 403);
    }
  }
}

function next_position($table, $where = '', $params = []) {
  $pdo = db();
  $sql = "SELECT COALESCE(MAX(position),-1) + 1 AS np FROM {$table} ";
  if ($where) $sql .= " WHERE $where";
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return (int)($row['np'] ?? 0);
}

// Sanitizar cadenas simples
function s($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// ==========================
// API (AJAX)
// ==========================
$action = $_GET['action'] ?? '';
if ($action) {
  require_csrf();
  try {
    switch ($action) {
      case 'get_board': {
        $pdo = db();
        $board_id = (int)($_GET['board_id'] ?? ($_SESSION['current_board'] ?? 1));
        $_SESSION['current_board'] = $board_id;
        
        $board = $pdo->query("SELECT * FROM boards WHERE id=$board_id")->fetch();
        $boards = $pdo->query('SELECT * FROM boards ORDER BY id ASC')->fetchAll();
        $lists = $pdo->query("SELECT * FROM lists WHERE board_id=$board_id ORDER BY position ASC, id ASC")->fetchAll();
        
        // Obtener tarjetas con conteo de actividades
        $cards = $pdo->query("
          SELECT c.*, 
                 (SELECT COUNT(*) FROM card_activities WHERE card_id = c.id) as activities_count
          FROM cards c 
          INNER JOIN lists l ON c.list_id=l.id 
          WHERE l.board_id=$board_id 
          ORDER BY c.position ASC, c.id ASC
        ")->fetchAll();
        
        $sprints = $pdo->query("SELECT * FROM sprints WHERE board_id=$board_id ORDER BY id DESC")->fetchAll();
        $sprintActivo = $pdo->query("SELECT * FROM sprints WHERE board_id=$board_id AND estado='activo' LIMIT 1")->fetch();
        
        json_response(true, [
          'board' => $board,
          'boards' => $boards,
          'lists' => $lists,
          'cards' => $cards,
          'sprints' => $sprints,
          'sprintActivo' => $sprintActivo,
          'csrf' => $_SESSION['csrf']
        ]);
      }
      case 'add_list': {
        $title = trim($_POST['title'] ?? 'Nueva lista');
        if ($title === '') $title = 'Nueva lista';
        $pos = next_position('lists');
        $pdo = db();
        $st = $pdo->prepare('INSERT INTO lists(title, position) VALUES(?, ?)');
        $st->execute([$title, $pos]);
        json_response(true, ['id' => $pdo->lastInsertId()]);
      }
      case 'rename_list': {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if (!$id || $title === '') json_response(false, ['msg' => 'Datos inválidos'], 400);
        $pdo = db();
        $st = $pdo->prepare('UPDATE lists SET title=? WHERE id=?');
        $st->execute([$title, $id]);
        json_response(true);
      }
      case 'delete_list': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Lista inválida'], 400);
        $pdo = db();
        $pdo->prepare('DELETE FROM cards WHERE list_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM lists WHERE id=?')->execute([$id]);
        json_response(true);
      }
      case 'add_card': {
        $list_id = (int)($_POST['list_id'] ?? 0);
        $title = trim($_POST['title'] ?? 'Nueva tarjeta');
        $desc = trim($_POST['description'] ?? '');
        $points = (int)($_POST['story_points'] ?? 0);
        $asignado = trim($_POST['asignado_a'] ?? '');
        $sprint_id = (int)($_POST['sprint_id'] ?? null);
        $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $es_proyecto_largo = (int)($_POST['es_proyecto_largo'] ?? 0);
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        if (!$list_id) json_response(false, ['msg' => 'Lista inválida'], 400);
        $pos = next_position('cards', 'list_id = ?', [$list_id]);
        $pdo = db();
        $st = $pdo->prepare('INSERT INTO cards(list_id, title, description, position, story_points, asignado_a, sprint_id, fecha_entrega, categoria, es_proyecto_largo, fecha_inicio) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$list_id, $title ?: 'Nueva tarjeta', $desc, $pos, $points, $asignado, $sprint_id, $fecha_entrega, $categoria, $es_proyecto_largo, $fecha_inicio]);
        json_response(true, ['id' => $pdo->lastInsertId()]);
      }
      case 'update_card': {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $points = (int)($_POST['story_points'] ?? 0);
        $asignado = trim($_POST['asignado_a'] ?? '');
        $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $es_proyecto_largo = (int)($_POST['es_proyecto_largo'] ?? 0);
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        if (!$id) json_response(false, ['msg' => 'Tarjeta inválida'], 400);
        $pdo = db();
        $pdo->prepare('UPDATE cards SET title=?, description=?, story_points=?, asignado_a=?, fecha_entrega=?, categoria=?, es_proyecto_largo=?, fecha_inicio=? WHERE id=?')
            ->execute([$title, $desc, $points, $asignado, $fecha_entrega, $categoria, $es_proyecto_largo, $fecha_inicio, $id]);
        json_response(true);
      }
      case 'delete_card': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Tarjeta inválida'], 400);
        db()->prepare('DELETE FROM cards WHERE id=?')->execute([$id]);
        json_response(true);
      }
      case 'add_activity': {
        $card_id = (int)($_POST['card_id'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? 'comentario');
        $contenido = trim($_POST['contenido'] ?? '');
        if (!$card_id || !$contenido) json_response(false, ['msg' => 'Datos incompletos'], 400);
        
        $pdo = db();
        
        // Manejar archivo adjunto si existe
        $archivo_nombre = null;
        $archivo_ruta = null;
        $archivo_tipo = null;
        $archivo_tamano = null;
        
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
          $uploadDir = __DIR__ . '/uploads/';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
          }
          
          $archivo_nombre = basename($_FILES['archivo']['name']);
          $archivo_tipo = $_FILES['archivo']['type'];
          $archivo_tamano = $_FILES['archivo']['size'];
          
          // Generar nombre único para evitar colisiones
          $extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
          $nombreUnico = time() . '_' . uniqid() . '.' . $extension;
          $archivo_ruta = 'uploads/' . $nombreUnico;
          
          if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadDir . $nombreUnico)) {
            json_response(false, ['msg' => 'Error al subir archivo'], 500);
          }
        }
        
        $st = $pdo->prepare('INSERT INTO card_activities(card_id, tipo, contenido, archivo_nombre, archivo_ruta, archivo_tipo, archivo_tamano) VALUES(?,?,?,?,?,?,?)');
        $st->execute([$card_id, $tipo, $contenido, $archivo_nombre, $archivo_ruta, $archivo_tipo, $archivo_tamano]);
        json_response(true, ['id' => $pdo->lastInsertId()]);
      }
      case 'get_activities': {
        $card_id = (int)($_GET['card_id'] ?? 0);
        if (!$card_id) json_response(false, ['msg' => 'Card ID inválido'], 400);
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM card_activities WHERE card_id=? ORDER BY created_at DESC');
        $st->execute([$card_id]);
        json_response(true, ['activities' => $st->fetchAll()]);
      }
      case 'delete_activity': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Actividad inválida'], 400);
        
        $pdo = db();
        // Obtener la actividad para eliminar el archivo físico si existe
        $activity = $pdo->query("SELECT archivo_ruta FROM card_activities WHERE id=$id")->fetch();
        if ($activity && $activity['archivo_ruta']) {
          $filePath = __DIR__ . '/' . $activity['archivo_ruta'];
          if (file_exists($filePath)) {
            unlink($filePath);
          }
        }
        
        $pdo->prepare('DELETE FROM card_activities WHERE id=?')->execute([$id]);
        json_response(true);
      }
      case 'update_activity': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Actividad inválida'], 400);
        
        $pdo = db();
        $updates = [];
        $params = [];
        
        // Si se envió una descripción, actualizarla
        if (isset($_POST['descripcion'])) {
          $updates[] = 'descripcion=?';
          $params[] = $_POST['descripcion'];
        }
        
        // Si se envió un archivo, procesarlo
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
          // Eliminar archivo anterior si existe
          $activity = $pdo->query("SELECT archivo_ruta FROM card_activities WHERE id=$id")->fetch();
          if ($activity && $activity['archivo_ruta']) {
            $oldPath = __DIR__ . '/' . $activity['archivo_ruta'];
            if (file_exists($oldPath)) unlink($oldPath);
          }
          
          // Guardar nuevo archivo
          $uploadDir = __DIR__ . '/uploads/';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
          
          $fileInfo = pathinfo($_FILES['archivo']['name']);
          $uniqueName = time() . '_' . uniqid() . '.' . ($fileInfo['extension'] ?? 'bin');
          $uploadPath = $uploadDir . $uniqueName;
          
          if (move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadPath)) {
            $updates[] = 'archivo_nombre=?';
            $params[] = $_FILES['archivo']['name'];
            $updates[] = 'archivo_ruta=?';
            $params[] = 'uploads/' . $uniqueName;
            $updates[] = 'archivo_tipo=?';
            $params[] = $_FILES['archivo']['type'];
            $updates[] = 'archivo_tamano=?';
            $params[] = $_FILES['archivo']['size'];
          }
        }
        
        if (empty($updates)) json_response(false, ['msg' => 'No hay datos para actualizar'], 400);
        
        $params[] = $id;
        $sql = 'UPDATE card_activities SET ' . implode(', ', $updates) . ' WHERE id=?';
        $pdo->prepare($sql)->execute($params);
        json_response(true);
      }
      case 'move_card': {
        // Mover tarjeta entre listas/posiciones y reordenar
        $id = (int)($_POST['id'] ?? 0);
        $to_list = (int)($_POST['to_list'] ?? 0);
        $to_index = (int)($_POST['to_index'] ?? 0);
        if (!$id || !$to_list) json_response(false, ['msg' => 'Datos inválidos'], 400);
        $pdo = db();
        // Insertar la tarjeta en la posición deseada dentro de la lista destino
        // 1) Traer todas las tarjetas destino ordenadas
        $st = $pdo->prepare('SELECT id FROM cards WHERE list_id=? AND id<>? ORDER BY position ASC, id ASC');
        $st->execute([$to_list, $id]);
        $ids = array_column($st->fetchAll(), 'id');
        array_splice($ids, $to_index, 0, [$id]);
        // 2) Actualizar list_id y positions
        $pdo->prepare('UPDATE cards SET list_id=? WHERE id=?')->execute([$to_list, $id]);
        $upd = $pdo->prepare('UPDATE cards SET position=? WHERE id=?');
        foreach ($ids as $i => $cid) { $upd->execute([$i, $cid]); }
        json_response(true);
      }
      case 'reorder_lists': {
        // Reordenar listas según un array de IDs
        $order = $_POST['order'] ?? [];
        // FormData envía arrays con sufijo []
        if (empty($order) && isset($_POST['order'])) {
          $order = is_array($_POST['order']) ? $_POST['order'] : [$_POST['order']];
        }
        if (!is_array($order) || empty($order)) json_response(false, ['msg' => 'Orden inválido'], 400);
        $pdo = db();
        $st = $pdo->prepare('UPDATE lists SET position=? WHERE id=?');
        foreach ($order as $i => $id) { $st->execute([(int)$i, (int)$id]); }
        json_response(true);
      }
      case 'add_sprint': {
        $nombre = trim($_POST['nombre'] ?? '');
        $inicio = trim($_POST['fecha_inicio'] ?? date('Y-m-d'));
        $fin = trim($_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+14 days')));
        $objetivo = trim($_POST['objetivo'] ?? '');
        $board_id = (int)($_POST['board_id'] ?? $_SESSION['board_activo'] ?? 0);
        
        if (!$nombre) json_response(false, ['msg' => 'Nombre requerido'], 400);
        if (!$board_id) json_response(false, ['msg' => 'Tablero inválido'], 400);
        
        $pdo = db();
        // Marcar otros sprints como completados en este tablero
        $pdo->prepare("UPDATE sprints SET estado='completado' WHERE board_id=? AND estado='activo'")->execute([$board_id]);
        
        // Crear nuevo sprint activo
        $st = $pdo->prepare('INSERT INTO sprints(board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) VALUES(?,?,?,?,?,?)');
        $st->execute([$board_id, $nombre, $inicio, $fin, $objetivo, 'activo']);
        json_response(true, ['id' => $pdo->lastInsertId()]);
      }
      case 'set_sprint_activo': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Sprint inválido'], 400);
        $pdo = db();
        $pdo->exec("UPDATE sprints SET estado='completado' WHERE estado='activo'");
        $pdo->prepare("UPDATE sprints SET estado='activo' WHERE id=?")->execute([$id]);
        json_response(true);
      }
      case 'add_board': {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $color = trim($_POST['color'] ?? '#3b82f6');
        if (!$nombre) json_response(false, ['msg' => 'Nombre requerido'], 400);
        
        $pdo = db();
        $st = $pdo->prepare('INSERT INTO boards(nombre, descripcion, color) VALUES(?,?,?)');
        $st->execute([$nombre, $descripcion, $color]);
        $boardId = $pdo->lastInsertId();
        
        // Crear las 3 listas predefinidas
        $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'Pendiente', 0)");
        $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'En Progreso', 1)");
        $pdo->exec("INSERT INTO lists (board_id, title, position) VALUES ($boardId, 'Hecho', 2)");
        
        // Crear sprint inicial
        $inicio = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+14 days'));
        $pdo->exec("INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) 
                    VALUES ($boardId, 'Sprint 1', '$inicio', '$fin', 'Primer sprint', 'activo')");
        
        json_response(true, ['id' => $boardId]);
      }
      case 'delete_board': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) json_response(false, ['msg' => 'Tablero inválido'], 400);
        $pdo = db();
        // Verificar que no sea el último tablero
        $count = $pdo->query("SELECT COUNT(*) FROM boards")->fetchColumn();
        if ($count <= 1) json_response(false, ['msg' => 'No puedes eliminar el último tablero'], 400);
        
        $pdo->prepare('DELETE FROM boards WHERE id=?')->execute([$id]);
        json_response(true);
      }
    }
  } catch (Throwable $e) {
    json_response(false, ['msg' => $e->getMessage()], 500);
  }
}

// ==========================
// VISTA (HTML + Bootstrap + JS)
// ==========================
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= s(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    
    * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    
    body{ 
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
      background-attachment: fixed;
      margin:0; 
      padding:0;
      min-height: 100vh;
    }
    
    .toolbar{ 
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);
      border-bottom: 1px solid rgba(148, 163, 184, 0.1);
      box-shadow: 0 4px 24px rgba(0,0,0,0.4);
      backdrop-filter: blur(20px);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .sprint-stat{ 
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%);
      padding: 0.5rem 1rem; 
      border-radius: 12px; 
      color: #cbd5e1;
      border: 1px solid rgba(59, 130, 246, 0.3);
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .sprint-stat:hover{ 
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.25) 0%, rgba(99, 102, 241, 0.25) 100%);
      border-color: rgba(59, 130, 246, 0.5);
      transform: translateY(-2px);
    }
    .sprint-stat b{ color: #fff; font-size: 1.05rem; font-weight: 700; }
    
    .kanban{ 
      display: grid; 
      grid-template-columns: repeat(3, 1fr); 
      gap: 1.75rem; 
      height: calc(100vh - 160px);
      padding: 1.5rem;
    }
    
    .list{ 
      color:#e2e8f0; 
      border-radius:16px; 
      padding:18px; 
      box-shadow: 0 10px 30px rgba(0,0,0,.4), 0 0 1px rgba(255,255,255,0.1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.08);
      position: relative;
    }
    
    .list::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      border-radius: 16px 16px 0 0;
    }
    
    .list.pendiente{ 
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%);
      backdrop-filter: blur(20px);
    }
    .list.pendiente::before { background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%); }
    
    .list.en-progreso{ 
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(251, 146, 60, 0.15) 100%);
      backdrop-filter: blur(20px);
    }
    .list.en-progreso::before { background: linear-gradient(90deg, #f59e0b 0%, #fb923c 100%); }
    
    .list.hecho{ 
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
      backdrop-filter: blur(20px);
    }
    .list.hecho::before { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); }
    
    .list-header{ 
      display:flex; 
      align-items:center; 
      justify-content:space-between; 
      gap:.5rem; 
      margin-bottom: 1.2rem;
      padding-top: 0.5rem;
    }
    .list-title{ 
      font-weight:800; 
      font-size:1.15rem; 
      color: #fff; 
      text-shadow: 0 2px 8px rgba(0,0,0,0.4);
      letter-spacing: -0.025em;
    }
    
    .dropzone{ 
      flex: 1; 
      overflow-y: auto; 
      overflow-x: hidden;
      padding-right: 6px;
    }
    .dropzone::-webkit-scrollbar { width: 6px; }
    .dropzone::-webkit-scrollbar-track { background: transparent; }
    .dropzone::-webkit-scrollbar-thumb { 
      background: rgba(255,255,255,0.2); 
      border-radius: 10px;
      transition: background 0.3s;
    }
    .dropzone::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
    
    .card-item{ 
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      color:#1f2937; 
      border: 1px solid rgba(226, 232, 240, 0.8);
      border-radius:12px; 
      padding:14px; 
      margin-bottom:12px; 
      cursor:grab; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.06);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .card-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 3px;
      height: 100%;
      background: linear-gradient(180deg, #3b82f6 0%, #8b5cf6 100%);
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .card-item:hover{ 
      transform: translateY(-3px) scale(1.01); 
      box-shadow: 0 8px 20px rgba(0,0,0,0.15), 0 3px 8px rgba(0,0,0,0.1);
      border-color: rgba(59, 130, 246, 0.3);
    }
    .card-item:hover::before { opacity: 1; }
    .card-item.dragging{ opacity:.6; cursor: grabbing; transform: rotate(2deg); }
    
    .story-points-badge{
      display: inline-block;
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 0.3rem 0.6rem;
      border-radius: 8px;
      min-width: 28px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
    }
    
    .ghost{ 
      border:2px dashed rgba(255,255,255,0.4); 
      background: rgba(255,255,255,0.05);
      border-radius:12px; 
      margin-bottom:12px; 
      height:60px;
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 0.5; }
      50% { opacity: 0.8; }
    }
    
    .btn{ 
      font-weight: 600; 
      border-radius: 10px;
      transition: all 0.3s ease;
      border: none;
    }
    
    .btn-dark-soft{ 
      background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
      border:1px solid #374151; 
      color:#e5e7eb;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .btn-dark-soft:hover{ 
      background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
      color:#fff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    .btn-add-card{ 
      background: rgba(255,255,255,0.15); 
      border: 1px solid rgba(255,255,255,0.2); 
      color: #fff;
      font-size: 0.875rem;
      padding: 0.45rem 0.9rem;
      font-weight: 600;
      border-radius: 10px;
      backdrop-filter: blur(10px);
    }
    .btn-add-card:hover{ 
      background: rgba(255,255,255,0.25); 
      color: #fff; 
      border-color: rgba(255,255,255,0.4);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .btn-primary{
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    .btn-primary:hover{
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
    }
    
    .btn-success{
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    .btn-success:hover{
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5);
    }
    
    .btn-outline-info{
      border: 2px solid #06b6d4;
      color: #06b6d4;
      background: transparent;
    }
    .btn-outline-info:hover{
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
      color: white;
      border-color: #06b6d4;
      transform: translateY(-2px);
    }
    
    .btn-outline-danger{
      border: 2px solid #ef4444;
      color: #ef4444;
      background: transparent;
    }
    .btn-outline-danger:hover{
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      border-color: #ef4444;
      transform: translateY(-2px);
    }
    
    .modal-content{
      border-radius: 16px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    
    .form-control, .form-select{
      border-radius: 10px;
      border: 1px solid #374151;
      transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus{
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    
    .badge{
      font-weight: 600;
      padding: 0.35em 0.65em;
      border-radius: 8px;
    }
    
    .table-dark{
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      overflow: hidden;
    }
    
    .table-dark th{
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
      color: #94a3b8;
      border-bottom: 2px solid #334155;
    }
    
    .table-dark tbody tr{
      border-bottom: 1px solid rgba(51, 65, 85, 0.5);
      transition: all 0.2s ease;
    }
    
    .table-dark tbody tr:hover{
      background: rgba(59, 130, 246, 0.1);
      transform: scale(1.01);
    }
    
    #currentBoardName{
      background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .btn-group .btn{
      font-size: 0.875rem;
      padding: 0.4rem 1rem;
    }
    
    /* Timeline de actividades */
    .activity-item {
      position: relative;
      padding-left: 2rem;
      padding-bottom: 1rem;
      border-left: 2px solid rgba(59, 130, 246, 0.3);
      margin-left: 0.5rem;
    }
    
    .activity-item:last-child {
      border-left-color: transparent;
      padding-bottom: 0;
    }
    
    .activity-item::before {
      content: '';
      position: absolute;
      left: -6px;
      top: 0;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    
    .activity-content {
      background: rgba(30, 41, 59, 0.6);
      padding: 0.75rem;
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      transition: all 0.2s ease;
    }
    
    .activity-content:hover {
      background: rgba(30, 41, 59, 0.8);
      border-color: rgba(59, 130, 246, 0.4);
    }
    
    .activity-content .btn-sm {
      opacity: 0.7;
      transition: all 0.2s ease;
    }
    
    .activity-content:hover .btn-sm {
      opacity: 1;
    }
    
    .activity-time {
      font-size: 0.7rem;
      color: #94a3b8;
      font-weight: 500;
    }
    
    .activity-text {
      color: #e2e8f0;
      font-size: 0.875rem;
      line-height: 1.5;
    }
    
    #activitiesTimeline::-webkit-scrollbar {
      width: 6px;
    }
    
    #activitiesTimeline::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.2);
      border-radius: 10px;
    }
    
    #activitiesTimeline::-webkit-scrollbar-thumb {
      background: rgba(59, 130, 246, 0.4);
      border-radius: 10px;
    }
    
    #activitiesTimeline::-webkit-scrollbar-thumb:hover {
      background: rgba(59, 130, 246, 0.6);
    }
    
    /* Archivos adjuntos */
    .attachment-preview {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.3);
      border-radius: 8px;
      padding: 0.5rem 0.75rem;
      margin-top: 0.5rem;
      transition: all 0.2s ease;
    }
    
    .attachment-preview:hover {
      background: rgba(59, 130, 246, 0.25);
      border-color: rgba(59, 130, 246, 0.5);
      transform: translateX(3px);
    }
    
    .attachment-preview img {
      max-height: 40px;
      border-radius: 4px;
    }
    
    .attachment-icon {
      font-size: 1.5rem;
    }
    
    .attachment-info {
      display: flex;
      flex-direction: column;
    }
    
    .attachment-name {
      font-size: 0.85rem;
      color: #e2e8f0;
      font-weight: 500;
    }
    
    .attachment-size {
      font-size: 0.7rem;
      color: #94a3b8;
    }
    
    /* Modales personalizados */
    .modal-content {
      backdrop-filter: blur(10px);
    }
    
    .modal-header h5 {
      background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    #customPromptInput:focus,
    #newBoardNameInput:focus {
      border-color: #3b82f6 !important;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
    }
  </style>
</head>
<body>

<nav class="toolbar py-3 mb-3">
  <div class="container-fluid">
    <!-- Fila 1: Header principal -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-3">
        <h4 class="m-0" style="color: #e2e8f0; font-weight: 800; font-size: 1.5rem;">
          📋 <?= s(APP_NAME) ?>
        </h4>
        <select id="boardSelector" class="form-select form-select-sm" style="max-width: 220px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.3); color: #e2e8f0; font-weight: 500;">
          <option value="">Cargando tableros...</option>
        </select>
        <button class="btn btn-sm btn-success" id="btnNewBoard">
          ✨ Nuevo Tablero
        </button>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <!-- Toggle Vista -->
        <div class="btn-group shadow-sm" role="group">
          <button class="btn btn-sm btn-primary active" id="btnVistaKanban" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none;">
            📋 Kanban
          </button>
          <button class="btn btn-sm btn-outline-primary" id="btnVistaLista" style="border-color: #3b82f6; color: #3b82f6;">
            � Lista
          </button>
        </div>
        <button class="btn btn-sm btn-dark-soft" id="btnResetDemo">
          🎲 Ejemplos
        </button>
        <button class="btn btn-sm btn-outline-danger" id="btnDeleteBoard">
          🗑️ Eliminar
        </button>
      </div>
    </div>
    
    <!-- Fila 2: Info del tablero y sprint -->
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-4">
        <div>
          <h5 class="m-0" style="font-weight: 700; font-size: 1.3rem;" id="currentBoardName">Cargando...</h5>
          <small style="color: #94a3b8; font-weight: 500;" id="sprintTitle">🏃 Sprint 1</small>
        </div>
        <small style="color: #94a3b8; font-style: italic; font-weight: 500;" id="sprintObjetivo"></small>
        <button class="btn btn-sm btn-outline-info" id="btnNewSprint">
          ⚡ Nuevo Sprint
        </button>
      </div>
      <div class="d-flex gap-3">
        <div class="sprint-stat">
          📅 Días restantes: <b id="diasRestantes">-</b>
        </div>
        <div class="sprint-stat">
          📊 Puntos: <b id="puntosCompletados">0</b>/<b id="puntosTotales">0</b>
        </div>
        <div class="sprint-stat">
          ⚡ Progreso: <b id="porcentaje">0%</b>
        </div>
      </div>
    </div>
  </div>
</nav>

<main class="container-fluid px-3">
  <!-- Vista Kanban -->
  <div id="board" class="kanban"></div>
  
  <!-- Vista Lista -->
  <div id="vistaLista" class="table-responsive" style="display:none;">
    <table class="table table-dark table-striped table-hover">
      <thead>
        <tr>
          <th style="width: 60px;">#ID</th>
          <th>Título</th>
          <th style="width: 150px;">Estado</th>
          <th style="width: 130px;">Categoría</th>
          <th style="width: 80px;">Puntos</th>
          <th style="width: 150px;">Fecha Entrega</th>
          <th style="width: 120px;">Tipo</th>
          <th style="width: 100px;">Acciones</th>
        </tr>
      </thead>
      <tbody id="listaTableBody">
      </tbody>
    </table>
  </div>
</main>

<!-- Modal: Editar tarjeta -->
<div class="modal fade" id="cardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">✏️ Editar Tarea</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <input type="hidden" id="cardId">
        <div class="mb-3">
          <label class="form-label fw-semibold">📝 Título</label>
          <input type="text" id="cardTitle" class="form-control bg-dark text-light border-secondary" placeholder="Título de la tarea" style="border-radius: 10px;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">📄 Descripción</label>
          <textarea id="cardDesc" class="form-control bg-dark text-light border-secondary" rows="3" placeholder="Detalles o notas" style="border-radius: 10px;"></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📂 Categoría/Reunión</label>
            <select id="cardCategoria" class="form-select bg-dark text-light border-secondary" style="border-radius: 10px;">
              <option value="">Sin categoría</option>
              <option value="soporte">🔧 Soporte/Infraestructura (Martes)</option>
              <option value="desarrollo">💻 Desarrollo Software (Viernes)</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">⏱️ Story Points (Complejidad)</label>
            <select id="cardPoints" class="form-select bg-dark text-light border-secondary" style="border-radius: 10px;">
              <option value="0">Sin estimar</option>
              <option value="1">1 - Muy rápida (15 min)</option>
              <option value="2">2 - Rápida (30 min)</option>
              <option value="3">3 - Media (1-2 hrs)</option>
              <option value="5">5 - Compleja (medio día)</option>
              <option value="8">8 - Muy compleja (1 día)</option>
              <option value="13">13 - Épica (varios días)</option>
            </select>
          </div>
        </div>
        
        <!-- Proyecto Largo -->
        <div class="mb-3">
          <div class="form-check" style="background: rgba(139, 92, 246, 0.1); padding: 0.75rem; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.3);">
            <input type="checkbox" class="form-check-input" id="cardProyectoLargo" style="cursor: pointer;">
            <label class="form-check-label fw-semibold" for="cardProyectoLargo" style="cursor: pointer;">
              🚀 Proyecto de largo plazo (más de 2 semanas)
            </label>
          </div>
        </div>
        
        <!-- Fechas Proyecto -->
        <div id="fechasProyecto" class="row mb-3" style="display:none;">
          <div class="col-md-6">
            <label class="form-label fw-semibold">📅 Fecha Inicio</label>
            <input type="date" id="cardFechaInicio" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">🏁 Fecha Entrega</label>
            <input type="date" id="cardFechaEntrega" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
          </div>
        </div>
        
        <!-- Fecha Entrega Normal (para tareas de sprint) -->
        <div id="fechaNormal" class="mb-3">
          <label class="form-label fw-semibold">📅 Fecha Entrega</label>
          <input type="date" id="cardFechaEntregaNormal" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
          <small class="text-secondary">💡 Martes (soporte) o Viernes (desarrollo)</small>
        </div>
        
        <hr style="border-color: rgba(148, 163, 184, 0.2); margin: 1.5rem 0;">
        
        <!-- Sección de Actividades/Bitácora -->
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size: 1.1rem; color: #60a5fa;">📋 Bitácora de Actividades</label>
          <p class="small text-secondary mb-3">Documenta procesos, acciones y adjunta archivos (imágenes, documentos, etc.)</p>
          
          <!-- Agregar nueva actividad -->
          <div class="mb-2">
            <input type="text" id="newActivityInput" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Describe la actividad o proceso realizado..." style="border-radius: 10px;">
            
            <div class="d-flex gap-2 align-items-center">
              <button class="btn btn-success" id="btnAddActivity" style="border-radius: 8px;">
                ➕ Agregar
              </button>
            </div>
          </div>
          
          <!-- Timeline de actividades -->
          <div id="activitiesTimeline" style="max-height: 300px; overflow-y: auto; background: rgba(15, 23, 42, 0.5); border-radius: 10px; padding: 1rem;">
            <p class="text-center text-secondary small">No hay actividades registradas</p>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-danger me-auto" id="btnDeleteCard" style="border-radius: 10px;">🗑️ Eliminar</button>
        <button class="btn btn-primary" id="btnSaveCard" style="border-radius: 10px;">💾 Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Crear Sprint -->
<div class="modal fade" id="sprintModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">🏃 Crear Nuevo Sprint</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <div class="mb-3">
          <label class="form-label fw-semibold">✍️ Nombre del Sprint</label>
          <input type="text" id="sprintNombre" class="form-control bg-dark text-light border-secondary" placeholder="Ej: Sprint 1, Sprint Noviembre" style="border-radius: 10px;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">🎯 Objetivo del Sprint</label>
          <textarea id="sprintObjetivoInput" class="form-control bg-dark text-light border-secondary" rows="2" placeholder="¿Qué quieres lograr en este sprint?" style="border-radius: 10px;"></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📅 Fecha Inicio</label>
            <input type="date" id="sprintFechaInicio" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📅 Fecha Fin</label>
            <input type="date" id="sprintFechaFin" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">⏱️ Duración rápida</label>
          <div class="btn-group w-100" role="group">
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="7" style="border-radius: 10px 0 0 10px;">1 semana</button>
            <button type="button" class="btn btn-outline-info btn-duracion active" data-days="14" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; border-color: #06b6d4;">2 semanas</button>
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="21">3 semanas</button>
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="30" style="border-radius: 0 10px 10px 0;">1 mes</button>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-success" id="btnSaveSprint" style="border-radius: 10px;">✅ Crear Sprint</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Prompt -->
<div class="modal fade" id="customPromptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold" id="customPromptTitle">📝 Ingresa información</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <label class="form-label fw-semibold" id="customPromptLabel">Valor:</label>
        <input type="text" id="customPromptInput" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-primary" id="customPromptConfirm" style="border-radius: 10px;">✅ Aceptar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Confirm -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold" id="customConfirmTitle">⚠️ Confirmar acción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <p id="customConfirmMessage" class="mb-0"></p>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-danger" id="customConfirmYes" style="border-radius: 10px;">✅ Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Nuevo Tablero -->
<div class="modal fade" id="newBoardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">📋 Crear Nuevo Tablero</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <label class="form-label fw-semibold">✍️ Nombre del tablero:</label>
        <input type="text" id="newBoardNameInput" class="form-control bg-dark text-light border-secondary" placeholder="Ej: Proyecto Cliente X" style="border-radius: 10px;">
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-success" id="btnCreateNewBoard" style="border-radius: 10px;">✅ Crear Tablero</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==========================
// Frontend JS – Kanban
// ==========================
const CSRF = <?= json_encode($_SESSION['csrf']) ?>;
const board = document.getElementById('board');
const cardModal = new bootstrap.Modal(document.getElementById('cardModal'));
const sprintModal = new bootstrap.Modal(document.getElementById('sprintModal'));
const elCardId = document.getElementById('cardId');
const elCardTitle = document.getElementById('cardTitle');
const elCardDesc = document.getElementById('cardDesc');
const elCardPoints = document.getElementById('cardPoints');
const elCardCategoria = document.getElementById('cardCategoria');
const elCardProyectoLargo = document.getElementById('cardProyectoLargo');
const elCardFechaInicio = document.getElementById('cardFechaInicio');
const elCardFechaEntrega = document.getElementById('cardFechaEntrega');
const elCardFechaEntregaNormal = document.getElementById('cardFechaEntregaNormal');
const boardSelector = document.getElementById('boardSelector');

// Elementos del modal de sprint
const elSprintNombre = document.getElementById('sprintNombre');
const elSprintObjetivo = document.getElementById('sprintObjetivoInput');
const elSprintFechaInicio = document.getElementById('sprintFechaInicio');
const elSprintFechaFin = document.getElementById('sprintFechaFin');

// Elementos para actividades
const elNewActivityInput = document.getElementById('newActivityInput');
const elActivitiesTimeline = document.getElementById('activitiesTimeline');
const elBtnAddActivity = document.getElementById('btnAddActivity');

// Modales personalizados
const customPromptModal = new bootstrap.Modal(document.getElementById('customPromptModal'));
const customConfirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
const newBoardModal = new bootstrap.Modal(document.getElementById('newBoardModal'));

// Función personalizada de prompt
function customPrompt(title, label, defaultValue = '') {
  return new Promise((resolve) => {
    const modal = document.getElementById('customPromptModal');
    const input = document.getElementById('customPromptInput');
    const titleEl = document.getElementById('customPromptTitle');
    const labelEl = document.getElementById('customPromptLabel');
    const confirmBtn = document.getElementById('customPromptConfirm');
    
    titleEl.textContent = title;
    labelEl.textContent = label;
    input.value = defaultValue;
    
    const handleConfirm = () => {
      const value = input.value.trim();
      customPromptModal.hide();
      resolve(value || null);
      confirmBtn.removeEventListener('click', handleConfirm);
      input.removeEventListener('keypress', handleKeyPress);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleCancel = () => {
      resolve(null);
      confirmBtn.removeEventListener('click', handleConfirm);
      input.removeEventListener('keypress', handleKeyPress);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleKeyPress = (e) => {
      if (e.key === 'Enter') handleConfirm();
    };
    
    confirmBtn.addEventListener('click', handleConfirm);
    input.addEventListener('keypress', handleKeyPress);
    modal.addEventListener('hidden.bs.modal', handleCancel, { once: true });
    
    customPromptModal.show();
    setTimeout(() => input.focus(), 300);
  });
}

// Función personalizada de confirm
function customConfirm(title, message) {
  return new Promise((resolve) => {
    const modal = document.getElementById('customConfirmModal');
    const titleEl = document.getElementById('customConfirmTitle');
    const messageEl = document.getElementById('customConfirmMessage');
    const confirmBtn = document.getElementById('customConfirmYes');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    const handleConfirm = () => {
      customConfirmModal.hide();
      resolve(true);
      confirmBtn.removeEventListener('click', handleConfirm);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleCancel = () => {
      resolve(false);
      confirmBtn.removeEventListener('click', handleConfirm);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    confirmBtn.addEventListener('click', handleConfirm);
    modal.addEventListener('hidden.bs.modal', handleCancel, { once: true });
    
    customConfirmModal.show();
  });
}

// Toggle para mostrar campos de proyecto largo
elCardProyectoLargo.addEventListener('change', function() {
  const esProyectoLargo = this.checked;
  document.getElementById('fechasProyecto').style.display = esProyectoLargo ? 'flex' : 'none';
  document.getElementById('fechaNormal').style.display = esProyectoLargo ? 'none' : 'block';
});

// Botones de duración rápida en modal sprint
document.querySelectorAll('.btn-duracion').forEach(btn => {
  btn.addEventListener('click', function() {
    // Remover active de todos
    document.querySelectorAll('.btn-duracion').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    
    // Calcular fecha fin
    const days = parseInt(this.dataset.days);
    const inicio = new Date(elSprintFechaInicio.value || new Date());
    const fin = new Date(inicio);
    fin.setDate(fin.getDate() + days);
    elSprintFechaFin.value = fin.toISOString().split('T')[0];
  });
});

// Cuando cambia fecha inicio, actualizar fecha fin según duración seleccionada
elSprintFechaInicio.addEventListener('change', function() {
  const btnActive = document.querySelector('.btn-duracion.active');
  if (btnActive) {
    const days = parseInt(btnActive.dataset.days);
    const inicio = new Date(this.value);
    const fin = new Date(inicio);
    fin.setDate(fin.getDate() + days);
    elSprintFechaFin.value = fin.toISOString().split('T')[0];
  }
});

let currentBoard = null;
let currentSprint = null;
let allCards = [];
let allBoards = [];
let vistaActual = 'kanban'; // 'kanban' o 'lista'

// Toggle entre vistas
document.getElementById('btnVistaKanban').addEventListener('click', function() {
  vistaActual = 'kanban';
  document.getElementById('board').style.display = 'grid';
  document.getElementById('vistaLista').style.display = 'none';
  this.classList.add('active');
  this.classList.remove('btn-outline-primary');
  this.classList.add('btn-primary');
  this.style.background = '#3b82f6';
  this.style.borderColor = '#3b82f6';
  document.getElementById('btnVistaLista').classList.remove('active');
  document.getElementById('btnVistaLista').classList.add('btn-outline-primary');
  document.getElementById('btnVistaLista').classList.remove('btn-primary');
  document.getElementById('btnVistaLista').style.background = '';
  document.getElementById('btnVistaLista').style.borderColor = '';
});

document.getElementById('btnVistaLista').addEventListener('click', function() {
  vistaActual = 'lista';
  document.getElementById('board').style.display = 'none';
  document.getElementById('vistaLista').style.display = 'block';
  this.classList.add('active');
  this.classList.remove('btn-outline-primary');
  this.classList.add('btn-primary');
  this.style.background = '#3b82f6';
  this.style.borderColor = '#3b82f6';
  document.getElementById('btnVistaKanban').classList.remove('active');
  document.getElementById('btnVistaKanban').classList.add('btn-outline-primary');
  document.getElementById('btnVistaKanban').classList.remove('btn-primary');
  document.getElementById('btnVistaKanban').style.background = '';
  document.getElementById('btnVistaKanban').style.borderColor = '';
  renderListaView();
});

// Utilidades de fetch (POST con CSRF)
async function api(action, data = {}){
  const form = new FormData();
  Object.entries({ ...data, csrf: CSRF }).forEach(([k,v]) => {
    // Si es un array, agregarlo correctamente
    if (Array.isArray(v)) {
      v.forEach(item => form.append(`${k}[]`, item));
    } else {
      form.append(k, v);
    }
  });
  const res = await fetch(`?action=${encodeURIComponent(action)}`, { method:'POST', body: form });
  const js = await res.json();
  if(!js.ok) throw new Error(js.msg || 'Error API');
  return js;
}

async function load(boardId = null){
  const url = boardId ? `?action=get_board&board_id=${boardId}` : '?action=get_board';
  const res = await fetch(url);
  const js = await res.json();
  if (!js.ok) throw new Error(js.msg || 'Error al cargar');
  
  currentBoard = js.board;
  currentSprint = js.sprintActivo;
  allCards = js.cards;
  allBoards = js.boards;
  
  // Actualizar título del tablero
  document.getElementById('currentBoardName').textContent = js.board?.nombre || 'Sin tablero';
  
  updateBoardSelector(js.boards, js.board?.id);
  render(js.lists, js.cards);
  updateSprintStats(js.cards);
  
  // Si estamos en vista lista, renderizarla también
  if (vistaActual === 'lista') {
    renderListaView();
  }
}

function updateBoardSelector(boards, currentId) {
  boardSelector.innerHTML = '';
  boards.forEach(b => {
    const option = document.createElement('option');
    option.value = b.id;
    option.textContent = b.nombre;
    if (b.id == currentId) option.selected = true;
    boardSelector.appendChild(option);
  });
}

boardSelector.addEventListener('change', () => {
  load(boardSelector.value);
});

function updateSprintStats(cards) {
  if (!currentSprint) {
    document.getElementById('sprintTitle').textContent = '🏃 Sin sprint activo';
    document.getElementById('sprintObjetivo').textContent = '';
    return;
  }
  
  // Actualizar título y objetivo del sprint
  document.getElementById('sprintTitle').textContent = `🏃 ${currentSprint.nombre}`;
  document.getElementById('sprintObjetivo').textContent = currentSprint.objetivo || '';
  
  // Calcular días restantes
  const hoy = new Date();
  const fin = new Date(currentSprint.fecha_fin);
  const diffTime = fin - hoy;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  document.getElementById('diasRestantes').textContent = diffDays >= 0 ? diffDays : '0 (vencido)';
  
  // Calcular puntos
  const hechoListId = document.querySelector('.list.hecho')?.dataset.listId;
  const completados = cards.filter(c => c.list_id == hechoListId).reduce((sum, c) => sum + (c.story_points || 0), 0);
  const totales = cards.reduce((sum, c) => sum + (c.story_points || 0), 0);
  
  document.getElementById('puntosCompletados').textContent = completados;
  document.getElementById('puntosTotales').textContent = totales;
  document.getElementById('porcentaje').textContent = totales > 0 ? Math.round((completados / totales) * 100) + '%' : '0%';
}

function render(lists, cards){
  board.innerHTML = '';
  
  // Listas fijas predefinidas
  const fixedLists = [
    { id: 'pendiente', title: 'Pendiente', cssClass: 'pendiente' },
    { id: 'en-progreso', title: 'En Progreso', cssClass: 'en-progreso' },
    { id: 'hecho', title: 'Hecho', cssClass: 'hecho' }
  ];
  
  const cardsByList = {};
  lists.forEach(l => cardsByList[l.id] = []);
  cards.forEach(c => { (cardsByList[c.list_id] ||= []).push(c); });

  // Asegurar que existan las 3 listas en la BD y obtener sus IDs reales
  fixedLists.forEach((fixedList, index) => {
    const dbList = lists.find(l => l.title === fixedList.title) || lists[index];
    if (!dbList) return;
    
    const col = document.createElement('div');
    col.className = `list ${fixedList.cssClass}`;
    col.dataset.listId = dbList.id;

    col.innerHTML = `
      <div class="list-header">
        <div class="list-title">${escapeHtml(fixedList.title)}</div>
        <button class="btn btn-sm btn-add-card">✨ Tarea</button>
      </div>
      <div class="dropzone"></div>
    `;

    const dz = col.querySelector('.dropzone');

    // Render tarjetas
    (cardsByList[dbList.id]||[]).forEach(card => {
      dz.appendChild(renderCard(card));
    });

    // Drag & drop eventos en zona
    setupDropzone(dz);

    // Evento agregar tarjeta
    col.querySelector('.btn-add-card').addEventListener('click', async () => {
      const title = await customPrompt('📝 Nueva Tarea', 'Título de la tarea:', 'Nueva tarea');
      if (!title) return;
      const sprintId = currentSprint ? currentSprint.id : null;
      await api('add_card', { list_id: dbList.id, title, sprint_id: sprintId });
      load();
    });

    board.appendChild(col);
  });
}

// Renderizar vista de lista/tabla
function renderListaView() {
  const tbody = document.getElementById('listaTableBody');
  tbody.innerHTML = '';
  
  // Obtener lista names
  const lists = {}; 
  document.querySelectorAll('.list').forEach(l => {
    const listId = l.dataset.listId;
    const listTitle = l.querySelector('.list-title')?.textContent || 'Sin estado';
    lists[listId] = listTitle;
  });
  
  // Ordenar tarjetas por ID descendente (más recientes primero)
  const sortedCards = [...allCards].sort((a, b) => b.id - a.id);
  
  sortedCards.forEach(card => {
    const tr = document.createElement('tr');
    
    // Estado de la lista
    const estadoNombre = lists[card.list_id] || 'Sin estado';
    let estadoBadge = '';
    if (estadoNombre === 'Pendiente') {
      estadoBadge = '<span class="badge bg-secondary">⏳ Pendiente</span>';
    } else if (estadoNombre === 'En Progreso') {
      estadoBadge = '<span class="badge bg-primary">🔄 En Progreso</span>';
    } else if (estadoNombre === 'Hecho') {
      estadoBadge = '<span class="badge bg-success">✅ Hecho</span>';
    }
    
    // Categoría
    let categoriaBadge = '<span class="badge bg-dark">Sin categoría</span>';
    if (card.categoria === 'soporte') {
      categoriaBadge = '<span class="badge bg-warning text-dark">🔧 Soporte</span>';
    } else if (card.categoria === 'desarrollo') {
      categoriaBadge = '<span class="badge bg-info text-dark">💻 Desarrollo</span>';
    }
    
    // Tipo (proyecto largo o sprint)
    const tipoBadge = card.es_proyecto_largo == 1 
      ? '<span class="badge" style="background: #8b5cf6;">🚀 Largo</span>'
      : '<span class="badge bg-dark">⚡ Sprint</span>';
    
    // Fecha entrega con alerta
    let fechaHtml = '<span class="text-muted">-</span>';
    if (card.fecha_entrega) {
      const fechaEntrega = new Date(card.fecha_entrega);
      const hoy = new Date();
      const diffDays = Math.ceil((fechaEntrega - hoy) / (1000 * 60 * 60 * 24));
      
      let colorFecha = '#10b981';
      let iconoFecha = '📅';
      if (diffDays < 0) {
        colorFecha = '#ef4444';
        iconoFecha = '🔴';
      } else if (diffDays <= 2) {
        colorFecha = '#f59e0b';
        iconoFecha = '⚠️';
      }
      
      const fechaFormateada = fechaEntrega.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
      
      if (card.es_proyecto_largo == 1 && card.fecha_inicio) {
        const fechaInicio = new Date(card.fecha_inicio);
        const inicioFormateada = fechaInicio.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
        fechaHtml = `<span style="color: ${colorFecha}; font-weight: 600;">${iconoFecha} ${inicioFormateada} → ${fechaFormateada}</span>`;
      } else {
        fechaHtml = `<span style="color: ${colorFecha}; font-weight: 600;">${iconoFecha} ${fechaFormateada}</span>`;
      }
    }
    
    tr.innerHTML = `
      <td><span class="badge bg-secondary">#${card.id}</span></td>
      <td>
        <div class="fw-semibold">${escapeHtml(card.title)}</div>
        ${card.description ? `<small class="text-muted">${escapeHtml(card.description)}</small>` : ''}
      </td>
      <td>${estadoBadge}</td>
      <td>${categoriaBadge}</td>
      <td><span class="badge bg-primary">${card.story_points || 0} pts</span></td>
      <td>${fechaHtml}</td>
      <td>${tipoBadge}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary btn-edit-lista" data-card-id="${card.id}">✏️</button>
        <button class="btn btn-sm btn-outline-danger btn-del-lista" data-card-id="${card.id}">🗑️</button>
      </td>
    `;
    
    tbody.appendChild(tr);
  });
  
  // Event listeners para botones
  document.querySelectorAll('.btn-edit-lista').forEach(btn => {
    btn.addEventListener('click', function() {
      const cardId = parseInt(this.dataset.cardId);
      const card = allCards.find(c => c.id === cardId);
      if (card) openCardModal(card);
    });
  });
  
  document.querySelectorAll('.btn-del-lista').forEach(btn => {
    btn.addEventListener('click', async function() {
      const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
      if (!confirmed) return;
      const cardId = parseInt(this.dataset.cardId);
      await api('delete_card', { id: cardId });
      load();
    });
  });
}

function renderCard(card){
  const el = document.createElement('div');
  el.className = 'card-item';
  el.draggable = true;
  el.dataset.cardId = card.id;
  
  // Formatear fecha y hora
  const createdDate = card.created_at ? new Date(card.created_at) : new Date();
  const formattedDate = createdDate.toLocaleDateString('es-ES', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
  const formattedTime = createdDate.toLocaleTimeString('es-ES', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
  
  // Story points
  const pointsBadge = card.story_points > 0 
    ? `<span class="story-points-badge">${card.story_points} pts</span>` 
    : '';
  
  // Categoría badge
  let categoriaBadge = '';
  if (card.categoria === 'soporte') {
    categoriaBadge = '<span class="badge text-dark" style="font-size: 0.7rem; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(251, 191, 36, 0.3);">🔧 Soporte</span>';
  } else if (card.categoria === 'desarrollo') {
    categoriaBadge = '<span class="badge text-dark" style="font-size: 0.7rem; background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(34, 211, 238, 0.3);">💻 Desarrollo</span>';
  }
  
  // Badge de proyecto largo
  const proyectoLargoBadge = card.es_proyecto_largo == 1 
    ? '<span class="badge text-white" style="font-size: 0.7rem; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(168, 85, 247, 0.3);">🚀 Largo Plazo</span>'
    : '';
  
  // Badge de actividades
  const activitiesBadge = card.activities_count > 0
    ? `<span class="badge text-white" style="font-size: 0.7rem; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(20, 184, 166, 0.3);">📋 ${card.activities_count}</span>`
    : '';
  
  // Fecha de entrega con alertas
  let fechaBadge = '';
  if (card.fecha_entrega) {
    const fechaEntrega = new Date(card.fecha_entrega);
    const hoy = new Date();
    const diffDays = Math.ceil((fechaEntrega - hoy) / (1000 * 60 * 60 * 24));
    
    let colorFecha = '#10b981'; // verde
    let iconoFecha = '📅';
    if (diffDays < 0) {
      colorFecha = '#ef4444'; // rojo (vencida)
      iconoFecha = '🔴';
    } else if (diffDays <= 2) {
      colorFecha = '#f59e0b'; // amarillo (próxima)
      iconoFecha = '⚠️';
    }
    
    const fechaFormateada = fechaEntrega.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
    
    // Si es proyecto largo, mostrar rango de fechas
    if (card.es_proyecto_largo == 1 && card.fecha_inicio) {
      const fechaInicio = new Date(card.fecha_inicio);
      const inicioFormateada = fechaInicio.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
      fechaBadge = `<span style="color: ${colorFecha}; font-size: 0.7rem; font-weight: 600;">${iconoFecha} ${inicioFormateada} → ${fechaFormateada}</span>`;
    } else {
      fechaBadge = `<span style="color: ${colorFecha}; font-size: 0.7rem; font-weight: 600;">${iconoFecha} ${fechaFormateada}</span>`;
    }
  }
  
  el.innerHTML = `
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 0.3rem 0.6rem; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">#${card.id}</span>
        ${pointsBadge}
      </div>
    </div>
    <div class="fw-bold mb-2" style="color: #111827; font-size: 0.95rem; line-height: 1.4;">${escapeHtml(card.title)}</div>
    ${card.description ? `<div class="small mb-2" style="color: #6b7280; line-height: 1.5;">${escapeHtml(card.description)}</div>` : ''}
    <div class="d-flex gap-2 mb-2 flex-wrap">
      ${categoriaBadge}
      ${proyectoLargoBadge}
      ${activitiesBadge}
    </div>
    <div class="d-flex justify-content-between align-items-end gap-2 mt-auto pt-2" style="border-top: 1px solid rgba(226, 232, 240, 0.8);">
      <div class="d-flex flex-column gap-1">
        <small style="color: #9ca3af; font-size: 0.65rem; font-weight: 500;">📅 ${formattedDate} • ${formattedTime}</small>
        ${fechaBadge ? `<div>${fechaBadge}</div>` : ''}
      </div>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary btn-edit" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 6px; border-width: 1.5px;">✏️</button>
        <button class="btn btn-sm btn-outline-danger btn-del" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 6px; border-width: 1.5px;">🗑️</button>
      </div>
    </div>
  `;

  // Drag start/stop
  el.addEventListener('dragstart', e => {
    el.classList.add('dragging');
    e.dataTransfer.setData('text/plain', String(card.id));
    e.dataTransfer.effectAllowed = 'move';
  });
  el.addEventListener('dragend', () => {
    el.classList.remove('dragging');
    // Limpiar todos los ghosts cuando termina el arrastre
    document.querySelectorAll('.ghost').forEach(g => g.remove());
  });

  // Acciones tarjeta
  el.querySelector('.btn-edit').addEventListener('click', () => openCardModal(card));
  el.querySelector('.btn-del').addEventListener('click', async () => {
    const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
    if (!confirmed) return;
    await api('delete_card', { id: card.id });
    load();
  });

  return el;
}

function setupDropzone(dz){
  dz.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    // Remover ghost anterior de cualquier dropzone
    document.querySelectorAll('.ghost').forEach(g => g.remove());
    
    const after = getDragAfterElement(dz, e.clientY);
    const ghost = document.createElement('div');
    ghost.className = 'ghost';
    
    if (after == null) dz.appendChild(ghost); 
    else dz.insertBefore(ghost, after);
  });
  
  dz.addEventListener('drop', async e => {
    e.preventDefault();
    const cardId = parseInt(e.dataTransfer.getData('text/plain'), 10);
    const listId = parseInt(dz.closest('.list').dataset.listId, 10);
    
    // Obtener índice del ghost antes de eliminarlo
    const ghost = dz.querySelector('.ghost');
    const index = ghost ? [...dz.children].indexOf(ghost) : 0;
    
    // Limpiar todos los ghosts
    document.querySelectorAll('.ghost').forEach(g => g.remove());
    
    await api('move_card', { id: cardId, to_list: listId, to_index: index < 0 ? 0 : index });
    load();
  });
  
  dz.addEventListener('dragleave', e => {
    // Solo remover ghost si salimos completamente de la dropzone
    if (!e.relatedTarget || !dz.contains(e.relatedTarget)) {
      const ghost = dz.querySelector('.ghost');
      if (ghost) ghost.remove();
    }
  });
}

function getDragAfterElement(container, y){
  const els = [...container.querySelectorAll('.card-item:not(.dragging)')];
  return els.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height/2;
    if (offset < 0 && offset > closest.offset) return { offset, element: child };
    else return closest;
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function openCardModal(card){
  elCardId.value = card.id;
  elCardTitle.value = card.title;
  elCardDesc.value = card.description || '';
  elCardPoints.value = card.story_points || 0;
  elCardCategoria.value = card.categoria || '';
  
  // Cargar campos de proyecto largo
  const esProyectoLargo = card.es_proyecto_largo == 1;
  elCardProyectoLargo.checked = esProyectoLargo;
  
  if (esProyectoLargo) {
    elCardFechaInicio.value = card.fecha_inicio || '';
    elCardFechaEntrega.value = card.fecha_entrega || '';
    document.getElementById('fechasProyecto').style.display = 'flex';
    document.getElementById('fechaNormal').style.display = 'none';
  } else {
    elCardFechaEntregaNormal.value = card.fecha_entrega || '';
    document.getElementById('fechasProyecto').style.display = 'none';
    document.getElementById('fechaNormal').style.display = 'block';
  }
  
  // Cargar actividades
  loadActivities(card.id);
  
  cardModal.show();
}

// Cargar actividades de una tarjeta
async function loadActivities(cardId) {
  try {
    const res = await fetch(`?action=get_activities&card_id=${cardId}`);
    const data = await res.json();
    
    if (data.ok && data.activities.length > 0) {
      renderActivities(data.activities);
    } else {
      elActivitiesTimeline.innerHTML = '<p class="text-center text-secondary small mb-0">No hay actividades registradas</p>';
    }
  } catch(e) {
    console.error('Error cargando actividades:', e);
  }
}

// Renderizar timeline de actividades
function renderActivities(activities) {
  elActivitiesTimeline.innerHTML = activities.map(act => {
    const fecha = new Date(act.created_at);
    const fechaFormateada = fecha.toLocaleDateString('es-ES', { 
      day: '2-digit', 
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
    
    // Generar preview del archivo adjunto si existe
    let attachmentHtml = '';
    if (act.archivo_nombre) {
      const isImage = act.archivo_tipo?.startsWith('image/');
      const sizeMB = (act.archivo_tamano / 1024 / 1024).toFixed(2);
      const icon = getFileIcon(act.archivo_tipo, act.archivo_nombre);
      
      if (isImage) {
        attachmentHtml = `
          <a href="${act.archivo_ruta}" target="_blank" class="attachment-preview">
            <img src="${act.archivo_ruta}" alt="${act.archivo_nombre}">
            <div class="attachment-info">
              <span class="attachment-name">${escapeHtml(act.archivo_nombre)}</span>
              <span class="attachment-size">${sizeMB} MB</span>
            </div>
          </a>
        `;
      } else {
        attachmentHtml = `
          <a href="${act.archivo_ruta}" target="_blank" download class="attachment-preview">
            <span class="attachment-icon">${icon}</span>
            <div class="attachment-info">
              <span class="attachment-name">${escapeHtml(act.archivo_nombre)}</span>
              <span class="attachment-size">${sizeMB} MB</span>
            </div>
          </a>
        `;
      }
    }
    
    return `
      <div class="activity-item">
        <div class="activity-content">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="activity-time">⏰ ${fechaFormateada}</span>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-secondary" onclick="editActivity(${act.id}, '${escapeHtml(act.contenido).replace(/'/g, "\\'")}')" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">✏️</button>
              ${!act.archivo_nombre ? `<button class="btn btn-sm btn-outline-info" onclick="attachFile(${act.id})" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">📎</button>` : ''}
              <button class="btn btn-sm btn-outline-danger" onclick="deleteActivity(${act.id})" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">🗑️</button>
            </div>
          </div>
          <div class="activity-text">${escapeHtml(act.contenido)}</div>
          ${attachmentHtml}
        </div>
      </div>
    `;
  }).join('');
}

// Obtener icono según tipo de archivo
function getFileIcon(mimeType, fileName) {
  if (!mimeType && !fileName) return '📄';
  
  const ext = fileName?.split('.').pop()?.toLowerCase();
  
  if (mimeType?.startsWith('image/')) return '🖼️';
  if (mimeType?.startsWith('video/')) return '🎥';
  if (mimeType?.startsWith('audio/')) return '🎵';
  if (mimeType?.includes('pdf')) return '📕';
  if (mimeType?.includes('word') || ext === 'doc' || ext === 'docx') return '📘';
  if (mimeType?.includes('excel') || ext === 'xls' || ext === 'xlsx') return '📗';
  if (mimeType?.includes('powerpoint') || ext === 'ppt' || ext === 'pptx') return '📙';
  if (mimeType?.includes('zip') || mimeType?.includes('rar') || ext === 'zip' || ext === 'rar') return '🗜️';
  if (ext === 'txt') return '📝';
  
  return '📄';
}

// Agregar nueva actividad
elBtnAddActivity.addEventListener('click', async () => {
  const contenido = elNewActivityInput.value.trim();
  if (!contenido) return;
  
  const cardId = elCardId.value;
  const res = await api('add_activity', { 
    card_id: cardId, 
    contenido 
  });
  
  if (res.ok) {
    elNewActivityInput.value = '';
    loadActivities(cardId);
  }
});

// Permitir Enter para agregar actividad
elNewActivityInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    elBtnAddActivity.click();
  }
});

// Eliminar actividad
async function deleteActivity(activityId) {
  const confirmed = await customConfirm('🗑️ Eliminar Actividad', '¿Estás seguro de eliminar esta actividad?');
  if (!confirmed) return;
  
  await api('delete_activity', { id: activityId });
  loadActivities(elCardId.value);
}

// Editar descripción de actividad
async function editActivity(activityId, currentText) {
  const newText = await customPrompt('✏️ Editar Actividad', 'Descripción:', currentText);
  if (!newText || newText === currentText) return;
  
  const formData = new FormData();
  formData.append('csrf', CSRF);
  formData.append('id', activityId);
  formData.append('descripcion', newText);
  
  try {
    const res = await fetch('?action=update_activity', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) loadActivities(elCardId.value);
  } catch(e) {
    console.error('Error editando actividad:', e);
  }
}

// Adjuntar archivo a actividad
async function attachFile(activityId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar';
  
  input.onchange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('csrf', CSRF);
    formData.append('id', activityId);
    formData.append('archivo', file);
    
    try {
      const res = await fetch('?action=update_activity', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.ok) loadActivities(elCardId.value);
    } catch(err) {
      console.error('Error adjuntando archivo:', err);
    }
  };
  
  input.click();
}

document.getElementById('btnSaveCard').addEventListener('click', async () => {
  const esProyectoLargo = elCardProyectoLargo.checked;
  const fechaEntrega = esProyectoLargo ? elCardFechaEntrega.value : elCardFechaEntregaNormal.value;
  
  await api('update_card', { 
    id: elCardId.value, 
    title: elCardTitle.value, 
    description: elCardDesc.value,
    story_points: elCardPoints.value,
    fecha_entrega: fechaEntrega,
    categoria: elCardCategoria.value,
    es_proyecto_largo: esProyectoLargo ? 1 : 0,
    fecha_inicio: esProyectoLargo ? elCardFechaInicio.value : '',
    asignado_a: '' // Siempre vacío para uso personal
  });
  cardModal.hide();
  load();
});

document.getElementById('btnDeleteCard').addEventListener('click', async () => {
  const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
  if (!confirmed) return;
  await api('delete_card', { id: elCardId.value });
  cardModal.hide();
  load();
});

// Cargar datos iniciales
load();

// Botón nuevo sprint
document.getElementById('btnNewSprint').addEventListener('click', () => {
  // Inicializar con fecha de hoy y 14 días después
  const hoy = new Date();
  elSprintFechaInicio.value = hoy.toISOString().split('T')[0];
  const fin = new Date(hoy);
  fin.setDate(fin.getDate() + 14);
  elSprintFechaFin.value = fin.toISOString().split('T')[0];
  
  // Limpiar campos
  elSprintNombre.value = '';
  elSprintObjetivo.value = '';
  
  sprintModal.show();
});

// Botón guardar sprint
document.getElementById('btnSaveSprint').addEventListener('click', async () => {
  const nombre = elSprintNombre.value.trim();
  const objetivo = elSprintObjetivo.value.trim();
  const fechaInicio = elSprintFechaInicio.value;
  const fechaFin = elSprintFechaFin.value;
  
  if (!nombre) {
    alert('El nombre del sprint es requerido');
    return;
  }
  
  if (!fechaInicio || !fechaFin) {
    alert('Las fechas de inicio y fin son requeridas');
    return;
  }
  
  if (new Date(fechaFin) <= new Date(fechaInicio)) {
    alert('La fecha de fin debe ser posterior a la fecha de inicio');
    return;
  }
  
  await api('add_sprint', {
    nombre,
    objetivo,
    fecha_inicio: fechaInicio,
    fecha_fin: fechaFin,
    board_id: currentBoard?.id
  });
  
  sprintModal.hide();
  load(); // Recargar para mostrar el nuevo sprint
});

// Botón nuevo tablero - Abrir modal
document.getElementById('btnNewBoard').addEventListener('click', () => {
  document.getElementById('newBoardNameInput').value = '';
  newBoardModal.show();
  setTimeout(() => document.getElementById('newBoardNameInput').focus(), 300);
});

// Permitir Enter en el input de nuevo tablero
document.getElementById('newBoardNameInput').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    document.getElementById('btnCreateNewBoard').click();
  }
});

// Confirmar creación de nuevo tablero
document.getElementById('btnCreateNewBoard').addEventListener('click', async () => {
  const nombre = document.getElementById('newBoardNameInput').value.trim();
  if (!nombre) return;
  
  await api('add_board', { nombre, descripcion: '' });
  newBoardModal.hide();
  load();
});

// Botón eliminar tablero
document.getElementById('btnDeleteBoard').addEventListener('click', async () => {
  if (!currentBoard) return;
  const confirmed = await customConfirm('🗑️ Eliminar Tablero', `¿Estás seguro de eliminar el tablero "${currentBoard.nombre}" y todas sus tareas?`);
  if (!confirmed) return;
  
  try {
    await api('delete_board', { id: currentBoard.id });
    load(); // Cargará el primer tablero disponible
  } catch(e) {
    alert(e.message);
  }
});

// Botón demo: agrega tarjetas de ejemplo
const btnDemo = document.getElementById('btnResetDemo');
btnDemo.addEventListener('click', async () => {
  const confirmed = await customConfirm('📋 Agregar Ejemplos', '¿Deseas agregar tarjetas de ejemplo al tablero?');
  if (!confirmed) return;
  const url = currentBoard ? `?action=get_board&board_id=${currentBoard.id}` : '?action=get_board';
  const res = await fetch(url);
  const js = await res.json();
  const firstList = js.lists[0];
  if (firstList){
    await api('add_card', { list_id: firstList.id, title: 'Revisar documentación', description:'Actualizar manual de usuario' });
    await api('add_card', { list_id: firstList.id, title: 'Planificar sprint', description:'Definir objetivos del próximo sprint' });
  }
  const second = js.lists[1];
  if (second){
    await api('add_card', { list_id: second.id, title: 'Implementar login', description:'Sistema de autenticación' });
  }
  const third = js.lists[2];
  if (third){
    await api('add_card', { list_id: third.id, title: 'Configurar base de datos', description:'SQLite funcionando correctamente' });
  }
  load();
});

// Helpers
function escapeHtml(str){
  return (str||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[m]));
}
</script>
</body>
</html>
