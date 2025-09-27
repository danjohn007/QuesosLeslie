<?php
/**
 * Production Batch Model
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'models/BaseModel.php';

class ProductionBatch extends BaseModel {
    protected $table = 'production_batches';
    
    public function getBatchesWithProducts($conditions = [], $limit = null) {
        $sql = "SELECT pb.*, p.name as product_name, p.code as product_code, 
                       p.unit_measure, p.category,
                       u.full_name as created_by_name,
                       DATEDIFF(pb.expiration_date, CURDATE()) as days_to_expire
                FROM production_batches pb
                JOIN products p ON pb.product_id = p.id
                JOIN users u ON pb.created_by = u.id";
        
        $params = [];
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "pb.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY pb.production_date DESC, pb.batch_code DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getBatchWithProduct($batch_id) {
        $sql = "SELECT pb.*, p.name as product_name, p.code as product_code, 
                       p.unit_measure, p.category, p.shelf_life_days,
                       u.full_name as created_by_name
                FROM production_batches pb
                JOIN products p ON pb.product_id = p.id
                JOIN users u ON pb.created_by = u.id
                WHERE pb.id = ?";
        
        return $this->db->fetchOne($sql, [$batch_id]);
    }
    
    public function getAvailableBatches($product_id = null) {
        $sql = "SELECT pb.*, p.name as product_name, p.code as product_code,
                       DATEDIFF(pb.expiration_date, CURDATE()) as days_to_expire
                FROM production_batches pb
                JOIN products p ON pb.product_id = p.id
                WHERE pb.quantity_available > 0 AND pb.quality_status = 'good'";
        
        $params = [];
        if ($product_id) {
            $sql .= " AND pb.product_id = ?";
            $params[] = $product_id;
        }
        
        $sql .= " ORDER BY pb.expiration_date ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function generateBatchCode($product_id, $production_date) {
        // Get product code
        $sql = "SELECT code FROM products WHERE id = ?";
        $product = $this->db->fetchOne($sql, [$product_id]);
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        // Format: LOTE-{PRODUCT_CODE}-{YYMMDD}
        $date_code = date('ymd', strtotime($production_date));
        $base_code = "LOTE-{$product['code']}-{$date_code}";
        
        // Check if code exists and add sequence if needed
        $sequence = 1;
        $batch_code = $base_code;
        
        while ($this->exists(['batch_code' => $batch_code])) {
            $sequence++;
            $batch_code = $base_code . "-" . str_pad($sequence, 2, '0', STR_PAD_LEFT);
        }
        
        return $batch_code;
    }
    
    public function createBatch($data) {
        try {
            $this->db->beginTransaction();
            
            // Calculate expiration date if not provided
            if (!isset($data['expiration_date']) && isset($data['product_id'])) {
                $sql = "SELECT shelf_life_days FROM products WHERE id = ?";
                $product = $this->db->fetchOne($sql, [$data['product_id']]);
                
                if ($product) {
                    $production_date = $data['production_date'];
                    $expiration_date = date('Y-m-d', strtotime($production_date . ' + ' . $product['shelf_life_days'] . ' days'));
                    $data['expiration_date'] = $expiration_date;
                }
            }
            
            // Generate batch code if not provided
            if (!isset($data['batch_code'])) {
                $data['batch_code'] = $this->generateBatchCode($data['product_id'], $data['production_date']);
            }
            
            // Set initial availability
            $data['quantity_available'] = $data['quantity_produced'];
            $data['quantity_assigned'] = 0;
            
            // Create the batch
            $batch_id = $this->create($data);
            
            // Record inventory movement
            $this->recordInventoryMovement($batch_id, 'production', $data['quantity_produced'], null, null, 'Producción inicial del lote', $data['created_by']);
            
            $this->db->commit();
            return $batch_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function assignQuantity($batch_id, $quantity, $reference_id, $reference_type, $notes = '', $user_id) {
        try {
            $this->db->beginTransaction();
            
            $batch = $this->findById($batch_id);
            if (!$batch) {
                throw new Exception("Batch not found");
            }
            
            if ($batch['quantity_available'] < $quantity) {
                throw new Exception("Insufficient quantity available");
            }
            
            // Update batch quantities
            $new_available = $batch['quantity_available'] - $quantity;
            $new_assigned = $batch['quantity_assigned'] + $quantity;
            
            $this->update($batch_id, [
                'quantity_available' => $new_available,
                'quantity_assigned' => $new_assigned
            ]);
            
            // Record inventory movement
            $this->recordInventoryMovement($batch_id, 'assignment', -$quantity, $reference_id, $reference_type, $notes, $user_id);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function releaseQuantity($batch_id, $quantity, $reference_id, $reference_type, $notes = '', $user_id) {
        try {
            $this->db->beginTransaction();
            
            $batch = $this->findById($batch_id);
            if (!$batch) {
                throw new Exception("Batch not found");
            }
            
            if ($batch['quantity_assigned'] < $quantity) {
                throw new Exception("Cannot release more than assigned quantity");
            }
            
            // Update batch quantities
            $new_available = $batch['quantity_available'] + $quantity;
            $new_assigned = $batch['quantity_assigned'] - $quantity;
            
            $this->update($batch_id, [
                'quantity_available' => $new_available,
                'quantity_assigned' => $new_assigned
            ]);
            
            // Record inventory movement
            $this->recordInventoryMovement($batch_id, 'release', $quantity, $reference_id, $reference_type, $notes, $user_id);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function recordSale($batch_id, $quantity, $sale_id, $notes = '', $user_id) {
        try {
            $this->db->beginTransaction();
            
            $batch = $this->findById($batch_id);
            if (!$batch) {
                throw new Exception("Batch not found");
            }
            
            if ($batch['quantity_assigned'] < $quantity) {
                throw new Exception("Cannot sell more than assigned quantity");
            }
            
            // Update batch quantities
            $new_assigned = $batch['quantity_assigned'] - $quantity;
            
            $this->update($batch_id, [
                'quantity_assigned' => $new_assigned
            ]);
            
            // Record inventory movement
            $this->recordInventoryMovement($batch_id, 'sale', -$quantity, $sale_id, 'sale', $notes, $user_id);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function recordInventoryMovement($batch_id, $type, $quantity, $reference_id, $reference_type, $notes, $user_id) {
        $sql = "INSERT INTO inventory_movements (batch_id, movement_type, quantity, reference_id, reference_type, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->execute($sql, [$batch_id, $type, $quantity, $reference_id, $reference_type, $notes, $user_id]);
    }
    
    public function getBatchMovements($batch_id) {
        $sql = "SELECT im.*, u.full_name as created_by_name
                FROM inventory_movements im
                JOIN users u ON im.created_by = u.id
                WHERE im.batch_id = ?
                ORDER BY im.created_at DESC";
        
        return $this->db->fetchAll($sql, [$batch_id]);
    }
    
    public function updateQualityStatus($batch_id, $status, $notes = '', $user_id) {
        $valid_statuses = ['good', 'warning', 'expired', 'damaged'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid quality status");
        }
        
        $result = $this->update($batch_id, [
            'quality_status' => $status,
            'notes' => $notes
        ]);
        
        // Log the quality change
        $this->recordInventoryMovement($batch_id, 'adjustment', 0, null, 'quality_change', "Cambio de estado de calidad a: {$status}. {$notes}", $user_id);
        
        return $result;
    }
    
    public function getQualityStatusDisplay($status) {
        $statuses = [
            'good' => 'Bueno',
            'warning' => 'Advertencia',
            'expired' => 'Vencido',
            'damaged' => 'Dañado'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    public function getExpiringBatches($days = 3) {
        $sql = "SELECT pb.*, p.name as product_name, p.code as product_code,
                       DATEDIFF(pb.expiration_date, CURDATE()) as days_to_expire
                FROM production_batches pb
                JOIN products p ON pb.product_id = p.id
                WHERE pb.expiration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    AND pb.quantity_available > 0
                    AND pb.quality_status != 'expired'
                ORDER BY pb.expiration_date ASC";
        
        return $this->db->fetchAll($sql, [$days]);
    }
    
    public function markAsExpired($batch_id, $user_id) {
        return $this->updateQualityStatus($batch_id, 'expired', 'Marcado como vencido automáticamente', $user_id);
    }
}
?>