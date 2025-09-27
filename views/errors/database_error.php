<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Base de Datos - Sistema Quesos Leslie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #6c757d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
        }
        .error-icon {
            font-size: 8rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="error-card">
                    <div class="error-icon">⚠️</div>
                    <h1 class="display-4 text-danger">Error de Conexión</h1>
                    <h3 class="mb-3">Base de Datos No Disponible</h3>
                    <p class="text-muted mb-4">
                        No se pudo conectar a la base de datos. Por favor, verifica la configuración.
                    </p>
                    <div class="alert alert-warning text-start">
                        <h6><i class="bi bi-info-circle"></i> Detalles del Error:</h6>
                        <small class="text-muted"><?php echo htmlspecialchars($message ?? 'Error de conexión desconocido'); ?></small>
                    </div>
                    <div class="alert alert-info text-start">
                        <h6><i class="bi bi-wrench"></i> Configuración Actual:</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Host:</strong> <?php echo defined('DB_HOST') ? DB_HOST : 'No definido'; ?></li>
                            <li><strong>Base de datos:</strong> <?php echo defined('DB_NAME') ? DB_NAME : 'No definido'; ?></li>
                            <li><strong>Usuario:</strong> <?php echo defined('DB_USER') ? DB_USER : 'No definido'; ?></li>
                        </ul>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="test_connection.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Test de Conexión
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reintentar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>