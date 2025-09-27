<?php
/**
 * Sale Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class Sale extends BaseModel {
    protected $table = 'sales';
    
    public function getSalesWithCustomers($conditions = [], $limit = null) {
        $sql = "SELECT s.*, c.name as customer_name, c.code as customer_code,
                       u.full_name as created_by_name,
                       o.order_number,
                       COUNT(si.id) as total_items,
                       COALESCE(SUM(si.quantity), 0) as total_quantity
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.created_by = u.id
                LEFT JOIN orders o ON s.order_id = o.id
                LEFT JOIN sale_items si ON s.id = si.sale_id";
        
        $params = [];
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "s.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getSaleWithDetails($sale_id) {
        $sql = "SELECT s.*, c.name as customer_name, c.code as customer_code,
                       c.address, c.phone as customer_phone, c.email as customer_email,
                       u.full_name as created_by_name,
                       o.order_number
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.created_by = u.id
                LEFT JOIN orders o ON s.order_id = o.id
                WHERE s.id = ?";
        
        $sale = $this->db->fetchOne($sql, [$sale_id]);
        if (!$sale) return null;
        
        // Get sale items
        $sql = "SELECT si.*, p.name as product_name, p.code as product_code,
                       p.unit_measure, p.category,
                       pb.batch_code, pb.expiration_date
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                LEFT JOIN production_batches pb ON si.batch_id = pb.id
                WHERE si.sale_id = ?
                ORDER BY p.name";
        
        $sale['items'] = $this->db->fetchAll($sql, [$sale_id]);
        
        // Get payments
        $sql = "SELECT p.*, u.full_name as created_by_name
                FROM payments p
                JOIN users u ON p.created_by = u.id
                WHERE p.sale_id = ?
                ORDER BY p.payment_date DESC";
        
        $sale['payments'] = $this->db->fetchAll($sql, [$sale_id]);
        
        return $sale;
    }
    
    public function generateSaleNumber($sale_type = 'direct') {
        $prefix = 'VNT';
        $year = date('Y');
        
        // Get next sequential number for the year
        $sql = "SELECT COUNT(*) + 1 as next_num FROM sales WHERE YEAR(sale_date) = ?";
        $result = $this->db->fetchOne($sql, [$year]);
        $next_num = str_pad($result['next_num'], 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$next_num}";
    }
    
    public function createSaleWithItems($sale_data, $items) {
        try {
            $this->db->beginTransaction();
            
            // Generate sale number if not provided
            if (!isset($sale_data['sale_number'])) {
                $sale_data['sale_number'] = $this->generateSaleNumber($sale_data['sale_type'] ?? 'direct');
            }
            
            // Generate QR code
            if (!isset($sale_data['qr_code'])) {
                $sale_data['qr_code'] = 'QR-' . $sale_data['sale_number'];
            }
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['quantity'] * $item['unit_price'];
            }
            $sale_data['total_amount'] = $total_amount;
            
            // Create the sale
            $sale_id = $this->create($sale_data);
            
            // Create sale items and update inventory
            require_once 'models/ProductionBatch.php';
            $batchModel = new ProductionBatch();
            
            foreach ($items as $item) {
                $item['sale_id'] = $sale_id;
                $item['subtotal'] = $item['quantity'] * $item['unit_price'];
                
                $sql = "INSERT INTO sale_items (sale_id, product_id, batch_id, quantity, unit_price, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $this->db->execute($sql, [
                    $item['sale_id'],
                    $item['product_id'],
                    $item['batch_id'] ?? null,
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                ]);
                
                // Record sale in batch if batch is specified
                if (!empty($item['batch_id'])) {
                    $batchModel->recordSale(
                        $item['batch_id'],
                        $item['quantity'],
                        $sale_id,
                        "Venta {$sale_data['sale_number']}",
                        $sale_data['created_by']
                    );
                }
            }
            
            $this->db->commit();
            return $sale_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function addPayment($sale_id, $payment_data) {
        try {
            $this->db->beginTransaction();
            
            $sale = $this->findById($sale_id);
            if (!$sale) {
                throw new Exception("Sale not found");
            }
            
            // Insert payment
            $sql = "INSERT INTO payments (sale_id, payment_date, payment_method, amount, reference, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $sale_id,
                $payment_data['payment_date'],
                $payment_data['payment_method'],
                $payment_data['amount'],
                $payment_data['reference'] ?? null,
                $payment_data['notes'] ?? '',
                $payment_data['created_by']
            ]);
            
            // Update sale payment status
            $this->updatePaymentStatus($sale_id);
            
            $this->db->commit();
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updatePaymentStatus($sale_id) {
        $sql = "SELECT total_amount, COALESCE(SUM(p.amount), 0) as total_paid
                FROM sales s
                LEFT JOIN payments p ON s.id = p.sale_id
                WHERE s.id = ?
                GROUP BY s.id";
        
        $result = $this->db->fetchOne($sql, [$sale_id]);
        
        if ($result) {
            $total_amount = $result['total_amount'];
            $total_paid = $result['total_paid'];
            
            if ($total_paid >= $total_amount) {
                $payment_status = 'paid';
            } elseif ($total_paid > 0) {
                $payment_status = 'partial';
            } else {
                $payment_status = 'pending';
            }
            
            $this->update($sale_id, [
                'paid_amount' => $total_paid,
                'payment_status' => $payment_status
            ]);
        }
    }
    
    public function getSalesByCustomer($customer_id, $limit = null) {
        return $this->getSalesWithCustomers(['customer_id' => $customer_id], $limit);
    }
    
    public function getSalesByDateRange($start_date, $end_date) {
        $sql = "SELECT s.*, c.name as customer_name, c.code as customer_code,
                       u.full_name as created_by_name,
                       COUNT(si.id) as total_items
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.created_by = u.id
                LEFT JOIN sale_items si ON s.id = si.sale_id
                WHERE s.sale_date BETWEEN ? AND ?
                GROUP BY s.id
                ORDER BY s.sale_date DESC";
        
        return $this->db->fetchAll($sql, [$start_date, $end_date]);
    }
    
    public function getSalesStats() {
        $stats = [];
        
        // Today's sales
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(paid_amount), 0) as paid
                FROM sales WHERE DATE(sale_date) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['today'] = $result;
        
        // This week's sales
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(paid_amount), 0) as paid
                FROM sales WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())";
        $result = $this->db->fetchOne($sql);
        $stats['week'] = $result;
        
        // This month's sales
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(paid_amount), 0) as paid
                FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
        $result = $this->db->fetchOne($sql);
        $stats['month'] = $result;
        
        // By payment status
        $sql = "SELECT payment_status, COUNT(*) as count, COALESCE(SUM(total_amount - paid_amount), 0) as pending_amount
                FROM sales GROUP BY payment_status";
        $results = $this->db->fetchAll($sql);
        $stats['by_payment_status'] = [];
        foreach ($results as $result) {
            $stats['by_payment_status'][$result['payment_status']] = $result;
        }
        
        // By sale type
        $sql = "SELECT sale_type, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM sales GROUP BY sale_type";
        $results = $this->db->fetchAll($sql);
        $stats['by_type'] = [];
        foreach ($results as $result) {
            $stats['by_type'][$result['sale_type']] = $result;
        }
        
        return $stats;
    }
    
    public function getPendingPayments() {
        return $this->getSalesWithCustomers(['payment_status' => 'pending']);
    }
    
    public function getPartialPayments() {
        return $this->getSalesWithCustomers(['payment_status' => 'partial']);
    }
    
    public function getSaleTypeDisplay($type) {
        $types = [
            'presale' => 'Preventa',
            'direct' => 'Directo',
            'route' => 'En Ruta'
        ];
        
        return isset($types[$type]) ? $types[$type] : $type;
    }
    
    public function getPaymentStatusDisplay($status) {
        $statuses = [
            'pending' => 'Pendiente',
            'partial' => 'Parcial',
            'paid' => 'Pagado'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    public function getPaymentMethodDisplay($method) {
        $methods = [
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia',
            'card' => 'Tarjeta',
            'credit' => 'Crédito'
        ];
        
        return isset($methods[$method]) ? $methods[$method] : $method;
    }
    
    public function getTopSellingProducts($limit = 10, $start_date = null, $end_date = null) {
        $sql = "SELECT p.name, p.code, p.category,
                       COUNT(si.id) as sales_count,
                       SUM(si.quantity) as total_quantity,
                       SUM(si.subtotal) as total_revenue
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id";
        
        $params = [];
        if ($start_date && $end_date) {
            $sql .= " WHERE s.sale_date BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
        }
        
        $sql .= " GROUP BY p.id
                  ORDER BY total_revenue DESC
                  LIMIT ?";
        
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getSalesReportByPeriod($period = 'daily', $limit = 30) {
        $date_format = '';
        $group_by = '';
        
        switch ($period) {
            case 'daily':
                $date_format = '%Y-%m-%d';
                $group_by = 'DATE(sale_date)';
                break;
            case 'weekly':
                $date_format = '%Y-%u';
                $group_by = 'YEARWEEK(sale_date)';
                break;
            case 'monthly':
                $date_format = '%Y-%m';
                $group_by = 'YEAR(sale_date), MONTH(sale_date)';
                break;
        }
        
        $sql = "SELECT DATE_FORMAT(sale_date, '$date_format') as period,
                       COUNT(*) as sales_count,
                       SUM(total_amount) as total_revenue,
                       SUM(paid_amount) as total_paid,
                       AVG(total_amount) as average_sale
                FROM sales
                GROUP BY $group_by
                ORDER BY sale_date DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    public function markAsPaid($sale_id, $payment_method = 'cash', $reference = '', $user_id) {
        try {
            $this->db->beginTransaction();
            
            $sale = $this->findById($sale_id);
            if (!$sale) {
                throw new Exception("Sale not found");
            }
            
            $pending_amount = $sale['total_amount'] - $sale['paid_amount'];
            
            if ($pending_amount > 0) {
                $payment_data = [
                    'payment_date' => date('Y-m-d'),
                    'payment_method' => $payment_method,
                    'amount' => $pending_amount,
                    'reference' => $reference,
                    'notes' => 'Pago completo registrado',
                    'created_by' => $user_id
                ];
                
                $this->addPayment($sale_id, $payment_data);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
?>