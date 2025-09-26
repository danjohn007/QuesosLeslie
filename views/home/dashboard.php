<!-- Dashboard Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Ventas Hoy</div>
                        <div class="h5 mb-0 font-weight-bold">
                            $<?php echo number_format($stats['sales_today']['total'], 2); ?>
                        </div>
                        <small><?php echo $stats['sales_today']['count']; ?> transacciones</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-coin fa-2x"></i>
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
                        <div class="text-xs font-weight-bold mb-1">Pedidos Pendientes</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['pending_orders']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cart3 fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold mb-1">Rutas Activas</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['active_routes']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-truck fa-2x"></i>
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
                        <div class="text-xs font-weight-bold mb-1">Alertas</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php echo $stats['low_stock'] + $stats['expiring_products']; ?>
                        </div>
                        <small><?php echo $stats['expiring_products']; ?> próximos a caducar</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-cart3"></i> Pedidos Recientes
                </h6>
                <a href="<?php echo BASE_URL; ?>orders" class="btn btn-sm btn-primary">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_orders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Fecha Entrega</th>
                                <th>Estado</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $order['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($order['order_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($order['delivery_date'])); ?></td>
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
                                    <span class="badge bg-<?php echo $status_classes[$order['status']]; ?> badge-status">
                                        <?php echo $status_labels[$order['status']]; ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">No hay pedidos recientes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Alerts and Routes -->
    <div class="col-xl-4 col-lg-5">
        <!-- Alerts -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-bell"></i> Alertas
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($alerts)): ?>
                <?php foreach (array_slice($alerts, 0, 5) as $alert): ?>
                <div class="alert alert-<?php echo $alert['priority'] === 'high' ? 'warning' : 'info'; ?> py-2 mb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?php echo $alert['type'] === 'expiration_alert' ? 'clock' : 'info-circle'; ?> me-2"></i>
                        <div class="flex-grow-1">
                            <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                            <br>
                            <small><?php echo htmlspecialchars($alert['message']); ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No hay alertas pendientes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Today's Routes -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-truck"></i> Rutas de Hoy
                </h6>
                <a href="<?php echo BASE_URL; ?>logistics" class="btn btn-sm btn-primary">Ver Todas</a>
            </div>
            <div class="card-body">
                <?php if (!empty($routes_today)): ?>
                <?php foreach ($routes_today as $route): ?>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div>
                        <strong><?php echo htmlspecialchars($route['route_name']); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($route['driver_name']); ?></small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?php echo $route['status'] === 'completed' ? 'success' : ($route['status'] === 'in_progress' ? 'primary' : 'secondary'); ?>">
                            <?php echo ucfirst($route['status']); ?>
                        </span>
                        <br>
                        <small class="text-muted"><?php echo $route['completed_orders']; ?>/<?php echo $route['total_orders']; ?> entregas</small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-truck text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No hay rutas programadas para hoy</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-lightning"></i> Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>orders/create" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i><br>
                            Nuevo Pedido
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>sales/create" class="btn btn-outline-success w-100">
                            <i class="bi bi-cash"></i><br>
                            Nueva Venta
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>customers/create" class="btn btn-outline-info w-100">
                            <i class="bi bi-person-plus"></i><br>
                            Nuevo Cliente
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>logistics/create" class="btn btn-outline-warning w-100">
                            <i class="bi bi-truck"></i><br>
                            Nueva Ruta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>