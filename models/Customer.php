<?php
/**
 * Customer Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class Customer extends BaseModel {
    protected $table = 'customers';
    
    public function getActiveCustomers() {
        return $this->findAll(['is_active' => 1], 'name ASC');
    }
    
    public function getCustomersWithStats() {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT o.id) as total_orders,
                       COUNT(DISTINCT s.id) as total_sales,
                       COALESCE(SUM(s.total_amount), 0) as total_revenue,
                       MAX(s.sale_date) as last_sale_date
                FROM customers c
                LEFT JOIN orders o ON c.id = o.customer_id
                LEFT JOIN sales s ON c.id = s.customer_id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    public function getCustomerWithHistory($customer_id) {
        $customer = $this->findById($customer_id);
        if (!$customer) return null;
        
        // Get order history
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as total_items,
                       COALESCE(SUM(oi.quantity_ordered), 0) as total_quantity
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.customer_id = ?
                GROUP BY o.id
                ORDER BY o.order_date DESC
                LIMIT 10";
        $customer['recent_orders'] = $this->db->fetchAll($sql, [$customer_id]);
        
        // Get sales history
        $sql = "SELECT s.*, 
                       COUNT(si.id) as total_items,
                       COALESCE(SUM(si.quantity), 0) as total_quantity
                FROM sales s
                LEFT JOIN sale_items si ON s.id = si.sale_id
                WHERE s.customer_id = ?
                GROUP BY s.id
                ORDER BY s.sale_date DESC
                LIMIT 10";
        $customer['recent_sales'] = $this->db->fetchAll($sql, [$customer_id]);
        
        return $customer;
    }
    
    public function generateCustomerCode($name) {
        // Generate code based on first letters of name
        $words = explode(' ', trim($name));
        $code = '';
        
        foreach ($words as $word) {
            if (strlen($code) < 3 && !empty($word)) {
                $code .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Pad with zeros if needed
        $code = str_pad($code, 3, '0', STR_PAD_RIGHT);
        
        // Get next sequential number
        $sql = "SELECT COUNT(*) + 1 as next_num FROM customers WHERE code LIKE ?";
        $result = $this->db->fetchOne($sql, [$code . '%']);
        $next_num = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
        
        return $code . $next_num;
    }
    
    public function getCustomersByType($type) {
        return $this->findAll(['customer_type' => $type, 'is_active' => 1], 'name ASC');
    }
    
    public function getCustomerTypes() {
        return [
            'regular' => 'Regular',
            'wholesale' => 'Mayorista',
            'retail' => 'Detallista'
        ];
    }
    
    public function getCustomerTypeDisplay($type) {
        $types = $this->getCustomerTypes();
        return isset($types[$type]) ? $types[$type] : $type;
    }
    
    public function getCustomerStats($customer_id) {
        $stats = [];
        
        // Order stats
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                    COALESCE(SUM(total_amount), 0) as total_ordered_amount
                FROM orders 
                WHERE customer_id = ?";
        $result = $this->db->fetchOne($sql, [$customer_id]);
        $stats['orders'] = $result;
        
        // Sales stats
        $sql = "SELECT 
                    COUNT(*) as total_sales,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(paid_amount), 0) as total_paid,
                    COALESCE(AVG(total_amount), 0) as average_sale
                FROM sales 
                WHERE customer_id = ?";
        $result = $this->db->fetchOne($sql, [$customer_id]);
        $stats['sales'] = $result;
        
        // Payment stats
        $sql = "SELECT 
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_sales,
                    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_sales,
                    COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total_amount - paid_amount ELSE 0 END), 0) as pending_amount
                FROM sales 
                WHERE customer_id = ?";
        $result = $this->db->fetchOne($sql, [$customer_id]);
        $stats['payments'] = $result;
        
        return $stats;
    }
    
    public function getTopCustomers($limit = 10) {
        $sql = "SELECT c.*, 
                       COUNT(s.id) as total_sales,
                       COALESCE(SUM(s.total_amount), 0) as total_revenue
                FROM customers c
                LEFT JOIN sales s ON c.id = s.customer_id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY total_revenue DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    public function searchCustomers($query, $limit = 20) {
        $search = "%{$query}%";
        $sql = "SELECT * FROM customers 
                WHERE is_active = 1 
                  AND (name LIKE ? OR code LIKE ? OR contact_person LIKE ? OR phone LIKE ?)
                ORDER BY name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$search, $search, $search, $search, $limit]);
    }
    
    public function updateCreditLimit($customer_id, $credit_limit) {
        return $this->update($customer_id, ['credit_limit' => $credit_limit]);
    }
    
    public function getCreditBalance($customer_id) {
        $sql = "SELECT 
                    c.credit_limit,
                    COALESCE(SUM(CASE WHEN s.payment_status != 'paid' THEN s.total_amount - s.paid_amount ELSE 0 END), 0) as pending_amount
                FROM customers c
                LEFT JOIN sales s ON c.id = s.customer_id
                WHERE c.id = ?
                GROUP BY c.id";
        
        $result = $this->db->fetchOne($sql, [$customer_id]);
        
        if ($result) {
            return [
                'credit_limit' => $result['credit_limit'],
                'pending_amount' => $result['pending_amount'],
                'available_credit' => $result['credit_limit'] - $result['pending_amount']
            ];
        }
        
        return ['credit_limit' => 0, 'pending_amount' => 0, 'available_credit' => 0];
    }
    
    public function deactivateCustomer($customer_id) {
        return $this->update($customer_id, ['is_active' => 0]);
    }
    
    public function activateCustomer($customer_id) {
        return $this->update($customer_id, ['is_active' => 1]);
    }
}
?>