<?php
/**
 * Modelo Activity - Gestión de actividades/bitácora de tarjetas
 */

class Activity {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las actividades de una tarjeta
     */
    public function getByCard($cardId) {
        $sql = "SELECT * FROM card_activities WHERE card_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cardId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una actividad por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM card_activities WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear una nueva actividad
     */
    public function create($cardId, $contenido, $tipo = 'comentario', $fileData = null) {
        $archivoNombre = null;
        $archivoRuta = null;
        $archivoTipo = null;
        $archivoTamano = null;
        
        // Si hay archivo adjunto, procesarlo
        if ($fileData && isset($fileData['name'])) {
            $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($fileData['name']);
            $uniqueName = time() . '_' . uniqid() . '.' . ($fileInfo['extension'] ?? 'bin');
            $uploadPath = $uploadDir . $uniqueName;
            
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                $archivoNombre = $fileData['name'];
                $archivoRuta = 'public/uploads/' . $uniqueName;
                $archivoTipo = $fileData['type'];
                $archivoTamano = $fileData['size'];
            }
        }
        
        $sql = "INSERT INTO card_activities (card_id, tipo, contenido, archivo_nombre, archivo_ruta, archivo_tipo, archivo_tamano) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $cardId, $tipo, $contenido, 
            $archivoNombre, $archivoRuta, $archivoTipo, $archivoTamano
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar una actividad
     */
    public function update($id, $contenido = null, $fileData = null) {
        $activity = $this->getById($id);
        if (!$activity) {
            return false;
        }
        
        $updates = [];
        $values = [];
        
        // Actualizar contenido
        if ($contenido !== null) {
            $updates[] = "contenido = ?";
            $values[] = $contenido;
        }
        
        // Actualizar archivo
        if ($fileData && isset($fileData['name'])) {
            // Eliminar archivo anterior si existe
            if ($activity['archivo_ruta']) {
                $oldPath = dirname(dirname(__DIR__)) . '/' . $activity['archivo_ruta'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Subir nuevo archivo
            $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($fileData['name']);
            $uniqueName = time() . '_' . uniqid() . '.' . ($fileInfo['extension'] ?? 'bin');
            $uploadPath = $uploadDir . $uniqueName;
            
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                $updates[] = "archivo_nombre = ?";
                $values[] = $fileData['name'];
                $updates[] = "archivo_ruta = ?";
                $values[] = 'public/uploads/' . $uniqueName;
                $updates[] = "archivo_tipo = ?";
                $values[] = $fileData['type'];
                $updates[] = "archivo_tamano = ?";
                $values[] = $fileData['size'];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE card_activities SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Eliminar una actividad
     */
    public function delete($id) {
        $activity = $this->getById($id);
        
        // Eliminar archivo físico si existe
        if ($activity && $activity['archivo_ruta']) {
            $filePath = dirname(dirname(__DIR__)) . '/' . $activity['archivo_ruta'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $sql = "DELETE FROM card_activities WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Obtener actividades con archivos adjuntos
     */
    public function getWithFiles($cardId) {
        $sql = "SELECT * FROM card_activities 
                WHERE card_id = ? AND archivo_nombre IS NOT NULL 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cardId]);
        return $stmt->fetchAll();
    }
}
