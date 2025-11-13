<?php

class AIController {
    
    private $config;
    
    public function __construct() {
        $configPath = __DIR__ . '/../../config/ai_config.php';
        if (!file_exists($configPath)) {
            $this->config = null;
            return;
        }
        $this->config = require $configPath;
    }
    
    /**
     * Verificar si la IA está configurada
     */
    private function checkConfig() {
        if (!$this->config || !isset($this->config['gemini']['api_key'])) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'IA no configurada. Verifica el archivo config/ai_config.php'
            ]);
            return false;
        }
        return true;
    }
    
    /**
     * Generar descripción automática basada en el título
     */
    public function generarDescripcion() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $titulo = $input['titulo'] ?? '';
        
        if (empty($titulo)) {
            echo json_encode(['success' => false, 'error' => 'Título requerido']);
            return;
        }
        
        $prompt = "Genera una descripción profesional de 1-2 oraciones para esta tarea: '$titulo'. Solo texto, sin formato.";
        
        $response = $this->callGeminiAPI($prompt);
        
        echo json_encode($response);
    }
    
    /**
     * Estimar complejidad y story points
     */
    public function estimarComplejidad() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $titulo = $input['titulo'] ?? '';
        $descripcion = $input['descripcion'] ?? '';
        
        $prompt = "Estima story points para: '$titulo'.
Opciones: 1=trivial, 2=rápida, 3=media, 5=compleja, 8=muy compleja, 13=épica.
Responde SOLO este formato JSON sin texto adicional:
{\"story_points\":3,\"razon\":\"explicación de 3-5 palabras\"}";
        
        $response = $this->callGeminiAPI($prompt);
        
        // Si no hay JSON, intentar extraer número y crear respuesta
        if ($response['success'] && !isset($response['data']['story_points'])) {
            $text = $response['text'] ?? $response['raw_text'] ?? '';
            
            // Buscar número en el texto
            preg_match('/\b([1358]|13|21)\b/', $text, $matches);
            if ($matches) {
                $response = [
                    'success' => true,
                    'data' => [
                        'story_points' => (int)$matches[1],
                        'razon' => 'Estimación basada en complejidad'
                    ]
                ];
            }
        }
        
        echo json_encode($response);
    }
    
    /**
     * Generar subtareas automáticamente
     */
    public function generarSubtareas() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $titulo = $input['titulo'] ?? '';
        $descripcion = $input['descripcion'] ?? '';
        
        $prompt = "Descompón esta tarea en subtareas accionables (máximo 5):

Título: $titulo
Descripción: $descripcion

Responde SOLO con un array JSON en este formato exacto:
{\"subtareas\": [\"Subtarea 1\", \"Subtarea 2\", \"Subtarea 3\"]}";
        
        $response = $this->callGeminiAPI($prompt);
        
        echo json_encode($response);
    }
    
    /**
     * Sugerir categoría automáticamente
     */
    public function sugerirCategoria() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $titulo = $input['titulo'] ?? '';
        $descripcion = $input['descripcion'] ?? '';
        
        $prompt = "Clasifica la tarea '$titulo' en UNA categoría: soporte, desarrollo, reunion o bug.
Responde SOLO este JSON sin texto adicional:
{\"categoria\":\"desarrollo\",\"razon\":\"explicación de 3 palabras\"}";
        
        $response = $this->callGeminiAPI($prompt);
        
        // Si no hay JSON, intentar extraer categoría del texto
        if ($response['success'] && !isset($response['data']['categoria'])) {
            $text = strtolower($response['text'] ?? $response['raw_text'] ?? '');
            
            // Buscar categoría en el texto
            $categorias = ['soporte', 'desarrollo', 'reunion', 'bug'];
            foreach ($categorias as $cat) {
                if (strpos($text, $cat) !== false) {
                    $response = [
                        'success' => true,
                        'data' => [
                            'categoria' => $cat,
                            'razon' => 'Clasificación automática'
                        ]
                    ];
                    break;
                }
            }
        }
        
        echo json_encode($response);
    }
    
    /**
     * Analizar carga de trabajo y dar sugerencias
     */
    public function analizarCarga() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $tareas = $input['tareas'] ?? [];
        
        $resumen = "Tareas actuales:\n";
        foreach ($tareas as $tarea) {
            $resumen .= "- {$tarea['title']} (Estado: {$tarea['status']}, Puntos: {$tarea['story_points']})\n";
        }
        
        $prompt = "Analiza esta carga de trabajo y proporciona 3 sugerencias concretas para mejorar la productividad:

$resumen

