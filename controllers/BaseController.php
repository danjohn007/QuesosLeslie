<?php
/**
 * Base Controller Class
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

class BaseController {
    protected $db;
    protected $user_id;
    protected $user_role;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            // Database connection failed - handle gracefully
            $this->showDatabaseError($e->getMessage());
            exit;
        }
        $this->checkAuthentication();
    }
    
    protected function checkAuthentication() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            // Allow access to login and test pages
            $allowed_controllers = ['auth', 'test_connection'];
            $current_controller = strtolower(get_class($this));
            $current_controller = str_replace('controller', '', $current_controller);
            
            if (!in_array($current_controller, $allowed_controllers)) {
                header('Location: ' . BASE_URL . 'auth/login');
                exit;
            }
        } else {
            $this->user_id = $_SESSION['user_id'];
            $this->user_role = $_SESSION['user_role'];
        }
    }
    
    protected function requireRole($required_roles) {
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        if (!in_array($this->user_role, $required_roles)) {
            $this->showError('Acceso denegado', 'No tienes permisos para acceder a esta sección.', 403);
            exit;
        }
    }
    
    protected function loadView($view, $data = []) {
        extract($data);
        
        // Start output buffering
        ob_start();
        include "views/{$view}.php";
        $content = ob_get_clean();
        
        // Load layout
        include 'views/layout/main.php';
    }
    
    protected function loadPartialView($view, $data = []) {
        extract($data);
        include "views/{$view}.php";
    }
    
    protected function jsonResponse($data, $status_code = 200) {
        // Check if headers have been sent already
        if (!headers_sent()) {
            http_response_code($status_code);
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url, $message = null, $type = 'info') {
        if ($message) {
            $this->setFlashMessage($message, $type);
        }
        
        if (strpos($url, 'http') !== 0) {
            $url = BASE_URL . ltrim($url, '/');
        }
        
        header("Location: {$url}");
        exit;
    }
    
    protected function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    protected function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
    
    protected function showError($title, $message, $code = 404) {
        // Check if headers have been sent already
        if (!headers_sent()) {
            http_response_code($code);
        }
        $this->loadView('errors/error', [
            'title' => $title,
            'message' => $message,
            'code' => $code
        ]);
    }
    
    protected function showDatabaseError($message) {
        // Check if headers have been sent already
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        // Use output buffering to prevent further header issues
        ob_start();
        include 'views/errors/database_error.php';
        $content = ob_get_clean();
        echo $content;
    }
    
    protected function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule_string) {
            $rules_array = explode('|', $rule_string);
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            
            foreach ($rules_array as $rule) {
                $rule_parts = explode(':', $rule);
                $rule_name = $rule_parts[0];
                $rule_param = isset($rule_parts[1]) ? $rule_parts[1] : null;
                
                switch ($rule_name) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "El campo {$field} es obligatorio.";
                        }
                        break;
                    
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "El campo {$field} debe ser un email válido.";
                        }
                        break;
                    
                    case 'min':
                        if (!empty($value) && strlen($value) < $rule_param) {
                            $errors[$field][] = "El campo {$field} debe tener al menos {$rule_param} caracteres.";
                        }
                        break;
                    
                    case 'max':
                        if (!empty($value) && strlen($value) > $rule_param) {
                            $errors[$field][] = "El campo {$field} no debe exceder {$rule_param} caracteres.";
                        }
                        break;
                    
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "El campo {$field} debe ser numérico.";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    protected function generateQRCode($data) {
        // Generate a simple QR code identifier
        return 'QR-' . strtoupper(substr(md5($data . time()), 0, 10));
    }
    
    protected function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
        try {
            $sql = "INSERT INTO system_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $this->user_id,
                $action,
                $table_name,
                $record_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
?>