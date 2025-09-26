<?php
/**
 * Database Connection and Base URL Test
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

// Include configuration
require_once 'config/config.php';
require_once 'config/database.php';

// Auto-detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$base_url = $protocol . '://' . $host . $path . '/';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0">
                            <i class="bi bi-gear-fill"></i> Test de Sistema
                        </h2>
                    </div>
                    <div class="card-body">
                        <h3 class="h4 mb-4">Sistema de Logística - Quesos Leslie</h3>
                        
                        <!-- Base URL Test -->
                        <div class="alert alert-info">
                            <h5><i class="bi bi-link-45deg"></i> Base URL</h5>
                            <p class="mb-0"><strong>URL detectada:</strong> <?php echo htmlspecialchars($base_url); ?></p>
                        </div>
                        
                        <!-- Database Connection Test -->
                        <div class="mt-4">
                            <h5><i class="bi bi-database"></i> Conexión a Base de Datos</h5>
                            <?php
                            try {
                                $db = Database::getInstance();
                                $connection_test = $db->testConnection();
                                
                                if ($connection_test) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="bi bi-check-circle"></i> ';
                                    echo '<strong>¡Conexión exitosa!</strong><br>';
                                    echo 'Host: ' . DB_HOST . '<br>';
                                    echo 'Base de datos: ' . DB_NAME . '<br>';
                                    echo 'Usuario: ' . DB_USER;
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-danger">';
                                    echo '<i class="bi bi-x-circle"></i> ';
                                    echo '<strong>Error de conexión</strong><br>';
                                    echo 'No se pudo conectar a la base de datos.';
                                    echo '</div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="bi bi-x-circle"></i> ';
                                echo '<strong>Error de conexión:</strong><br>';
                                echo htmlspecialchars($e->getMessage());
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                        <!-- PHP Configuration -->
                        <div class="mt-4">
                            <h5><i class="bi bi-code-slash"></i> Configuración PHP</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Versión PHP:</strong></td>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Zona horaria:</strong></td>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Memoria límite:</strong></td>
                                        <td><?php echo ini_get('memory_limit'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Upload max filesize:</strong></td>
                                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Required Extensions -->
                        <div class="mt-4">
                            <h5><i class="bi bi-puzzle"></i> Extensiones PHP</h5>
                            <div class="row">
                                <?php
                                $required_extensions = ['pdo', 'pdo_mysql', 'session', 'json', 'mbstring'];
                                foreach ($required_extensions as $ext) {
                                    $loaded = extension_loaded($ext);
                                    echo '<div class="col-md-6 mb-2">';
                                    echo '<span class="badge ' . ($loaded ? 'bg-success' : 'bg-danger') . '">';
                                    echo ($loaded ? '<i class="bi bi-check"></i>' : '<i class="bi bi-x"></i>') . ' ' . $ext;
                                    echo '</span>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Directory Permissions -->
                        <div class="mt-4">
                            <h5><i class="bi bi-folder-check"></i> Permisos de Directorios</h5>
                            <div class="row">
                                <?php
                                $required_dirs = ['logs', 'assets/uploads', 'assets/qr_codes'];
                                foreach ($required_dirs as $dir) {
                                    $writable = is_writable($dir);
                                    $exists = is_dir($dir);
                                    
                                    echo '<div class="col-md-6 mb-2">';
                                    if ($exists && $writable) {
                                        echo '<span class="badge bg-success"><i class="bi bi-check"></i> ' . $dir . '</span>';
                                    } elseif ($exists && !$writable) {
                                        echo '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> ' . $dir . ' (no escribible)</span>';
                                    } else {
                                        echo '<span class="badge bg-danger"><i class="bi bi-x"></i> ' . $dir . ' (no existe)</span>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="<?php echo $base_url; ?>" class="btn btn-primary">
                                <i class="bi bi-house"></i> Ir al Sistema
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>