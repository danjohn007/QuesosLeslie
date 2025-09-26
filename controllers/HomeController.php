<?php
/**
 * Home Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';

class HomeController extends BaseController {
    
    public function index() {
        // Dashboard data
        $data = [
            'page_title' => 'Dashboard',
            'stats' => $this->getDashboardStats(),
            'recent_orders' => $this->getRecentOrders(),
            'alerts' => $this->getAlerts(),
            'routes_today' => $this->getRoutesToday()
        ];
        
        $this->loadView('home/dashboard', $data);
    }
    
    private function getDashboardStats() {
        $stats = [];
        
        // Today's sales
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                FROM sales WHERE DATE(sale_date) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['sales_today'] = [
            'count' => $result['count'],
            'total' => $result['total']
        ];
        
        // Pending orders
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed')";
        $result = $this->db->fetchOne($sql);
        $stats['pending_orders'] = $result['count'];
        
        // Active routes
        $sql = "SELECT COUNT(*) as count FROM routes 
                WHERE DATE(route_date) = CURDATE() AND status IN ('planned', 'in_progress')";
        $result = $this->db->fetchOne($sql);
        $stats['active_routes'] = $result['count'];
        
        // Low stock alerts
        $sql = "SELECT COUNT(*) as count FROM production_batches pb 
                JOIN products p ON pb.product_id = p.id 
                WHERE pb.quantity_available <= 10 AND pb.quality_status = 'good'";
        $result = $this->db->fetchOne($sql);
        $stats['low_stock'] = $result['count'];
        
        // Expiring products
        $sql = "SELECT COUNT(*) as count FROM production_batches 
                WHERE expiration_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) 
                AND quantity_available > 0 AND quality_status != 'expired'";
        $result = $this->db->fetchOne($sql);
        $stats['expiring_products'] = $result['count'];
        
        return $stats;
    }
    
    private function getRecentOrders() {
        $sql = "SELECT o.id, o.order_number, c.name as customer_name, 
                       o.order_date, o.delivery_date, o.status, o.total_amount
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                ORDER BY o.created_at DESC
                LIMIT 5";
        
        return $this->db->fetchAll($sql);
    }
    
    private function getAlerts() {
        $sql = "SELECT * FROM notifications 
                WHERE (user_id IS NULL OR user_id = ?) 
                AND is_read = 0 
                ORDER BY priority DESC, created_at DESC 
                LIMIT 10";
        
        return $this->db->fetchAll($sql, [$this->user_id]);
    }
    
    private function getRoutesToday() {
        $sql = "SELECT r.id, r.route_name, u.full_name as driver_name, 
                       r.status, r.total_orders, r.completed_orders
                FROM routes r
                JOIN users u ON r.driver_id = u.id
                WHERE DATE(r.route_date) = CURDATE()
                ORDER BY r.start_time";
        
        return $this->db->fetchAll($sql);
    }
}
?>