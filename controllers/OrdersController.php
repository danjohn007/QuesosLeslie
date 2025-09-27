<?php
/**
 * Orders Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';
require_once 'models/Order.php';
require_once 'models/Customer.php';
require_once 'models/Product.php';

class OrdersController extends BaseController {
    
    private $orderModel;
    private $customerModel;
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->customerModel = new Customer();
        $this->productModel = new Product();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filter_customer = isset($_GET['customer']) ? $_GET['customer'] : '';
        
        $conditions = [];
        if ($filter_status) {
            $conditions['status'] = $filter_status;
        }
        if ($filter_customer) {
            $conditions['customer_id'] = $filter_customer;
        }
        
        $orders_data = $this->orderModel->paginate($page, ITEMS_PER_PAGE, $conditions);
        $orders = $this->orderModel->getOrdersWithCustomers($conditions);
        
        $data = [
            'page_title' => 'Gestión de Pedidos',
            'orders' => $orders,
            'customers' => $this->customerModel->getActiveCustomers(),
            'filter_status' => $filter_status,
            'filter_customer' => $filter_customer,
            'pagination' => $orders_data['pagination'],
            'stats' => $this->orderModel->getOrderStats()
        ];
        
        $this->loadView('orders/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateOrder();
        } else {
            $data = [
                'page_title' => 'Nuevo Pedido',
                'customers' => $this->customerModel->getActiveCustomers(),
                'products' => $this->productModel->getProductsWithStock()
            ];
            
            $this->loadView('orders/create', $data);
        }
    }
    
    private function processCreateOrder() {
        $order_data = [
            'customer_id' => intval($_POST['customer_id'] ?? 0),
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'delivery_date' => $_POST['delivery_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'order_type' => $_POST['order_type'] ?? 'presale',
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $this->user_id
        ];
        
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item_data) {
                if (!empty($item_data['product_id']) && !empty($item_data['quantity']) && !empty($item_data['price'])) {
                    $items[] = [
                        'product_id' => intval($item_data['product_id']),
                        'quantity_ordered' => floatval($item_data['quantity']),
                        'unit_price' => floatval($item_data['price']),
                        'notes' => trim($item_data['notes'] ?? '')
                    ];
                }
            }
        }
        
        // Validation
        $errors = $this->validateInput($order_data, [
            'customer_id' => 'required|numeric',
            'order_date' => 'required',
            'delivery_date' => 'required'
        ]);
        
        // Validate customer exists
        if (!$this->customerModel->findById($order_data['customer_id'])) {
            $errors['customer_id'][] = 'Cliente no encontrado';
        }
        
        // Validate dates
        if (strtotime($order_data['delivery_date']) < strtotime($order_data['order_date'])) {
            $errors['delivery_date'][] = 'La fecha de entrega debe ser igual o posterior a la fecha del pedido';
        }
        
        // Validate items
        if (empty($items)) {
            $errors['items'][] = 'Debe agregar al menos un producto al pedido';
        }
        
        if (empty($errors)) {
            try {
                $order_id = $this->orderModel->createOrderWithItems($order_data, $items);
                $this->logActivity('CREATE', 'orders', $order_id, null, $order_data);
                $this->redirect('orders/view/' . $order_id, 'Pedido creado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al crear el pedido: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->create();
    }
    
    public function view($order_id) {
        $order = $this->orderModel->getOrderWithDetails($order_id);
        if (!$order) {
            $this->showError('Pedido no encontrado', 'El pedido solicitado no existe.', 404);
            return;
        }
        
        $data = [
            'page_title' => 'Pedido ' . $order['order_number'],
            'order' => $order
        ];
        
        $this->loadView('orders/view', $data);
    }
    
    public function edit($order_id) {
        $order = $this->orderModel->getOrderWithDetails($order_id);
        if (!$order) {
            $this->showError('Pedido no encontrado', 'El pedido solicitado no existe.', 404);
            return;
        }
        
        // Only allow editing of pending orders
        if ($order['status'] !== 'pending') {
            $this->showError('Pedido no editable', 'Solo se pueden editar pedidos en estado pendiente.', 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processEditOrder($order_id, $order);
        } else {
            $data = [
                'page_title' => 'Editar Pedido ' . $order['order_number'],
                'order' => $order,
                'customers' => $this->customerModel->getActiveCustomers(),
                'products' => $this->productModel->getProductsWithStock()
            ];
            
            $this->loadView('orders/edit', $data);
        }
    }
    
    private function processEditOrder($order_id, $current_order) {
        $order_data = [
            'customer_id' => intval($_POST['customer_id'] ?? $current_order['customer_id']),
            'delivery_date' => $_POST['delivery_date'] ?? $current_order['delivery_date'],
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        // Validation
        $errors = $this->validateInput($order_data, [
            'customer_id' => 'required|numeric',
            'delivery_date' => 'required'
        ]);
        
        if (empty($errors)) {
            try {
                $this->orderModel->update($order_id, $order_data);
                $this->logActivity('UPDATE', 'orders', $order_id, $current_order, $order_data);
                $this->redirect('orders/view/' . $order_id, 'Pedido actualizado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al actualizar el pedido: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->edit($order_id);
    }
    
    public function confirm($order_id) {
        try {
            $this->orderModel->updateOrderStatus($order_id, 'confirmed', 'Pedido confirmado', $this->user_id);
            $this->logActivity('STATUS_CHANGE', 'orders', $order_id);
            $this->redirect('orders/view/' . $order_id, 'Pedido confirmado exitosamente', 'success');
        } catch (Exception $e) {
            $this->redirect('orders/view/' . $order_id, 'Error al confirmar el pedido: ' . $e->getMessage(), 'error');
        }
    }
    
    public function cancel($order_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notes = trim($_POST['notes'] ?? '');
            try {
                $this->orderModel->updateOrderStatus($order_id, 'cancelled', $notes, $this->user_id);
                $this->logActivity('STATUS_CHANGE', 'orders', $order_id);
                $this->redirect('orders', 'Pedido cancelado exitosamente', 'success');
            } catch (Exception $e) {
                $this->redirect('orders/view/' . $order_id, 'Error al cancelar el pedido: ' . $e->getMessage(), 'error');
            }
        } else {
            $order = $this->orderModel->getOrderWithDetails($order_id);
            if (!$order) {
                $this->showError('Pedido no encontrado', 'El pedido solicitado no existe.', 404);
                return;
            }
            
            $data = [
                'page_title' => 'Cancelar Pedido',
                'order' => $order
            ];
            
            $this->loadView('orders/cancel', $data);
        }
    }
    
    public function pending() {
        $orders = $this->orderModel->getOrdersByStatus('pending');
        
        $data = [
            'page_title' => 'Pedidos Pendientes',
            'orders' => $orders,
            'show_actions' => true
        ];
        
        $this->loadView('orders/list', $data);
    }
    
    public function confirmed() {
        $orders = $this->orderModel->getOrdersByStatus('confirmed');
        
        $data = [
            'page_title' => 'Pedidos Confirmados',
            'orders' => $orders,
            'show_route_actions' => true
        ];
        
        $this->loadView('orders/list', $data);
    }
    
    public function delivered() {
        $orders = $this->orderModel->getOrdersByStatus('delivered');
        
        $data = [
            'page_title' => 'Pedidos Entregados',
            'orders' => $orders
        ];
        
        $this->loadView('orders/list', $data);
    }
    
    public function search() {
        $query = trim($_GET['q'] ?? '');
        $results = [];
        
        if (strlen($query) >= 3) {
            // Search in order numbers and customer names
            $sql = "SELECT o.*, c.name as customer_name 
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    WHERE o.order_number LIKE ? OR c.name LIKE ?
                    ORDER BY o.created_at DESC
                    LIMIT 20";
            
            $search = "%{$query}%";
            $results = $this->db->fetchAll($sql, [$search, $search]);
        }
        
        $this->jsonResponse([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    public function print_order($order_id) {
        $order = $this->orderModel->getOrderWithDetails($order_id);
        if (!$order) {
            $this->showError('Pedido no encontrado', 'El pedido solicitado no existe.', 404);
            return;
        }
        
        $data = [
            'page_title' => 'Imprimir Pedido',
            'order' => $order,
            'print_mode' => true
        ];
        
        $this->loadView('orders/print', $data);
    }
    
    // API endpoints for AJAX requests
    public function api_get_customer_info($customer_id) {
        $customer = $this->customerModel->findById($customer_id);
        if ($customer) {
            $credit_balance = $this->customerModel->getCreditBalance($customer_id);
            $customer['credit_balance'] = $credit_balance;
        }
        
        $this->jsonResponse($customer ?: ['error' => 'Customer not found']);
    }
    
    public function api_get_product_info($product_id) {
        $product = $this->productModel->getProductWithBatches($product_id);
        $this->jsonResponse($product ?: ['error' => 'Product not found']);
    }
}
?>