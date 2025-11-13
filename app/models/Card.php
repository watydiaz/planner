<?php
/**
 * Modelo Card - Gestión de tarjetas/tareas
 */

class Card {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las tarjetas de una lista
     */
    public function getByList($listId) {
        $sql = "SELECT * FROM cards WHERE list_id = ? ORDER BY position ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$listId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener todas las tarjetas de un tablero
     */
    public function getByBoard($boardId) {
        $sql = "
            SELECT c.* 
            FROM cards c
            INNER JOIN lists l ON c.list_id = l.id
            WHERE l.board_id = ?
            ORDER BY c.position ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener tarjetas de un sprint
     */
    public function getBySprint($sprintId) {
        $sql = "SELECT * FROM cards WHERE sprint_id = ? ORDER BY position ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sprintId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una tarjeta por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM cards WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear una nueva tarjeta
     */
    public function create($data) {
        $listId = $data['list_id'];
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $storyPoints = $data['story_points'] ?? 0;
        $asignadoA = $data['asignado_a'] ?? '';
        $sprintId = $data['sprint_id'] ?? null;
        $fechaEntrega = $data['fecha_entrega'] ?? null;
        $categoria = $data['categoria'] ?? '';
        $esProyectoLargo = $data['es_proyecto_largo'] ?? 0;
        $fechaInicio = $data['fecha_inicio'] ?? null;
        
        // Obtener última posición
        $sql = "SELECT MAX(position) as max_pos FROM cards WHERE list_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$listId]);
        $result = $stmt->fetch();
        $position = ($result['max_pos'] ?? -1) + 1;
        
        $sql = "INSERT INTO cards (list_id, title, description, position, story_points, asignado_a, sprint_id, fecha_entrega, categoria, es_proyecto_largo, fecha_inicio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $listId, $title, $description, $position, $storyPoints, 
            $asignadoA, $sprintId, $fechaEntrega, $categoria, 
            $esProyectoLargo, $fechaInicio
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar una tarjeta
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'title', 'description', 'story_points', 'asignado_a', 
            'sprint_id', 'fecha_entrega', 'categoria', 
            'es_proyecto_largo', 'fecha_inicio'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE cards SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Mover tarjeta a otra lista
     */
    public function move($cardId, $newListId, $newPosition) {
        // Reordenar tarjetas en lista destino
        $sql = "UPDATE cards SET position = position + 1 WHERE list_id = ? AND position >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newListId, $newPosition]);
        
        // Mover la tarjeta
        $sql = "UPDATE cards SET list_id = ?, position = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newListId, $newPosition, $cardId]);
    }
    
    /**
     * Actualizar posición de una tarjeta
     */
    public function updatePosition($id, $position) {
        $sql = "UPDATE cards SET position = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$position, $id]);
    }
    
    /**
     * Eliminar una tarjeta
     */
    public function delete($id) {
        $sql = "DELETE FROM cards WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Contar actividades de una tarjeta
     */
    public function countActivities($cardId) {
        $sql = "SELECT COUNT(*) as total FROM card_activities WHERE card_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cardId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
