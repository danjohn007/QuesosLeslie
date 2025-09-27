<?php
/**
 * Router for PHP Development Server
 * This file handles URL rewriting for the built-in PHP server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If it's a real file (CSS, JS, images, etc.), serve it directly
if (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false; // Let the server handle it
}

// Extract the path part for routing
$path = trim($uri, '/');

// Set the url parameter for our routing system
$_GET['url'] = $path;

// Include the main index file
require_once __DIR__ . '/index.php';
?>