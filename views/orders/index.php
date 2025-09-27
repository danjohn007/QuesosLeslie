<!-- Orders Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Pedidos Hoy</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['today']['count']; ?></div>
                        <small>$<?php echo number_format($stats['today']['total'], 2); ?></small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cart3 fa-2x"></i>
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
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['week']['count']; ?></div>
                        <small>$<?php echo number_format($stats['week']['total'], 2); ?></small>
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
                        <div class="text-xs font-weight-bold mb-1">Pendientes</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['by_status']['pending'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Confirmados</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['by_status']['confirmed'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fa-2x"></i>
                    </div>
                </div>
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
                        <label for="status" class="form-label">Estado</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="in_route" <?php echo $filter_status == 'in_route' ? 'selected' : ''; ?>>En Ruta</option>
                            <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="customer" class="form-label">Cliente</label>
                        <select name="customer" id="customer" class="form-select">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" <?php echo $filter_customer == $customer['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?php echo BASE_URL; ?>orders" class="btn btn-outline-secondary">
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
                    <a href="<?php echo BASE_URL; ?>orders/create" class="btn btn-success btn-lg">
                        <i class="bi bi-plus-circle"></i> Nuevo Pedido
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-list-ul"></i> Lista de Pedidos
        </h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots"></i> Vista
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>orders/pending">
                    <i class="bi bi-clock"></i> Solo Pendientes
                </a></li>
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>orders/confirmed">
                    <i class="bi bi-check-circle"></i> Solo Confirmados
                </a></li>
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>orders/delivered">
                    <i class="bi bi-truck"></i> Solo Entregados
                </a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($orders)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Fecha Pedido</th>
                        <th>Fecha Entrega</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $order['id']; ?>" class="text-decoration-none fw-bold">
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </a>
                            <?php if ($order['qr_code']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($order['qr_code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($order['customer_code']); ?></span>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                        <td>
                            <?php 
                            $delivery_class = '';
                            $today = date('Y-m-d');
                            if ($order['delivery_date'] < $today && $order['status'] !== 'delivered') {
                                $delivery_class = 'text-danger';
                            } elseif ($order['delivery_date'] == $today) {
                                $delivery_class = 'text-warning';
                            }
                            ?>
                            <span class="<?php echo $delivery_class; ?>">
                                <?php echo date('d/m/Y', strtotime($order['delivery_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $order['total_items']; ?></span>
                            <?php echo number_format($order['total_quantity'], 2); ?> items
                        </td>
                        <td class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <?php
                            $status_classes = [
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'in_route' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $status_labels = [
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'in_route' => 'En Ruta',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_classes[$order['status']]; ?>">
                                <?php echo $status_labels[$order['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $type_labels = [
                                'presale' => 'Preventa',
                                'direct' => 'Directo',
                                'route' => 'En Ruta'
                            ];
                            ?>
                            <small class="text-muted"><?php echo $type_labels[$order['order_type']] ?? $order['order_type']; ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $order['id']; ?>" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                <a href="<?php echo BASE_URL; ?>orders/edit/<?php echo $order['id']; ?>" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>orders/confirm/<?php echo $order['id']; ?>" 
                                   class="btn btn-outline-success" title="Confirmar">
                                    <i class="bi bi-check-circle"></i>
                                </a>
                                <?php endif; ?>
                                
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown" title="Más opciones">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>orders/print_order/<?php echo $order['id']; ?>" target="_blank">
                                            <i class="bi bi-printer"></i> Imprimir
                                        </a></li>
                                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>orders/cancel/<?php echo $order['id']; ?>">
                                            <i class="bi bi-x-circle"></i> Cancelar
                                        </a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
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
            <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
            <h4 class="text-muted mt-3">No hay pedidos</h4>
            <p class="text-muted">Comienza creando tu primer pedido.</p>
            <a href="<?php echo BASE_URL; ?>orders/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Primer Pedido
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>