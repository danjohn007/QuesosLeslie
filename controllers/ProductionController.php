<?php
/**
 * Production Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';
require_once 'models/Product.php';
require_once 'models/ProductionBatch.php';

class ProductionController extends BaseController {
    
    private $productModel;
    private $batchModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->productModel = new Product();
        $this->batchModel = new ProductionBatch();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filter_product = isset($_GET['product']) ? $_GET['product'] : '';
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $conditions = [];
        if ($filter_product) {
            $conditions['product_id'] = $filter_product;
        }
        if ($filter_status) {
            $conditions['quality_status'] = $filter_status;
        }
        
        $batches_data = $this->batchModel->paginate($page, ITEMS_PER_PAGE, $conditions);
        $batches = $this->batchModel->getBatchesWithProducts($conditions);
        
        $data = [
            'page_title' => 'Gestión de Producción',
            'batches' => $batches,
            'products' => $this->productModel->getActiveProducts(),
            'filter_product' => $filter_product,
            'filter_status' => $filter_status,
            'pagination' => $batches_data['pagination'],
            'stats' => $this->getProductionStats()
        ];
        
        $this->loadView('production/index', $data);
    }
    
    public function products() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filter_category = isset($_GET['category']) ? $_GET['category'] : '';
        
        $conditions = ['is_active' => 1];
        if ($filter_category) {
            $conditions['category'] = $filter_category;
        }
        
        $products_data = $this->productModel->paginate($page, ITEMS_PER_PAGE, $conditions);
        $products_with_stock = $this->productModel->getProductsWithStock();
        
        $data = [
            'page_title' => 'Productos',
            'products' => $products_with_stock,
            'categories' => $this->productModel->getCategories(),
            'filter_category' => $filter_category,
            'pagination' => $products_data['pagination']
        ];
        
        $this->loadView('production/products', $data);
    }
    
    public function create_product() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateProduct();
        } else {
            $data = [
                'page_title' => 'Nuevo Producto',
                'categories' => $this->productModel->getCategories(),
                'unit_types' => $this->productModel->getUnitTypes()
            ];
            
            $this->loadView('production/create_product', $data);
        }
    }
    
    private function processCreateProduct() {
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'unit_type' => $_POST['unit_type'] ?? 'piece',
            'unit_measure' => trim($_POST['unit_measure'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'shelf_life_days' => intval($_POST['shelf_life_days'] ?? 30)
        ];
        
        // Validation
        $errors = $this->validateInput($data, [
            'code' => 'required|max:50',
            'name' => 'required|max:100',
            'category' => 'required|max:50',
            'unit_measure' => 'required|max:20',
            'price' => 'required|numeric',
            'shelf_life_days' => 'required|numeric'
        ]);
        
        // Check if code exists
        if ($this->productModel->exists(['code' => $data['code']])) {
            $errors['code'][] = 'El código del producto ya existe';
        }
        
        // Validate unit type
        if (!$this->productModel->validateUnitType($data['unit_type'])) {
            $errors['unit_type'][] = 'Tipo de unidad inválido';
        }
        
        if (empty($errors)) {
            try {
                $product_id = $this->productModel->create($data);
                $this->logActivity('CREATE', 'products', $product_id, null, $data);
                $this->redirect('production/products', 'Producto creado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al crear el producto: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->create_product();
    }
    
    public function create_batch() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateBatch();
        } else {
            $data = [
                'page_title' => 'Nuevo Lote de Producción',
                'products' => $this->productModel->getActiveProducts()
            ];
            
            $this->loadView('production/create_batch', $data);
        }
    }
    
    private function processCreateBatch() {
        $data = [
            'product_id' => intval($_POST['product_id'] ?? 0),
            'production_date' => $_POST['production_date'] ?? date('Y-m-d'),
            'quantity_produced' => floatval($_POST['quantity_produced'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $this->user_id
        ];
        
        // Validation
        $errors = $this->validateInput($data, [
            'product_id' => 'required|numeric',
            'production_date' => 'required',
            'quantity_produced' => 'required|numeric'
        ]);
        
        // Validate product exists
        if (!$this->productModel->findById($data['product_id'])) {
            $errors['product_id'][] = 'Producto no encontrado';
        }
        
        // Validate production date
        if (strtotime($data['production_date']) > time()) {
            $errors['production_date'][] = 'La fecha de producción no puede ser futura';
        }
        
        // Validate quantity
        if ($data['quantity_produced'] <= 0) {
            $errors['quantity_produced'][] = 'La cantidad debe ser mayor a cero';
        }
        
        if (empty($errors)) {
            try {
                $batch_id = $this->batchModel->createBatch($data);
                $this->logActivity('CREATE', 'production_batches', $batch_id, null, $data);
                $this->redirect('production/view_batch/' . $batch_id, 'Lote de producción creado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al crear el lote: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->create_batch();
    }
    
    public function view_batch($batch_id) {
        $batch = $this->batchModel->getBatchWithProduct($batch_id);
        if (!$batch) {
            $this->showError('Lote no encontrado', 'El lote solicitado no existe.', 404);
            return;
        }
        
        $movements = $this->batchModel->getBatchMovements($batch_id);
        
        $data = [
            'page_title' => 'Lote ' . $batch['batch_code'],
            'batch' => $batch,
            'movements' => $movements
        ];
        
        $this->loadView('production/view_batch', $data);
    }
    
    public function edit_batch($batch_id) {
        $batch = $this->batchModel->getBatchWithProduct($batch_id);
        if (!$batch) {
            $this->showError('Lote no encontrado', 'El lote solicitado no existe.', 404);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processEditBatch($batch_id, $batch);
        } else {
            $data = [
                'page_title' => 'Editar Lote ' . $batch['batch_code'],
                'batch' => $batch,
                'products' => $this->productModel->getActiveProducts()
            ];
            
            $this->loadView('production/edit_batch', $data);
        }
    }
    
    private function processEditBatch($batch_id, $current_batch) {
        $data = [
            'production_date' => $_POST['production_date'] ?? $current_batch['production_date'],
            'expiration_date' => $_POST['expiration_date'] ?? $current_batch['expiration_date'],
            'notes' => trim($_POST['notes'] ?? ''),
            'quality_status' => $_POST['quality_status'] ?? $current_batch['quality_status']
        ];
        
        // Validation
        $errors = $this->validateInput($data, [
            'production_date' => 'required',
            'expiration_date' => 'required',
            'quality_status' => 'required'
        ]);
        
        // Validate dates
        if (strtotime($data['expiration_date']) <= strtotime($data['production_date'])) {
            $errors['expiration_date'][] = 'La fecha de caducidad debe ser posterior a la fecha de producción';
        }
        
        if (empty($errors)) {
            try {
                $this->batchModel->update($batch_id, $data);
                
                // If quality status changed, record it
                if ($data['quality_status'] !== $current_batch['quality_status']) {
                    $this->batchModel->updateQualityStatus($batch_id, $data['quality_status'], $data['notes'], $this->user_id);
                }
                
                $this->logActivity('UPDATE', 'production_batches', $batch_id, $current_batch, $data);
                $this->redirect('production/view_batch/' . $batch_id, 'Lote actualizado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al actualizar el lote: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->edit_batch($batch_id);
    }
    
    public function inventory() {
        $products_with_stock = $this->productModel->getProductsWithStock();
        $low_stock = $this->productModel->getLowStockProducts();
        $expiring = $this->productModel->getExpiringProducts();
        
        $data = [
            'page_title' => 'Inventario',
            'products' => $products_with_stock,
            'low_stock' => $low_stock,
            'expiring' => $expiring,
            'stats' => $this->getInventoryStats()
        ];
        
        $this->loadView('production/inventory', $data);
    }
    
    public function alerts() {
        $low_stock = $this->productModel->getLowStockProducts();
        $expiring = $this->batchModel->getExpiringBatches();
        
        $data = [
            'page_title' => 'Alertas de Producción',
            'low_stock' => $low_stock,
            'expiring' => $expiring
        ];
        
        $this->loadView('production/alerts', $data);
    }
    
    private function getProductionStats() {
        $stats = [];
        
        // Today's production
        $sql = "SELECT COUNT(*) as batches_today, COALESCE(SUM(quantity_produced), 0) as quantity_today
                FROM production_batches WHERE DATE(production_date) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['today'] = $result;
        
        // This week's production
        $sql = "SELECT COUNT(*) as batches_week, COALESCE(SUM(quantity_produced), 0) as quantity_week
                FROM production_batches WHERE YEARWEEK(production_date) = YEARWEEK(CURDATE())";
        $result = $this->db->fetchOne($sql);
        $stats['week'] = $result;
        
        // Active batches
        $sql = "SELECT COUNT(*) as active_batches, COALESCE(SUM(quantity_available), 0) as total_available
                FROM production_batches WHERE quantity_available > 0 AND quality_status = 'good'";
        $result = $this->db->fetchOne($sql);
        $stats['active'] = $result;
        
        return $stats;
    }
    
    private function getInventoryStats() {
        $stats = [];
        
        // Total products
        $stats['total_products'] = $this->productModel->count(['is_active' => 1]);
        
        // Total stock value
        $sql = "SELECT COALESCE(SUM(pb.quantity_available * p.price), 0) as total_value
                FROM production_batches pb
                JOIN products p ON pb.product_id = p.id
                WHERE pb.quantity_available > 0 AND pb.quality_status = 'good'";
        $result = $this->db->fetchOne($sql);
        $stats['total_value'] = $result['total_value'];
        
        // Low stock count
        $stats['low_stock_count'] = count($this->productModel->getLowStockProducts());
        
        // Expiring count
        $stats['expiring_count'] = count($this->batchModel->getExpiringBatches());
        
        return $stats;
    }
}
?>