<?php
/**
 * Configuration File
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'laptopfi_quesosleslie');
define('DB_USER', 'laptopfi_quesosleslie');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8');

// Application Configuration
define('APP_NAME', 'Sistema de Logística - Quesos Leslie');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Mexico_City');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Password Configuration
define('PASSWORD_SALT', 'quejos_leslie_2024_salt');

// File Upload Configuration
define('UPLOAD_PATH', 'assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Pagination
define('ITEMS_PER_PAGE', 20);

// QR Code Configuration  
define('QR_CODE_PATH', 'assets/qr_codes/');

// WhatsApp Configuration (placeholder for future integration)
define('WHATSAPP_API_URL', '');
define('WHATSAPP_TOKEN', '');

// System Modules Status
define('MODULES', [
    'production' => true,
    'orders' => true,
    'logistics' => true,
    'sales' => true,
    'returns' => true,
    'customer_experience' => true,
    'analytics' => true,
    'customers' => true,
    'financial' => true
]);

// Error Handling
function handleError($errno, $errstr, $errfile, $errline) {
    $error = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error, 3, 'logs/error.log');
    
    if ($errno === E_ERROR || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        die('A critical error occurred. Please check the error log.');
    }
}

set_error_handler('handleError');

// Create necessary directories
$dirs = ['logs', 'assets/uploads', 'assets/qr_codes'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>