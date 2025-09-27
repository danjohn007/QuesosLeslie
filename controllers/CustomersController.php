<?php
/**
 * Customers Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';
require_once 'models/Customer.php';

class CustomersController extends BaseController {
    
    private $customerModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerModel = new Customer();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if ($search) {
            $customers_data = ['data' => $this->customerModel->searchCustomers($search), 'pagination' => ['total_pages' => 1]];
        } else {
            $conditions = ['is_active' => 1];
            if ($filter_type) {
                $conditions['customer_type'] = $filter_type;
            }
            
            $customers_data = $this->customerModel->paginate($page, ITEMS_PER_PAGE, $conditions);
        }
        
        $customers_with_stats = $this->customerModel->getCustomersWithStats();
        
        $data = [
            'page_title' => 'Gestión de Clientes',
            'customers' => $customers_with_stats,
            'filter_type' => $filter_type,
            'search' => $search,
            'pagination' => $customers_data['pagination'],
            'customer_types' => $this->customerModel->getCustomerTypes(),
            'stats' => $this->getCustomerStats()
        ];
        
        $this->loadView('customers/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateCustomer();
        } else {
            $data = [
                'page_title' => 'Nuevo Cliente',
                'customer_types' => $this->customerModel->getCustomerTypes()
            ];
            
            $this->loadView('customers/create', $data);
        }
    }
    
    private function processCreateCustomer() {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'customer_type' => $_POST['customer_type'] ?? 'regular',
            'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
            'payment_terms' => intval($_POST['payment_terms'] ?? 0)
        ];
        
        // Generate customer code
        $data['code'] = $this->customerModel->generateCustomerCode($data['name']);
        
        // Validation
        $errors = $this->validateInput($data, [
            'name' => 'required|max:100',
            'address' => 'required',
            'customer_type' => 'required'
        ]);
        
        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'El email debe ser válido';
        }
        
        // Check if customer name exists
        if ($this->customerModel->exists(['name' => $data['name']])) {
            $errors['name'][] = 'Ya existe un cliente con este nombre';
        }
        
        if (empty($errors)) {
            try {
                $customer_id = $this->customerModel->create($data);
                $this->logActivity('CREATE', 'customers', $customer_id, null, $data);
                $this->redirect('customers/view/' . $customer_id, 'Cliente creado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al crear el cliente: ' . $e->getMessage(), 'error');
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
    
    public function view($customer_id) {
        $customer = $this->customerModel->getCustomerWithHistory($customer_id);
        if (!$customer) {
            $this->showError('Cliente no encontrado', 'El cliente solicitado no existe.', 404);
            return;
        }
        
        $stats = $this->customerModel->getCustomerStats($customer_id);
        $credit_balance = $this->customerModel->getCreditBalance($customer_id);
        
        $data = [
            'page_title' => 'Cliente: ' . $customer['name'],
            'customer' => $customer,
            'stats' => $stats,
            'credit_balance' => $credit_balance
        ];
        
        $this->loadView('customers/view', $data);
    }
    
    public function edit($customer_id) {
        $customer = $this->customerModel->findById($customer_id);
        if (!$customer) {
            $this->showError('Cliente no encontrado', 'El cliente solicitado no existe.', 404);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processEditCustomer($customer_id, $customer);
        } else {
            $data = [
                'page_title' => 'Editar Cliente: ' . $customer['name'],
                'customer' => $customer,
                'customer_types' => $this->customerModel->getCustomerTypes()
            ];
            
            $this->loadView('customers/edit', $data);
        }
    }
    
    private function processEditCustomer($customer_id, $current_customer) {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'customer_type' => $_POST['customer_type'] ?? 'regular',
            'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
            'payment_terms' => intval($_POST['payment_terms'] ?? 0)
        ];
        
        // Validation
        $errors = $this->validateInput($data, [
            'name' => 'required|max:100',
            'address' => 'required',
            'customer_type' => 'required'
        ]);
        
        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'El email debe ser válido';
        }
        
        // Check if customer name exists for another customer
        $existing_customer = $this->customerModel->findOne(['name' => $data['name']]);
        if ($existing_customer && $existing_customer['id'] != $customer_id) {
            $errors['name'][] = 'Ya existe otro cliente con este nombre';
        }
        
        if (empty($errors)) {
            try {
                $this->customerModel->update($customer_id, $data);
                $this->logActivity('UPDATE', 'customers', $customer_id, $current_customer, $data);
                $this->redirect('customers/view/' . $customer_id, 'Cliente actualizado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al actualizar el cliente: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->edit($customer_id);
    }
    
    public function deactivate($customer_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->customerModel->deactivateCustomer($customer_id);
                $this->logActivity('DEACTIVATE', 'customers', $customer_id);
                $this->redirect('customers', 'Cliente desactivado exitosamente', 'success');
            } catch (Exception $e) {
                $this->redirect('customers/view/' . $customer_id, 'Error al desactivar el cliente: ' . $e->getMessage(), 'error');
            }
        } else {
            $customer = $this->customerModel->findById($customer_id);
            if (!$customer) {
                $this->showError('Cliente no encontrado', 'El cliente solicitado no existe.', 404);
                return;
            }
            
            $data = [
                'page_title' => 'Desactivar Cliente',
                'customer' => $customer
            ];
            
            $this->loadView('customers/deactivate', $data);
        }
    }
    
    public function activate($customer_id) {
        try {
            $this->customerModel->activateCustomer($customer_id);
            $this->logActivity('ACTIVATE', 'customers', $customer_id);
            $this->redirect('customers/view/' . $customer_id, 'Cliente activado exitosamente', 'success');
        } catch (Exception $e) {
            $this->redirect('customers', 'Error al activar el cliente: ' . $e->getMessage(), 'error');
        }
    }
    
    public function top() {
        $top_customers = $this->customerModel->getTopCustomers();
        
        $data = [
            'page_title' => 'Mejores Clientes',
            'customers' => $top_customers
        ];
        
        $this->loadView('customers/top', $data);
    }
    
    public function search() {
        $query = trim($_GET['q'] ?? '');
        $results = [];
        
        if (strlen($query) >= 2) {
            $results = $this->customerModel->searchCustomers($query);
        }
        
        $this->jsonResponse([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    public function credit_report() {
        $this->requireRole(['admin', 'manager']);
        
        $sql = "SELECT c.*, 
                       COALESCE(SUM(CASE WHEN s.payment_status != 'paid' THEN s.total_amount - s.paid_amount ELSE 0 END), 0) as pending_amount,
                       (c.credit_limit - COALESCE(SUM(CASE WHEN s.payment_status != 'paid' THEN s.total_amount - s.paid_amount ELSE 0 END), 0)) as available_credit
                FROM customers c
                LEFT JOIN sales s ON c.id = s.customer_id
                WHERE c.is_active = 1 AND c.credit_limit > 0
                GROUP BY c.id
                ORDER BY pending_amount DESC";
        
        $customers_credit = $this->db->fetchAll($sql);
        
        $data = [
            'page_title' => 'Reporte de Créditos',
            'customers' => $customers_credit
        ];
        
        $this->loadView('customers/credit_report', $data);
    }
    
    // API endpoints
    public function api_get_customer($customer_id) {
        $customer = $this->customerModel->findById($customer_id);
        if ($customer) {
            $credit_balance = $this->customerModel->getCreditBalance($customer_id);
            $customer['credit_balance'] = $credit_balance;
        }
        
        $this->jsonResponse($customer ?: ['error' => 'Customer not found']);
    }
    
    public function api_search() {
        $query = trim($_GET['q'] ?? '');
        $results = [];
        
        if (strlen($query) >= 2) {
            $results = $this->customerModel->searchCustomers($query, 10);
            // Format for select2 or similar
            $formatted = array_map(function($customer) {
                return [
                    'id' => $customer['id'],
                    'text' => $customer['name'] . ' (' . $customer['code'] . ')',
                    'data' => $customer
                ];
            }, $results);
            
            $this->jsonResponse(['results' => $formatted]);
        } else {
            $this->jsonResponse(['results' => []]);
        }
    }
    
    private function getCustomerStats() {
        $stats = [];
        
        // Total active customers
        $stats['total_active'] = $this->customerModel->count(['is_active' => 1]);
        
        // New customers this month
        $sql = "SELECT COUNT(*) as count FROM customers 
                WHERE is_active = 1 AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $result = $this->db->fetchOne($sql);
        $stats['new_this_month'] = $result['count'];
        
        // Customers with pending payments
        $sql = "SELECT COUNT(DISTINCT c.id) as count
                FROM customers c
                JOIN sales s ON c.id = s.customer_id
                WHERE c.is_active = 1 AND s.payment_status != 'paid'";
        $result = $this->db->fetchOne($sql);
        $stats['with_pending_payments'] = $result['count'];
        
        // By customer type
        $sql = "SELECT customer_type, COUNT(*) as count FROM customers WHERE is_active = 1 GROUP BY customer_type";
        $results = $this->db->fetchAll($sql);
        $stats['by_type'] = [];
        foreach ($results as $result) {
            $stats['by_type'][$result['customer_type']] = $result['count'];
        }
        
        return $stats;
    }
}
?>