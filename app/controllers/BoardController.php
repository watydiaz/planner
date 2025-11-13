<?php
/**
 * Controlador de Tableros
 */

require_once __DIR__ . '/BaseController.php';

class BoardController extends BaseController {
    private $boardModel;
    
    public function __construct() {
        $this->boardModel = new Board();
    }
    
    /**
     * Obtener todos los tableros
     */
    public function index() {
        $boards = $this->boardModel->getAll();
        $this->jsonResponse(true, ['boards' => $boards]);
    }
    
    /**
     * Obtener un tablero específico con sus listas y tarjetas
     */
    public function show($boardId = null) {
        if (!$boardId) {
            $boardId = (int)$this->get('board_id', 0);
        }
        
        if (!$boardId) {
            // Si no hay ID, obtener el primer tablero
            $boards = $this->boardModel->getAll();
            if (empty($boards)) {
                // Si no hay tableros, crear uno por defecto
                $boardId = $this->boardModel->create('Mi Tablero', 'Tablero principal', '#3b82f6');
                
                // Crear sprint inicial
                $sprintModel = new Sprint();
                $inicio = date('Y-m-d');
                $fin = date('Y-m-d', strtotime('+14 days'));
                $sprintModel->create($boardId, 'Sprint 1', $inicio, $fin, 'Primer sprint', 'activo');
            } else {
                $boardId = $boards[0]['id'];
            }
        }
        
        $board = $this->boardModel->getById($boardId);
        if (!$board) {
            $this->jsonResponse(false, ['msg' => 'Tablero no encontrado'], 404);
            return;
        }
        
        $_SESSION['current_board'] = $boardId;
        
        // Obtener todos los tableros para el selector
        $boards = $this->boardModel->getAll();
        
        // Obtener listas del tablero
        $listModel = new CardList();
        $lists = $listModel->getByBoard($boardId);
        
        // Obtener todas las tarjetas del tablero
        $cardModel = new Card();
        $cards = $cardModel->getByBoard($boardId);
        
        // Obtener sprints del tablero
        $sprintModel = new Sprint();
        $sprints = $sprintModel->getByBoard($boardId);
        $sprintActivo = $sprintModel->getActive($boardId);
        
        $this->jsonResponse(true, [
            'board' => $board,
            'boards' => $boards,
            'lists' => $lists,
            'cards' => $cards,
            'sprints' => $sprints,
            'sprintActivo' => $sprintActivo,
            'csrf' => $_SESSION['csrf']
        ]);
    }
    
    /**
     * Crear un nuevo tablero
     */
    public function create() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $nombre = $this->post('nombre', '');
        $descripcion = $this->post('descripcion', '');
        $color = $this->post('color', '#3b82f6');
        
        if (empty($nombre)) {
            $this->jsonResponse(false, ['msg' => 'El nombre es requerido'], 400);
        }
        
        $boardId = $this->boardModel->create($nombre, $descripcion, $color);
        
        $this->jsonResponse(true, ['board_id' => $boardId, 'msg' => 'Tablero creado']);
    }
    
    /**
     * Actualizar un tablero
     */
    public function update() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        $nombre = $this->post('nombre', '');
        $descripcion = $this->post('descripcion', '');
        $color = $this->post('color', '#3b82f6');
        
        if (!$id || empty($nombre)) {
            $this->jsonResponse(false, ['msg' => 'Datos inválidos'], 400);
        }
        
        $this->boardModel->update($id, $nombre, $descripcion, $color);
        
        $this->jsonResponse(true, ['msg' => 'Tablero actualizado']);
    }
    
    /**
     * Eliminar un tablero
     */
    public function delete() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->boardModel->delete($id);
        
        $this->jsonResponse(true, ['msg' => 'Tablero eliminado']);
    }
    
    /**
     * Obtener estadísticas de un tablero
     */
    public function stats() {
        $boardId = (int)$this->get('board_id', 0);
        
        if (!$boardId) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $stats = $this->boardModel->getStats($boardId);
        
        $this->jsonResponse(true, ['stats' => $stats]);
    }
}
