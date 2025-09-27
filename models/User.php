<?php
/**
 * User Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function findByUsername($username) {
        return $this->findOne(['username' => $username, 'is_active' => 1]);
    }
    
    public function findByEmail($email) {
        return $this->findOne(['email' => $email]);
    }
    
    public function getActiveUsers() {
        return $this->findAll(['is_active' => 1], 'full_name ASC');
    }
    
    public function getUsersByRole($role) {
        return $this->findAll(['role' => $role, 'is_active' => 1], 'full_name ASC');
    }
    
    public function getDrivers() {
        return $this->getUsersByRole('driver');
    }
    
    public function getSalesUsers() {
        return $this->getUsersByRole('sales');
    }
    
    public function getUserStats($user_id) {
        $stats = [];
        
        // Sales stats
        $sql = "SELECT COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_amount
                FROM sales WHERE created_by = ?";
        $result = $this->db->fetchOne($sql, [$user_id]);
        $stats['sales'] = $result;
        
        // Orders stats
        $sql = "SELECT COUNT(*) as total_orders
                FROM orders WHERE created_by = ?";
        $result = $this->db->fetchOne($sql, [$user_id]);
        $stats['orders'] = $result['total_orders'];
        
        // Routes stats (for drivers)
        $sql = "SELECT COUNT(*) as total_routes, 
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_routes
                FROM routes WHERE driver_id = ?";
        $result = $this->db->fetchOne($sql, [$user_id]);
        $stats['routes'] = $result;
        
        return $stats;
    }
    
    public function getRecentActivity($user_id, $limit = 10) {
        $sql = "SELECT sl.action, sl.table_name, sl.created_at,
                       CASE 
                           WHEN sl.table_name = 'orders' THEN CONCAT('Pedido ', o.order_number)
                           WHEN sl.table_name = 'sales' THEN CONCAT('Venta ', s.sale_number)
                           WHEN sl.table_name = 'production_batches' THEN CONCAT('Lote ', pb.batch_code)
                           ELSE CONCAT('Registro ID: ', sl.record_id)
                       END as description
                FROM system_logs sl
                LEFT JOIN orders o ON sl.table_name = 'orders' AND sl.record_id = o.id
                LEFT JOIN sales s ON sl.table_name = 'sales' AND sl.record_id = s.id
                LEFT JOIN production_batches pb ON sl.table_name = 'production_batches' AND sl.record_id = pb.id
                WHERE sl.user_id = ?
                ORDER BY sl.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$user_id, $limit]);
    }
    
    public function updateLastLogin($user_id) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$user_id]);
    }
    
    public function deactivateUser($user_id) {
        return $this->update($user_id, ['is_active' => 0]);
    }
    
    public function activateUser($user_id) {
        return $this->update($user_id, ['is_active' => 1]);
    }
    
    public function changePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        return $this->update($user_id, ['password' => $hashed_password]);
    }
    
    public function getRoleDisplay($role) {
        $roles = [
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'sales' => 'Vendedor',
            'driver' => 'Chofer'
        ];
        
        return isset($roles[$role]) ? $roles[$role] : $role;
    }
    
    public function validateRole($role) {
        $valid_roles = ['admin', 'manager', 'sales', 'driver'];
        return in_array($role, $valid_roles);
    }
}
?>