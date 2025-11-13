<?php
/**
 * Controlador Base - Funciones comunes para todos los controladores
 */

class BaseController {
    
    /**
     * Enviar respuesta JSON
     */
    protected function jsonResponse($success, $data = [], $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => $success,
            ...$data
        ]);
        exit;
    }
    
    /**
     * Validar CSRF token
     */
    protected function validateCSRF() {
        if (!isset($_SESSION['csrf'])) {
            return false;
        }
        
        $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
        return $token === $_SESSION['csrf'];
    }
    
    /**
     * Obtener parámetro POST
     */
    protected function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Obtener parámetro GET
     */
    protected function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Validar que el usuario esté autenticado (si implementas login)
     */
    protected function requireAuth() {
        // Por ahora no hay sistema de login, pero lo dejamos preparado
        return true;
    }
    
    /**
     * Sanitizar string
     */
    protected function sanitize($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
