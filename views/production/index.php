<!-- Production Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Producción Hoy</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['today']['batches_today']; ?></div>
                        <small><?php echo number_format($stats['today']['quantity_today'], 2); ?> kg</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-gear fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Esta Semana</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['week']['batches_week']; ?></div>
                        <small><?php echo number_format($stats['week']['quantity_week'], 2); ?> kg</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-week fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Lotes Activos</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['active']['active_batches']; ?></div>
                        <small><?php echo number_format($stats['active']['total_available'], 2); ?> kg disponibles</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box-seam fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card warning">
            <div class="card-body text-center">
                <a href="<?php echo BASE_URL; ?>production/alerts" class="text-white text-decoration-none">
                    <i class="bi bi-exclamation-triangle fa-2x"></i>
                    <div class="h5 mb-0 font-weight-bold mt-2">Ver Alertas</div>
                    <small>Stock bajo y próximos a caducar</small>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="product" class="form-label">Producto</label>
                        <select name="product" id="product" class="form-select">
                            <option value="">Todos los productos</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo $filter_product == $product['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Estado de Calidad</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="good" <?php echo $filter_status == 'good' ? 'selected' : ''; ?>>Bueno</option>
                            <option value="warning" <?php echo $filter_status == 'warning' ? 'selected' : ''; ?>>Advertencia</option>
                            <option value="expired" <?php echo $filter_status == 'expired' ? 'selected' : ''; ?>>Vencido</option>
                            <option value="damaged" <?php echo $filter_status == 'damaged' ? 'selected' : ''; ?>>Dañado</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?php echo BASE_URL; ?>production" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>production/create_batch" class="btn btn-success btn-lg">
                        <i class="bi bi-plus-circle"></i> Nuevo Lote
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Production Batches Table -->
<div class="card shadow">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-list-ul"></i> Lotes de Producción
        </h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots"></i> Acciones
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>production/products">
                    <i class="bi bi-box"></i> Gestionar Productos
                </a></li>
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>production/inventory">
                    <i class="bi bi-clipboard-data"></i> Ver Inventario
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>production/create_product">
                    <i class="bi bi-plus"></i> Nuevo Producto
                </a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($batches)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Código Lote</th>
                        <th>Producto</th>
                        <th>Fecha Producción</th>
                        <th>Fecha Caducidad</th>
                        <th>Cantidad</th>
                        <th>Disponible</th>
                        <th>Estado</th>
                        <th>Días Rest.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td>
                            <a href="<?php echo BASE_URL; ?>production/view_batch/<?php echo $batch['id']; ?>" class="text-decoration-none fw-bold">
                                <?php echo htmlspecialchars($batch['batch_code']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($batch['product_code']); ?></span>
                            <?php echo htmlspecialchars($batch['product_name']); ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($batch['production_date'])); ?></td>
                        <td>
                            <?php 
                            $expiration_class = '';
                            if ($batch['days_to_expire'] <= 0) {
                                $expiration_class = 'text-danger';
                            } elseif ($batch['days_to_expire'] <= 3) {
                                $expiration_class = 'text-warning';
                            }
                            ?>
                            <span class="<?php echo $expiration_class; ?>">
                                <?php echo date('d/m/Y', strtotime($batch['expiration_date'])); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($batch['quantity_produced'], 2); ?> <?php echo $batch['unit_measure']; ?></td>
                        <td>
                            <span class="fw-bold <?php echo $batch['quantity_available'] <= 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($batch['quantity_available'], 2); ?> <?php echo $batch['unit_measure']; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_classes = [
                                'good' => 'success',
                                'warning' => 'warning',
                                'expired' => 'danger',
                                'damaged' => 'danger'
                            ];
                            $status_labels = [
                                'good' => 'Bueno',
                                'warning' => 'Advertencia',
                                'expired' => 'Vencido',
                                'damaged' => 'Dañado'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_classes[$batch['quality_status']]; ?>">
                                <?php echo $status_labels[$batch['quality_status']]; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($batch['days_to_expire'] < 0): ?>
                                <span class="text-danger fw-bold">Vencido</span>
                            <?php elseif ($batch['days_to_expire'] == 0): ?>
                                <span class="text-warning fw-bold">Hoy</span>
                            <?php else: ?>
                                <?php echo $batch['days_to_expire']; ?> días
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo BASE_URL; ?>production/view_batch/<?php echo $batch['id']; ?>" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>production/edit_batch/<?php echo $batch['id']; ?>" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                <?php if ($pagination['has_prev']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>">Anterior</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagination['has_next']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>">Siguiente</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-box text-muted" style="font-size: 4rem;"></i>
            <h4 class="text-muted mt-3">No hay lotes de producción</h4>
            <p class="text-muted">Comienza creando tu primer lote de producción.</p>
            <a href="<?php echo BASE_URL; ?>production/create_batch" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Primer Lote
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>