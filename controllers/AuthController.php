<?php
/**
 * Authentication Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';
require_once 'models/User.php';

class AuthController extends BaseController {
    
    private $userModel;
    
    public function __construct() {
        // Call parent constructor which handles database and authentication
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function login() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
        } else {
            $this->showLoginForm();
        }
    }
    
    protected function showLoginForm() {
        $data = [
            'page_title' => 'Iniciar Sesión',
            'flash_message' => $this->getFlashMessage()
        ];
        
        // Load login view without layout
        include 'views/auth/login.php';
    }
    
    protected function processLogin() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        $errors = $this->validateInput($_POST, [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        if (empty($errors)) {
            $user = $this->userModel->findByUsername($username);
            
            if ($user && password_verify($password, $user['password']) && $user['is_active']) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                
                // Log the login
                $this->logActivity('LOGIN');
                
                $this->redirect('home', 'Bienvenido al sistema', 'success');
            } else {
                $this->setFlashMessage('Usuario o contraseña incorrectos', 'error');
            }
        } else {
            $this->setFlashMessage('Por favor completa todos los campos', 'error');
        }
        
        $this->showLoginForm();
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity('LOGOUT');
        }
        
        session_destroy();
        $this->redirect('auth/login', 'Has cerrado sesión correctamente', 'success');
    }
    
    public function register() {
        // Only allow admin to register new users
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            $this->redirect('auth/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processRegister();
        } else {
            $this->showRegisterForm();
        }
    }
    
    private function showRegisterForm() {
        $data = [
            'page_title' => 'Registrar Usuario',
            'flash_message' => $this->getFlashMessage()
        ];
        
        $this->loadView('auth/register', $data);
    }
    
    private function processRegister() {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'role' => $_POST['role'] ?? 'sales',
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        // Validate input
        $errors = $this->validateInput($data, [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email|max:100',
            'full_name' => 'required|max:100',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);
        
        // Check password confirmation
        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'][] = 'Las contraseñas no coinciden';
        }
        
        // Check if username exists
        if ($this->userModel->findByUsername($data['username'])) {
            $errors['username'][] = 'El nombre de usuario ya existe';
        }
        
        // Check if email exists
        if ($this->userModel->findByEmail($data['email'])) {
            $errors['email'][] = 'El email ya está registrado';
        }
        
        if (empty($errors)) {
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['confirm_password']);
            
            $user_id = $this->userModel->create($data);
            
            if ($user_id) {
                $this->logActivity('CREATE', 'users', $user_id, null, $data);
                $this->redirect('users', 'Usuario registrado exitosamente', 'success');
            } else {
                $this->setFlashMessage('Error al registrar el usuario', 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->showRegisterForm();
    }
    
    public function profile() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateProfile();
        } else {
            $this->showProfile();
        }
    }
    
    private function showProfile() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        
        $data = [
            'page_title' => 'Mi Perfil',
            'user' => $user,
            'flash_message' => $this->getFlashMessage()
        ];
        
        $this->loadView('auth/profile', $data);
    }
    
    private function updateProfile() {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        $errors = $this->validateInput($data, [
            'full_name' => 'required|max:100',
            'email' => 'required|email|max:100'
        ]);
        
        // Check if email exists for another user
        $existing_user = $this->userModel->findByEmail($data['email']);
        if ($existing_user && $existing_user['id'] != $_SESSION['user_id']) {
            $errors['email'][] = 'El email ya está registrado por otro usuario';
        }
        
        // Handle password change
        if (!empty($new_password)) {
            $user = $this->userModel->findById($_SESSION['user_id']);
            
            if (!password_verify($current_password, $user['password'])) {
                $errors['current_password'][] = 'La contraseña actual es incorrecta';
            }
            
            if (strlen($new_password) < 6) {
                $errors['new_password'][] = 'La nueva contraseña debe tener al menos 6 caracteres';
            }
            
            if ($new_password !== $confirm_password) {
                $errors['confirm_password'][] = 'Las contraseñas no coinciden';
            }
            
            if (empty($errors)) {
                $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }
        
        if (empty($errors)) {
            if ($this->userModel->update($_SESSION['user_id'], $data)) {
                $_SESSION['user_name'] = $data['full_name'];
                $this->logActivity('UPDATE', 'users', $_SESSION['user_id'], null, $data);
                $this->redirect('auth/profile', 'Perfil actualizado exitosamente', 'success');
            } else {
                $this->setFlashMessage('Error al actualizar el perfil', 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->showProfile();
    }
    
    protected function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
        try {
            $sql = "INSERT INTO system_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $_SESSION['user_id'] ?? null,
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