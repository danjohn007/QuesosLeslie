<?php
/**
 * Base Model Class
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

class BaseModel {
    protected $db;
    protected $table;
    protected $primary_key = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findAll($conditions = [], $order_by = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        if ($order_by) {
            $sql .= " ORDER BY {$order_by}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primary_key} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function findOne($conditions = []) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }
    
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->execute($sql, array_values($data));
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                WHERE {$this->primary_key} = ?";
        
        return $this->db->execute($sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primary_key} = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    public function paginate($page = 1, $per_page = ITEMS_PER_PAGE, $conditions = [], $order_by = null) {
        $offset = ($page - 1) * $per_page;
        
        $data = $this->findAll($conditions, $order_by, $per_page, $offset);
        $total = $this->count($conditions);
        $total_pages = ceil($total / $per_page);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
    }
    
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    public function executeQuery($sql, $params = []) {
        return $this->db->fetchAll($sql, $params);
    }
    
    public function executeUpdate($sql, $params = []) {
        return $this->db->execute($sql, $params);
    }
    
    protected function beforeCreate($data) {
        return $data;
    }
    
    protected function afterCreate($id, $data) {
        // Override in child classes if needed
    }
    
    protected function beforeUpdate($id, $data) {
        return $data;
    }
    
    protected function afterUpdate($id, $data) {
        // Override in child classes if needed
    }
    
    protected function beforeDelete($id) {
        return true;
    }
    
    protected function afterDelete($id) {
        // Override in child classes if needed
    }
}
?>