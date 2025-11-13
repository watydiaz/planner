

<?php
/**
 * Punto de entrada principal de la aplicación Kanban
 * Maneja enrutamiento, sesiones y carga de controladores
 */

// Mostrar errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================
// CONFIGURACIÓN INICIAL
// ==========================

// Iniciar sesión
session_start();

// Cargar configuración
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoload.php';

// Generar CSRF token si no existe
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Inicializar tablero actual
if (!isset($_SESSION['current_board'])) {
    $_SESSION['current_board'] = 1;
}

// ==========================
// ENRUTAMIENTO API
// ==========================

$action = $_GET['action'] ?? '';

if ($action) {
    // Validar CSRF para todas las peticiones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Leer input una sola vez y guardarlo para uso posterior
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true);
        
        // Guardar en global para que los controladores puedan acceder
        $GLOBALS['request_input'] = $inputData;
        
        $sent = $_POST['csrf'] ?? ($inputData['csrf'] ?? '');
        if (!$sent || $sent !== $_SESSION['csrf']) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'CSRF token inválido']);
            exit;
        }
    }

    try {
        // Enrutar a controladores
        switch ($action) {
            // Board endpoints
            case 'get_board':
                $controller = new BoardController();
                $boardId = (int)($_GET['board_id'] ?? $_SESSION['current_board'] ?? 1);
                $controller->show($boardId);
                break;

            case 'add_board':
                $controller = new BoardController();
                $controller->create();
                break;

            case 'delete_board':
                $controller = new BoardController();
                $controller->delete();
                break;

            // Sprint endpoints
            case 'add_sprint':
                $controller = new SprintController();
                $controller->create();
                break;

            case 'set_sprint_activo':
                $controller = new SprintController();
                $controller->complete();
                break;

            // Card endpoints
            case 'add_card':
                $controller = new CardController();
                $controller->create();
                break;

            case 'update_card':
                $controller = new CardController();
                $controller->update();
                break;

            case 'delete_card':
                $controller = new CardController();
                $controller->delete();
                break;

            case 'move_card':
                $controller = new CardController();
                $controller->move();
                break;

            // Activity endpoints
            case 'get_activities':
                $controller = new ActivityController();
                $controller->index();
                break;

            case 'add_activity':
                $controller = new ActivityController();
                $controller->create();
                break;

            case 'update_activity':
                $controller = new ActivityController();
                $controller->update();
                break;

            case 'delete_activity':
                $controller = new ActivityController();
                $controller->delete();
                break;

            // AI endpoints
            case 'ai_generar_descripcion':
                $controller = new AIController();
                $controller->generarDescripcion();
                break;

            case 'ai_estimar_complejidad':
                $controller = new AIController();
                $controller->estimarComplejidad();
                break;

            case 'ai_generar_subtareas':
                $controller = new AIController();
                $controller->generarSubtareas();
                break;

            case 'ai_sugerir_categoria':
                $controller = new AIController();
                $controller->sugerirCategoria();
                break;

            case 'ai_analizar_carga':
                $controller = new AIController();
                $controller->analizarCarga();
                break;

            case 'ai_chat':
                $controller = new AIController();
                $controller->chatAsistente();
                break;

            default:
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'msg' => 'Acción no encontrada']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// ==========================
// RENDERIZAR VISTA
// ==========================

// Si no es una petición API, mostrar la interfaz principal
$csrf = $_SESSION['csrf'];
$pageTitle = 'Planificador Kanban';

// Incluir el layout principal
include __DIR__ . '/app/views/layouts/main.php';
