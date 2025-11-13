<?php
/**
 * Modelo Sprint - Gestión de sprints
 */

class Sprint {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los sprints de un tablero
     */
    public function getByBoard($boardId) {
        $sql = "SELECT * FROM sprints WHERE board_id = ? ORDER BY fecha_inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener sprint activo de un tablero
     */
    public function getActive($boardId) {
        $sql = "SELECT * FROM sprints WHERE board_id = ? AND estado = 'activo' ORDER BY fecha_inicio DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener un sprint por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM sprints WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear un nuevo sprint
     */
    public function create($boardId, $nombre, $fechaInicio, $fechaFin, $objetivo = '') {
        // Completar sprints activos anteriores
        $this->completeActiveSprints($boardId);
        
        $sql = "INSERT INTO sprints (board_id, nombre, fecha_inicio, fecha_fin, objetivo, estado) 
                VALUES (?, ?, ?, ?, ?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId, $nombre, $fechaInicio, $fechaFin, $objetivo]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar un sprint
     */
    public function update($id, $nombre, $fechaInicio, $fechaFin, $objetivo = '') {
        $sql = "UPDATE sprints SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, objetivo = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $fechaInicio, $fechaFin, $objetivo, $id]);
    }
    
    /**
     * Cambiar estado de un sprint
     */
    public function changeStatus($id, $estado) {
        $sql = "UPDATE sprints SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
    
    /**
     * Completar sprint
     */
    public function complete($id) {
        return $this->changeStatus($id, 'completado');
    }
    
    /**
     * Eliminar un sprint
     */
    public function delete($id) {
        $sql = "DELETE FROM sprints WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Completar todos los sprints activos de un tablero
     */
    private function completeActiveSprints($boardId) {
        $sql = "UPDATE sprints SET estado = 'completado' WHERE board_id = ? AND estado = 'activo'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boardId]);
    }
    
    /**
     * Obtener estadísticas de un sprint
     */
    public function getStats($sprintId) {
        $sql = "
            SELECT 
                COUNT(c.id) AS total_tareas,
                SUM(c.story_points) AS total_puntos,
                SUM(CASE WHEN l.title = 'Completado' THEN 1 ELSE 0 END) AS tareas_completadas,
                SUM(CASE WHEN l.title = 'Completado' THEN c.story_points ELSE 0 END) AS puntos_completados
            FROM sprints s
            LEFT JOIN cards c ON c.sprint_id = s.id
            LEFT JOIN lists l ON c.list_id = l.id
            WHERE s.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sprintId]);
        return $stmt->fetch();
    }
}
