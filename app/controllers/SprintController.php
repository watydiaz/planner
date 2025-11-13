<?php
/**
 * Controlador de Sprints
 */

require_once __DIR__ . '/BaseController.php';

class SprintController extends BaseController {
    private $sprintModel;
    
    public function __construct() {
        $this->sprintModel = new Sprint();
    }
    
    /**
     * Obtener sprints de un tablero
     */
    public function index() {
        $boardId = (int)$this->get('board_id', 0);
        
        if (!$boardId) {
            $this->jsonResponse(false, ['msg' => 'Board ID requerido'], 400);
        }
        
        $sprints = $this->sprintModel->getByBoard($boardId);
        
        $this->jsonResponse(true, ['sprints' => $sprints]);
    }
    
    /**
     * Obtener sprint activo
     */
    public function active() {
        $boardId = (int)$this->get('board_id', 0);
        
        if (!$boardId) {
            $this->jsonResponse(false, ['msg' => 'Board ID requerido'], 400);
        }
        
        $sprint = $this->sprintModel->getActive($boardId);
        
        $this->jsonResponse(true, ['sprint' => $sprint]);
    }
    
    /**
     * Crear un nuevo sprint
     */
    public function create() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $boardId = (int)$this->post('board_id', 0);
        $nombre = $this->post('nombre', '');
        $fechaInicio = $this->post('fecha_inicio', '');
        $fechaFin = $this->post('fecha_fin', '');
        $objetivo = $this->post('objetivo', '');
        
        if (!$boardId || empty($nombre) || empty($fechaInicio) || empty($fechaFin)) {
            $this->jsonResponse(false, ['msg' => 'Datos incompletos'], 400);
        }
        
        $sprintId = $this->sprintModel->create($boardId, $nombre, $fechaInicio, $fechaFin, $objetivo);
        
        $this->jsonResponse(true, ['sprint_id' => $sprintId, 'msg' => 'Sprint creado']);
    }
    
    /**
     * Actualizar un sprint
     */
    public function update() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        $nombre = $this->post('nombre', '');
        $fechaInicio = $this->post('fecha_inicio', '');
        $fechaFin = $this->post('fecha_fin', '');
        $objetivo = $this->post('objetivo', '');
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->sprintModel->update($id, $nombre, $fechaInicio, $fechaFin, $objetivo);
        
        $this->jsonResponse(true, ['msg' => 'Sprint actualizado']);
    }
    
    /**
     * Completar un sprint
     */
    public function complete() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->sprintModel->complete($id);
        
        $this->jsonResponse(true, ['msg' => 'Sprint completado']);
    }
    
    /**
     * Eliminar un sprint
     */
    public function delete() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->sprintModel->delete($id);
        
        $this->jsonResponse(true, ['msg' => 'Sprint eliminado']);
    }
    
    /**
     * Obtener estadísticas de un sprint
     */
    public function stats() {
        $sprintId = (int)$this->get('sprint_id', 0);
        
        if (!$sprintId) {
            $this->jsonResponse(false, ['msg' => 'Sprint ID requerido'], 400);
        }
        
        $stats = $this->sprintModel->getStats($sprintId);
        
        $this->jsonResponse(true, ['stats' => $stats]);
    }
}
