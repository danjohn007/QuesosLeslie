<?php
/**
 * Product Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';
    
    public function getActiveProducts() {
        return $this->findAll(['is_active' => 1], 'name ASC');
    }
    
    public function getProductsByCategory($category) {
        return $this->findAll(['category' => $category, 'is_active' => 1], 'name ASC');
    }
    
    public function getProductsWithStock() {
        $sql = "SELECT p.*, 
                       COALESCE(SUM(pb.quantity_available), 0) as total_stock,
                       COUNT(pb.id) as active_batches,
                       MIN(pb.expiration_date) as nearest_expiration
                FROM products p
                LEFT JOIN production_batches pb ON p.id = pb.product_id 
                    AND pb.quantity_available > 0 
                    AND pb.quality_status = 'good'
                WHERE p.is_active = 1
                GROUP BY p.id
                ORDER BY p.name";
        
        return $this->db->fetchAll($sql);
    }
    
    public function getProductWithBatches($product_id) {
        $product = $this->findById($product_id);
        if (!$product) return null;
        
        $sql = "SELECT pb.*, 
                       DATEDIFF(pb.expiration_date, CURDATE()) as days_to_expire
                FROM production_batches pb
                WHERE pb.product_id = ? AND pb.quantity_available > 0
                ORDER BY pb.expiration_date ASC";
        
        $product['batches'] = $this->db->fetchAll($sql, [$product_id]);
        
        return $product;
    }
    
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category";
        $result = $this->db->fetchAll($sql);
        return array_column($result, 'category');
    }
    
    public function getUnitTypes() {
        return ['bulk' => 'A Granel', 'piece' => 'Por Pieza', 'package' => 'Por Paquete'];
    }
    
    public function getUnitTypeDisplay($unit_type) {
        $types = $this->getUnitTypes();
        return isset($types[$unit_type]) ? $types[$unit_type] : $unit_type;
    }
    
    public function validateUnitType($unit_type) {
        return array_key_exists($unit_type, $this->getUnitTypes());
    }
    
    public function getProductStats($product_id) {
        $stats = [];
        
        // Production stats
        $sql = "SELECT 
                    COUNT(*) as total_batches,
                    COALESCE(SUM(quantity_produced), 0) as total_produced,
                    COALESCE(SUM(quantity_available), 0) as total_available,
                    COALESCE(SUM(quantity_assigned), 0) as total_assigned
                FROM production_batches 
                WHERE product_id = ?";
        $result = $this->db->fetchOne($sql, [$product_id]);
        $stats['production'] = $result;
        
        // Sales stats
        $sql = "SELECT 
                    COUNT(*) as total_sales,
                    COALESCE(SUM(si.quantity), 0) as total_sold,
                    COALESCE(SUM(si.subtotal), 0) as total_revenue
                FROM sale_items si
                WHERE si.product_id = ?";
        $result = $this->db->fetchOne($sql, [$product_id]);
        $stats['sales'] = $result;
        
        // Orders stats
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(oi.quantity_ordered), 0) as total_ordered
                FROM order_items oi
                WHERE oi.product_id = ?";
        $result = $this->db->fetchOne($sql, [$product_id]);
        $stats['orders'] = $result;
        
        return $stats;
    }
    
    public function getLowStockProducts($threshold = 10) {
        $sql = "SELECT p.*, 
                       COALESCE(SUM(pb.quantity_available), 0) as total_stock
                FROM products p
                LEFT JOIN production_batches pb ON p.id = pb.product_id 
                    AND pb.quantity_available > 0 
                    AND pb.quality_status = 'good'
                WHERE p.is_active = 1
                GROUP BY p.id
                HAVING total_stock <= ?
                ORDER BY total_stock ASC";
        
        return $this->db->fetchAll($sql, [$threshold]);
    }
    
    public function getExpiringProducts($days = 3) {
        $sql = "SELECT p.*, pb.batch_code, pb.expiration_date, pb.quantity_available,
                       DATEDIFF(pb.expiration_date, CURDATE()) as days_to_expire
                FROM products p
                JOIN production_batches pb ON p.id = pb.product_id
                WHERE pb.expiration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    AND pb.quantity_available > 0
                    AND pb.quality_status != 'expired'
                    AND p.is_active = 1
                ORDER BY pb.expiration_date ASC";
        
        return $this->db->fetchAll($sql, [$days]);
    }
    
    public function generateProductCode($category) {
        // Get the next sequential number for the category
        $sql = "SELECT COUNT(*) + 1 as next_num FROM products WHERE category = ?";
        $result = $this->db->fetchOne($sql, [$category]);
        $next_num = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
        
        // Generate code based on category
        $category_code = strtoupper(substr($category, 0, 1));
        return $category_code . $next_num;
    }
    
    public function deactivateProduct($product_id) {
        return $this->update($product_id, ['is_active' => 0]);
    }
    
    public function activateProduct($product_id) {
        return $this->update($product_id, ['is_active' => 1]);
    }
}
?>