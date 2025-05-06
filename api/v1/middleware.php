<?php
/* File: api/v1/middleware.php
 * Descripción: Funciones de middleware para autenticación, rate limiting y CORS
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class SecurityMiddleware {
    // Configuración
    private $jwtSecret;
    private $db;
    private $rateLimit = 60; // Límite de solicitudes por minuto
    private $rateLimitWindow = 60; // Ventana de tiempo en segundos
    
    // Almacenamiento en memoria para rate limiting
    private static $requestCounts = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        global $config;
        $this->jwtSecret = $config['jwt_secret'];
        $this->db = Database::getConnection();
    }
    
    /**
     * Aplicar middleware de seguridad completo
     * 
     * @return array|null Usuario autenticado o null si hay error
     */
    public function applyFullSecurity() {
        // Aplicar CORS
        $this->applyCors();
        
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
        
        // Verificar límite de tasa
        if (!$this->checkRateLimit()) {
            $this->respondError('Demasiadas solicitudes. Por favor, inténtelo más tarde.', 429);
            return null;
        }
        
        // Verificar JWT
        $user = $this->validateJwt();
        if (!$user) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Aplicar solo middleware CORS
     */
    public function applyCors() {
        // Configuración de CORS
        $allowedOrigins = $this->getAllowedOrigins();
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // 24 horas
    }
    
    /**
     * Verificar límite de tasa (rate limiting)
     * 
     * @return bool True si está dentro del límite, false si excede
     */
    public function checkRateLimit() {
        $clientIp = $this->getClientIp();
        $currentTime = time();
        
        // Inicializar contador si no existe
        if (!isset(self::$requestCounts[$clientIp])) {
            self::$requestCounts[$clientIp] = [
                'count' => 0,
                'window_start' => $currentTime
            ];
        }
        
        // Reiniciar contador si la ventana de tiempo ha terminado
        if ($currentTime - self::$requestCounts[$clientIp]['window_start'] > $this->rateLimitWindow) {
            self::$requestCounts[$clientIp] = [
                'count' => 0,
                'window_start' => $currentTime
            ];
        }
        
        // Incrementar contador
        self::$requestCounts[$clientIp]['count']++;
        
        // Verificar límite
        if (self::$requestCounts[$clientIp]['count'] > $this->rateLimit) {
            // Registrar incidente en logs
            $this->logRateLimitExceeded($clientIp);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validar JWT y obtener usuario
     * 
     * @return array|null Datos del usuario o null si la validación falla
     */
    public function validateJwt() {
        // Obtener token del encabezado Authorization
        $authHeader = $this->getAuthHeader();
        if (!$authHeader) {
            $this->respondError('Token de autenticación no proporcionado', 401);
            return null;
        }
        
        // Extraer token (formato: Bearer <token>)
        $jwt = null;
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
        }
        
        if (!$jwt) {
            $this->respondError('Formato de token inválido', 401);
            return null;
        }
        
        // Validar token
        try {
            $decoded = JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));
            
            // Verificar token en la base de datos
            if (!$this->verifyTokenInDatabase($jwt, $decoded->sub)) {
                $this->respondError('Token inválido o revocado', 401);
                return null;
            }
            
            // Obtener información del usuario
            $user = $this->getUserById($decoded->sub);
            if (!$user) {
                $this->respondError('Usuario no encontrado', 401);
                return null;
            }
            
            // Verificar estado del usuario
            if ($user['estado'] !== 'activo') {
                $this->respondError('Cuenta de usuario inactiva o bloqueada', 403);
                return null;
            }
            
            return $user;
        } catch (ExpiredException $e) {
            $this->respondError('El token ha expirado', 401);
            return null;
        } catch (Exception $e) {
            $this->respondError('Error al validar token: ' . $e->getMessage(), 401);
            return null;
        }
    }
    
    /**
     * Verificar si el token existe en la base de datos y es válido
     * 
     * @param string $token Token JWT
     * @param int $userId ID del usuario
     * @return bool True si el token es válido
     */
    private function verifyTokenInDatabase($token, $userId) {
        try {
            $sql = "SELECT * FROM tokens_auth 
                    WHERE id_usuario = :userId 
                    AND token = :token 
                    AND fecha_expiracion > NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener información del usuario por ID
     * 
     * @param int $userId ID del usuario
     * @return array|null Datos del usuario o null si no se encuentra
     */
    private function getUserById($userId) {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, rol, estado 
                    FROM usuarios 
                    WHERE id_usuario = :userId";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener usuario: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener encabezado de autorización
     * 
     * @return string|null Encabezado o null si no existe
     */
    private function getAuthHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Obtener dirección IP del cliente
     * 
     * @return string Dirección IP
     */
    private function getClientIp() {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Si hay múltiples IPs, tomar la primera
        if (strpos($ipAddress, ',') !== false) {
            $ips = explode(',', $ipAddress);
            $ipAddress = trim($ips[0]);
        }
        
        return $ipAddress;
    }
    
    /**
     * Obtener orígenes permitidos para CORS
     * 
     * @return array Lista de orígenes permitidos
     */
    private function getAllowedOrigins() {
        global $config;
        
        // Obtener de la configuración o usar valor predeterminado
        if (isset($config['cors_allowed_origins']) && is_array($config['cors_allowed_origins'])) {
            return $config['cors_allowed_origins'];
        }
        
        // Configuración predeterminada
        return [
            'http://localhost',
            'http://localhost:8080',
            'https://simpro-lite.example.com'
        ];
    }
    
    /**
     * Registrar incidente de exceso de límite de tasa
     * 
     * @param string $clientIp IP del cliente
     */
    private function logRateLimitExceeded($clientIp) {
        try {
            $sql = "INSERT INTO logs_sistema (tipo, modulo, mensaje, ip_address) 
                    VALUES ('security', 'api', :mensaje, :ip)";
                    
            $message = "Límite de tasa excedido";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':mensaje', $message, PDO::PARAM_STR);
            $stmt->bindParam(':ip', $clientIp, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar exceso de límite: " . $e->getMessage());
        }
    }
    
    /**
     * Responder con error
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     */
    public function respondError($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $statusCode
        ]);
    }
}