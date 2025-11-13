<?php
/**
 * Controlador de Actividades
 */

require_once __DIR__ . '/BaseController.php';

class ActivityController extends BaseController {
    private $activityModel;
    
    public function __construct() {
        $this->activityModel = new Activity();
    }
    
    /**
     * Obtener actividades de una tarjeta
     */
    public function index() {
        $cardId = (int)$this->get('card_id', 0);
        
        if (!$cardId) {
            $this->jsonResponse(false, ['msg' => 'Card ID requerido'], 400);
        }
        
        $activities = $this->activityModel->getByCard($cardId);
        
        $this->jsonResponse(true, ['activities' => $activities]);
    }
    
    /**
     * Crear una nueva actividad
     */
    public function create() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $cardId = (int)$this->post('card_id', 0);
        $contenido = $this->post('contenido', '');
        $tipo = $this->post('tipo', 'comentario');
        
        if (!$cardId || empty($contenido)) {
            $this->jsonResponse(false, ['msg' => 'Datos incompletos'], 400);
        }
        
        // Procesar archivo si existe
        $fileData = null;
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $fileData = $_FILES['archivo'];
        }
        
        $activityId = $this->activityModel->create($cardId, $contenido, $tipo, $fileData);
        
        $this->jsonResponse(true, ['activity_id' => $activityId, 'msg' => 'Actividad creada']);
    }
    
    /**
     * Actualizar una actividad
     */
    public function update() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $contenido = $this->post('descripcion', null); // Viene como 'descripcion' del frontend
        
        // Procesar archivo si existe
        $fileData = null;
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $fileData = $_FILES['archivo'];
        }
        
        if ($contenido === null && $fileData === null) {
            $this->jsonResponse(false, ['msg' => 'No hay datos para actualizar'], 400);
        }
        
        $this->activityModel->update($id, $contenido, $fileData);
        
        $this->jsonResponse(true, ['msg' => 'Actividad actualizada']);
    }
    
    /**
     * Eliminar una actividad
     */
    public function delete() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->activityModel->delete($id);
        
        $this->jsonResponse(true, ['msg' => 'Actividad eliminada']);
    }
    
    /**
     * Obtener actividades con archivos
     */
    public function withFiles() {
        $cardId = (int)$this->get('card_id', 0);
        
        if (!$cardId) {
            $this->jsonResponse(false, ['msg' => 'Card ID requerido'], 400);
        }
        
        $activities = $this->activityModel->getWithFiles($cardId);
        
        $this->jsonResponse(true, ['activities' => $activities]);
    }
}