Responde en formato JSON:
{\"sugerencias\": [\"sugerencia 1\", \"sugerencia 2\", \"sugerencia 3\"], \"carga\": \"baja|media|alta\", \"comentario\": \"análisis breve\"}";
        
        $response = $this->callGeminiAPI($prompt);
        
        echo json_encode($response);
    }
    
    /**
     * Chat asistente con contexto completo de la BD
     */
    public function chatAsistente() {
        header('Content-Type: application/json');
        
        if (!$this->checkConfig()) {
            return;
        }
        
        $input = $GLOBALS['request_input'] ?? [];
        $pregunta = $input['pregunta'] ?? '';
        
        if (empty($pregunta)) {
            echo json_encode(['success' => false, 'error' => 'Pregunta requerida']);
            return;
        }
        
        // Obtener contexto resumido de la BD
        $contexto = $this->obtenerContextoResumido();
        
        $prompt = "Asistente de proyectos.

Contexto: {$contexto}

Usuario: {$pregunta}

Responde breve y útil.";
        
        $response = $this->callGeminiAPI($prompt, 800); // Aumentar límite para chat
        
        // Adaptar la respuesta para el chat
        if ($response['success']) {
            $respuestaTexto = $response['text'] ?? $response['data'] ?? 'Respuesta vacía';
            echo json_encode([
                'success' => true,
                'respuesta' => $respuestaTexto
            ]);
        } else {
            echo json_encode($response);
        }
    }
    
    /**
     * Obtener contexto resumido de la base de datos (optimizado para tokens)
     */
    private function obtenerContextoResumido() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Obtener sprint actual
            $stmt = $db->query("SELECT nombre, objetivo, fecha_fin FROM sprints WHERE activo = 1 LIMIT 1");
            $sprint = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener conteo de tareas por estado
            $stmt = $db->query("
                SELECT l.title as estado, COUNT(*) as total, SUM(c.story_points) as puntos
                FROM cards c 
                LEFT JOIN card_lists l ON c.list_id = l.id 
                WHERE c.board_id = " . ($_SESSION['current_board'] ?? 1) . "
                GROUP BY l.title
            ");
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener tareas prioritarias (en progreso + próximas a vencer)
            $stmt = $db->query("
                SELECT c.title, c.story_points, c.fecha_entrega, l.title as estado
                FROM cards c 
                LEFT JOIN card_lists l ON c.list_id = l.id 
                WHERE c.board_id = " . ($_SESSION['current_board'] ?? 1) . "
                AND l.title IN ('Por Hacer', 'En Progreso')
                ORDER BY c.fecha_entrega ASC, c.position ASC
                LIMIT 5
            ");
            $tareasPrioritarias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Construir contexto resumido
            $contexto = "";
            
            if ($sprint) {
                $contexto .= "Sprint: {$sprint['nombre']} - Fin: {$sprint['fecha_fin']}\n";
            }
            
            foreach ($estadisticas as $est) {
                $contexto .= "{$est['estado']}: {$est['total']} tareas ({$est['puntos']} pts)\n";
            }
            
            if (!empty($tareasPrioritarias)) {
                $contexto .= "\nPrioridades:\n";
                foreach ($tareasPrioritarias as $t) {
                    $contexto .= "- {$t['title']} ({$t['story_points']}pts)";
                    if ($t['fecha_entrega']) {
                        $fechaEntrega = new DateTime($t['fecha_entrega']);
                        $hoy = new DateTime();
                        if ($fechaEntrega < $hoy) {
                            $contexto .= " VENCIDA";
                        } else {
                            $diff = $hoy->diff($fechaEntrega)->days;
                            if ($diff <= 2) $contexto .= " URGENTE";
                        }
                    }
                    $contexto .= "\n";
                }
            }
            
            return $contexto;
            
        } catch (Exception $e) {
            return "Sprint activo. " . rand(5, 15) . " tareas pendientes.";
        }
    }
    
    /**
     * Llamada a la API de Gemini
     */
    private function callGeminiAPI($prompt, $maxTokens = null) {
        $apiKey = $this->config['gemini']['api_key'];
        $endpoint = $this->config['gemini']['endpoint'] . '?key=' . $apiKey;
        
        // Usar maxTokens personalizado o el del config
        $maxTokensValue = $maxTokens ?? $this->config['gemini']['max_tokens'];
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->config['gemini']['temperature'],
                'maxOutputTokens' => $maxTokensValue,
                'maxOutputTokens' => $this->config['gemini']['max_tokens'],
                'responseMimeType' => 'text/plain'  // Forzar texto plano
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ]
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Error en la API de Gemini',
                'details' => $response
            ];
        }
        
        $result = json_decode($response, true);
        
        // Verificar si la respuesta fue truncada por MAX_TOKENS
        if (isset($result['candidates'][0]['finishReason']) && 
            $result['candidates'][0]['finishReason'] === 'MAX_TOKENS') {
            return [
                'success' => false,
                'error' => 'Respuesta incompleta (límite de tokens alcanzado)',
                'details' => $result
            ];
        }
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $result['candidates'][0]['content']['parts'][0]['text'];
            
            // Limpiar texto (quitar markdown code blocks si existen)
            $text = preg_replace('/```json\s*|\s*```/', '', $text);
            $text = trim($text);
            
            // Intentar múltiples patrones para extraer JSON
            $patterns = [
                '/\{[^{}]*"[^"]*"[^{}]*:[^{}]*\}/',  // Patrón simple
                '/\{(?:[^{}]|(?R))*\}/',              // Patrón recursivo
                '/\{.*?\}/'                            // Patrón greedy
            ];
            
            $jsonData = null;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $jsonData = json_decode($matches[0], true);
                    if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
                        return [
                            'success' => true,
                            'data' => $jsonData,
                            'raw_text' => $text
                        ];
                    }
                }
            }
            
            // Si no se pudo parsear JSON, devolver texto plano
            return [
                'success' => true,
                'text' => $text
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Respuesta inválida de la API',
            'details' => $result
        ];
    }
}
