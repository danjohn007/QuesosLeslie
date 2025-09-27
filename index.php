<?php
/**
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 * Main Entry Point
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once 'config/config.php';
require_once 'config/database.php';

// Auto-detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('BASE_URL', $protocol . '://' . $host . $path . '/');

// Router
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$url_parts = explode('/', $url);

$controller = !empty($url_parts[0]) ? $url_parts[0] : 'home';
$action = !empty($url_parts[1]) ? $url_parts[1] : 'index';
$params = array_slice($url_parts, 2);

// Security: Only allow alphanumeric characters and underscores
$controller = preg_replace('/[^a-zA-Z0-9_]/', '', $controller);
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);

$controller_file = 'controllers/' . ucfirst($controller) . 'Controller.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    $controller_class = ucfirst($controller) . 'Controller';
    
    if (class_exists($controller_class)) {
        $controller_instance = new $controller_class();
        
        if (method_exists($controller_instance, $action)) {
            call_user_func_array([$controller_instance, $action], $params);
        } else {
            // Method not found, show 404
            http_response_code(404);
            require_once 'views/errors/404.php';
        }
    } else {
        // Controller class not found
        http_response_code(404);
        require_once 'views/errors/404.php';
    }
} else {
    // Controller file not found
    http_response_code(404);
    require_once 'views/errors/404.php';
}
?>