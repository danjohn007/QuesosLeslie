<?php
/**
 * Order Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class Order extends BaseModel {
    protected $table = 'orders';
    
    public function getOrdersWithCustomers($conditions = [], $limit = null) {
        $sql = "SELECT o.*, c.name as customer_name, c.code as customer_code,
                       c.address, c.phone as customer_phone,
                       u.full_name as created_by_name,
                       COUNT(oi.id) as total_items,
                       COALESCE(SUM(oi.quantity_ordered), 0) as total_quantity
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN users u ON o.created_by = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id";
        
        $params = [];
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "o.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getOrderWithDetails($order_id) {
        $sql = "SELECT o.*, c.name as customer_name, c.code as customer_code,
                       c.address, c.phone as customer_phone, c.email as customer_email,
                       c.customer_type, c.credit_limit,
                       u.full_name as created_by_name
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN users u ON o.created_by = u.id
                WHERE o.id = ?";
        
        $order = $this->db->fetchOne($sql, [$order_id]);
        if (!$order) return null;
        
        // Get order items
        $sql = "SELECT oi.*, p.name as product_name, p.code as product_code,
                       p.unit_measure, p.category,
                       pb.batch_code, pb.expiration_date
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN production_batches pb ON oi.batch_id = pb.id
                WHERE oi.order_id = ?
                ORDER BY p.name";
        
        $order['items'] = $this->db->fetchAll($sql, [$order_id]);
        
        return $order;
    }
    
    public function generateOrderNumber($order_type = 'presale') {
        $prefix = 'ORD';
        $year = date('Y');
        
        // Get next sequential number for the year
        $sql = "SELECT COUNT(*) + 1 as next_num FROM orders WHERE YEAR(order_date) = ?";
        $result = $this->db->fetchOne($sql, [$year]);
        $next_num = str_pad($result['next_num'], 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$next_num}";
    }
    
    public function createOrderWithItems($order_data, $items) {
        try {
            $this->db->beginTransaction();
            
            // Generate order number if not provided
            if (!isset($order_data['order_number'])) {
                $order_data['order_number'] = $this->generateOrderNumber($order_data['order_type'] ?? 'presale');
            }
            
            // Generate QR code
            if (!isset($order_data['qr_code'])) {
                $order_data['qr_code'] = 'QR-' . $order_data['order_number'];
            }
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['quantity_ordered'] * $item['unit_price'];
            }
            $order_data['total_amount'] = $total_amount;
            
            // Create the order
            $order_id = $this->create($order_data);
            
            // Create order items
            foreach ($items as $item) {
                $item['order_id'] = $order_id;
                $item['subtotal'] = $item['quantity_ordered'] * $item['unit_price'];
                
                $sql = "INSERT INTO order_items (order_id, product_id, batch_id, quantity_ordered, unit_price, subtotal, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->execute($sql, [
                    $item['order_id'],
                    $item['product_id'],
                    $item['batch_id'] ?? null,
                    $item['quantity_ordered'],
                    $item['unit_price'],
                    $item['subtotal'],
                    $item['notes'] ?? ''
                ]);
            }
            
            $this->db->commit();
            return $order_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function updateOrderStatus($order_id, $status, $notes = '', $user_id) {
        $valid_statuses = ['pending', 'confirmed', 'in_route', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid order status");
        }
        
        $old_order = $this->findById($order_id);
        if (!$old_order) {
            throw new Exception("Order not found");
        }
        
        $result = $this->update($order_id, ['status' => $status]);
        
        // Log the status change
        $this->logStatusChange($order_id, $old_order['status'], $status, $notes, $user_id);
        
        return $result;
    }
    
    private function logStatusChange($order_id, $old_status, $new_status, $notes, $user_id) {
        $sql = "INSERT INTO system_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (?, 'STATUS_CHANGE', 'orders', ?, ?, ?, ?, ?, NOW())";
        
        $old_values = json_encode(['status' => $old_status]);
        $new_values = json_encode(['status' => $new_status, 'notes' => $notes]);
        
        return $this->db->execute($sql, [
            $user_id,
            $order_id,
            $old_values,
            $new_values,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public function assignBatchesToOrder($order_id, $batch_assignments, $user_id) {
        try {
            $this->db->beginTransaction();
            
            $order = $this->getOrderWithDetails($order_id);
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            require_once 'models/ProductionBatch.php';
            $batchModel = new ProductionBatch();
            
            foreach ($batch_assignments as $item_id => $batch_data) {
                // Update order item with batch assignment
                $sql = "UPDATE order_items SET batch_id = ?, quantity_delivered = ? WHERE id = ?";
                $this->db->execute($sql, [$batch_data['batch_id'], $batch_data['quantity'], $item_id]);
                
                // Assign quantity from batch
                $batchModel->assignQuantity(
                    $batch_data['batch_id'],
                    $batch_data['quantity'],
                    $order_id,
                    'order',
                    "Asignado a pedido {$order['order_number']}",
                    $user_id
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getOrdersByStatus($status) {
        return $this->getOrdersWithCustomers(['status' => $status]);
    }
    
    public function getOrdersByCustomer($customer_id, $limit = null) {
        return $this->getOrdersWithCustomers(['customer_id' => $customer_id], $limit);
    }
    
    public function getOrdersByDateRange($start_date, $end_date) {
        $sql = "SELECT o.*, c.name as customer_name, c.code as customer_code,
                       u.full_name as created_by_name,
                       COUNT(oi.id) as total_items
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN users u ON o.created_by = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.order_date BETWEEN ? AND ?
                GROUP BY o.id
                ORDER BY o.order_date DESC";
        
        return $this->db->fetchAll($sql, [$start_date, $end_date]);
    }
    
    public function getOrderStats() {
        $stats = [];
        
        // Today's orders
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM orders WHERE DATE(order_date) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['today'] = $result;
        
        // This week's orders
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM orders WHERE YEARWEEK(order_date) = YEARWEEK(CURDATE())";
        $result = $this->db->fetchOne($sql);
        $stats['week'] = $result;
        
        // By status
        $sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
        $results = $this->db->fetchAll($sql);
        $stats['by_status'] = [];
        foreach ($results as $result) {
            $stats['by_status'][$result['status']] = $result['count'];
        }
        
        return $stats;
    }
    
    public function getOrderStatusDisplay($status) {
        $statuses = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'in_route' => 'En Ruta',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    public function getOrderTypeDisplay($type) {
        $types = [
            'presale' => 'Preventa',
            'direct' => 'Directo',
            'route' => 'En Ruta'
        ];
        
        return isset($types[$type]) ? $types[$type] : $type;
    }
    
    public function getPendingOrdersForRoute() {
        return $this->getOrdersWithCustomers(['status' => 'confirmed']);
    }
    
    public function markAsDelivered($order_id, $delivery_notes = '', $user_id) {
        try {
            $this->db->beginTransaction();
            
            // Update order status
            $this->updateOrderStatus($order_id, 'delivered', $delivery_notes, $user_id);
            
            // Create sale record from order
            $this->createSaleFromOrder($order_id, $user_id);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function createSaleFromOrder($order_id, $user_id) {
        $order = $this->getOrderWithDetails($order_id);
        if (!$order) return false;
        
        // Create sale record
        $sale_data = [
            'sale_number' => 'VNT-' . str_replace('ORD-', '', $order['order_number']),
            'order_id' => $order_id,
            'customer_id' => $order['customer_id'],
            'sale_date' => date('Y-m-d'),
            'sale_type' => 'presale',
            'total_amount' => $order['total_amount'],
            'payment_method' => 'cash', // Default, can be updated later
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'qr_code' => str_replace('QR-ORD-', 'QR-VNT-', $order['qr_code']),
            'created_by' => $user_id
        ];
        
        $sql = "INSERT INTO sales (sale_number, order_id, customer_id, sale_date, sale_type, total_amount, payment_method, payment_status, paid_amount, qr_code, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, array_values($sale_data));
        $sale_id = $this->db->lastInsertId();
        
        // Create sale items from order items
        foreach ($order['items'] as $item) {
            $sql = "INSERT INTO sale_items (sale_id, product_id, batch_id, quantity, unit_price, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $sale_id,
                $item['product_id'],
                $item['batch_id'],
                $item['quantity_delivered'] ?: $item['quantity_ordered'],
                $item['unit_price'],
                $item['subtotal']
            ]);
        }
        
        return $sale_id;
    }
}
?>