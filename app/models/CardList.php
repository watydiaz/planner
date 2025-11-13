<?php
/**
 * Modelo CardList - Gestión de listas/columnas del kanban
 */

class CardList {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las listas de un tablero
     */
    public function getByBoard($boardId) {
        $sql = "SELECT * FROM lists WHERE board_id = ? ORDER BY position ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una lista por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM lists WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear una nueva lista
     */
    public function create($boardId, $title, $position = null) {
        if ($position === null) {
            // Obtener la última posición
            $sql = "SELECT MAX(position) as max_pos FROM lists WHERE board_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$boardId]);
            $result = $stmt->fetch();
            $position = ($result['max_pos'] ?? -1) + 1;
        }
        
        $sql = "INSERT INTO lists (board_id, title, position) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId, $title, $position]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar una lista
     */
    public function update($id, $title) {
        $sql = "UPDATE lists SET title = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$title, $id]);
    }
    
    /**
     * Actualizar posición de una lista
     */
    public function updatePosition($id, $position) {
        $sql = "UPDATE lists SET position = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$position, $id]);
    }
    
    /**
     * Eliminar una lista
     */
    public function delete($id) {
        $sql = "DELETE FROM lists WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Contar tarjetas en una lista
     */
    public function countCards($listId) {
        $sql = "SELECT COUNT(*) as total FROM cards WHERE list_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$listId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
