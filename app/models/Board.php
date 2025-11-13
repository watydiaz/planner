<?php
/**
 * Modelo Board - Gestión de tableros
 */

class Board {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los tableros
     */
    public function getAll() {
        $sql = "SELECT * FROM boards ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener un tablero por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM boards WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear un nuevo tablero
     */
    public function create($nombre, $descripcion = '', $color = '#3b82f6') {
        $sql = "INSERT INTO boards (nombre, descripcion, color) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $color]);
        
        $boardId = $this->db->lastInsertId();
        
        // Crear listas predefinidas
        $this->createDefaultLists($boardId);
        
        return $boardId;
    }
    
    /**
     * Actualizar un tablero
     */
    public function update($id, $nombre, $descripcion = '', $color = '#3b82f6') {
        $sql = "UPDATE boards SET nombre = ?, descripcion = ?, color = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $color, $id]);
    }
    
    /**
     * Eliminar un tablero
     */
    public function delete($id) {
        $sql = "DELETE FROM boards WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Crear listas predefinidas para un tablero nuevo
     */
    private function createDefaultLists($boardId) {
        $lists = [
            ['Por Hacer', 0],
            ['En Progreso', 1],
            ['En Revisión', 2],
            ['Completado', 3]
        ];
        
        $sql = "INSERT INTO lists (board_id, title, position) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($lists as $list) {
            $stmt->execute([$boardId, $list[0], $list[1]]);
        }
    }
    
    /**
     * Obtener estadísticas de un tablero
     */
    public function getStats($boardId) {
        $sql = "
            SELECT 
                COUNT(DISTINCT l.id) AS num_listas,
                COUNT(DISTINCT c.id) AS num_tareas,
                COUNT(DISTINCT s.id) AS num_sprints
            FROM boards b
            LEFT JOIN lists l ON l.board_id = b.id
            LEFT JOIN cards c ON c.list_id = l.id
            LEFT JOIN sprints s ON s.board_id = b.id
            WHERE b.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetch();
    }
}
