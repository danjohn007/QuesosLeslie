<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Error'; ?> - Sistema Quesos Leslie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 500px;
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
                    <div class="error-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h1 class="display-4 text-danger"><?php echo isset($code) ? $code : 'Error'; ?></h1>
                    <h3 class="mb-3"><?php echo isset($title) ? htmlspecialchars($title) : 'Ha ocurrido un error'; ?></h3>
                    <p class="text-muted mb-4">
                        <?php echo isset($message) ? htmlspecialchars($message) : 'Por favor intenta nuevamente más tarde.'; ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                            <i class="bi bi-house"></i> Ir al Inicio
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>