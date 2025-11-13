<?php
/**
 * Controlador de Tarjetas
 */

require_once __DIR__ . '/BaseController.php';

class CardController extends BaseController {
    private $cardModel;
    
    public function __construct() {
        $this->cardModel = new Card();
    }
    
    /**
     * Obtener tarjetas de una lista
     */
    public function index() {
        $listId = (int)$this->get('list_id', 0);
        
        if (!$listId) {
            $this->jsonResponse(false, ['msg' => 'List ID requerido'], 400);
        }
        
        $cards = $this->cardModel->getByList($listId);
        
        $this->jsonResponse(true, ['cards' => $cards]);
    }
    
    /**
     * Obtener una tarjeta específica
     */
    public function show() {
        $id = (int)$this->get('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $card = $this->cardModel->getById($id);
        
        if (!$card) {
            $this->jsonResponse(false, ['msg' => 'Tarjeta no encontrada'], 404);
        }
        
        $this->jsonResponse(true, ['card' => $card]);
    }
    
    /**
     * Crear una nueva tarjeta
     */
    public function create() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $listId = (int)$this->post('list_id', 0);
        $title = $this->post('title', '');
        
        if (!$listId || empty($title)) {
            $this->jsonResponse(false, ['msg' => 'Datos incompletos'], 400);
        }
        
        $sprintId = $this->post('sprint_id');
        if ($sprintId === 'null' || $sprintId === '' || $sprintId === null) {
            $sprintId = null;
        } else {
            $sprintId = (int)$sprintId;
        }
        
        $fechaEntrega = $this->post('fecha_entrega');
        $fechaEntrega = ($fechaEntrega === '' || $fechaEntrega === null) ? null : $fechaEntrega;
        
        $fechaInicio = $this->post('fecha_inicio');
        $fechaInicio = ($fechaInicio === '' || $fechaInicio === null) ? null : $fechaInicio;
        
        $data = [
            'list_id' => $listId,
            'title' => $title,
            'description' => $this->post('description', ''),
            'story_points' => (int)$this->post('story_points', 0),
            'asignado_a' => $this->post('asignado_a', ''),
            'sprint_id' => $sprintId,
            'fecha_entrega' => $fechaEntrega,
            'categoria' => $this->post('categoria', ''),
            'es_proyecto_largo' => (int)$this->post('es_proyecto_largo', 0),
            'fecha_inicio' => $fechaInicio,
        ];
        
        $cardId = $this->cardModel->create($data);
        
        $this->jsonResponse(true, ['card_id' => $cardId, 'msg' => 'Tarjeta creada']);
    }
    
    /**
     * Actualizar una tarjeta
     */
    public function update() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $data = [];
        
        // Solo actualizar campos que vienen en el POST
        $fields = [
            'title', 'description', 'story_points', 'asignado_a',
            'sprint_id', 'fecha_entrega', 'categoria', 
            'es_proyecto_largo', 'fecha_inicio'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                // Manejar campos de fecha: convertir cadenas vacías a NULL
                if (in_array($field, ['fecha_entrega', 'fecha_inicio'])) {
                    $data[$field] = ($value === '' || $value === null) ? null : $value;
                }
                // Manejar sprint_id: convertir "null" o vacío a NULL
                elseif ($field === 'sprint_id') {
                    if ($value === 'null' || $value === '' || $value === null) {
                        $data[$field] = null;
                    } else {
                        $data[$field] = (int)$value;
                    }
                }
                // Campos numéricos
                elseif (in_array($field, ['story_points', 'es_proyecto_largo'])) {
                    $data[$field] = (int)$value;
                }
                // Campos de texto normales
                else {
                    $data[$field] = $value;
                }
            }
        }
        
        if (empty($data)) {
            $this->jsonResponse(false, ['msg' => 'No hay datos para actualizar'], 400);
        }
        
        $this->cardModel->update($id, $data);
        
        $this->jsonResponse(true, ['msg' => 'Tarjeta actualizada']);
    }
    
    /**
     * Mover tarjeta a otra lista
     */
    public function move() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $cardId = (int)$this->post('id', 0);
        $toList = (int)$this->post('to_list', 0);
        $toIndex = (int)$this->post('to_index', 0);
        
        if (!$cardId || !$toList) {
            $this->jsonResponse(false, ['msg' => 'Datos inválidos'], 400);
        }
        
        $this->cardModel->move($cardId, $toList, $toIndex);
        
        $this->jsonResponse(true, ['msg' => 'Tarjeta movida']);
    }
    
    /**
     * Eliminar una tarjeta
     */
    public function delete() {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(false, ['msg' => 'Token CSRF inválido'], 403);
        }
        
        $id = (int)$this->post('id', 0);
        
        if (!$id) {
            $this->jsonResponse(false, ['msg' => 'ID inválido'], 400);
        }
        
        $this->cardModel->delete($id);
        
        $this->jsonResponse(true, ['msg' => 'Tarjeta eliminada']);
    }
}
