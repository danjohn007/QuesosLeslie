<?php
/**
 * Sales Controller
 * Sistema de Logística de Entregas Quesos y Productos Leslie
 */

require_once 'controllers/BaseController.php';
require_once 'models/Sale.php';
require_once 'models/Customer.php';
require_once 'models/Product.php';

class SalesController extends BaseController {
    
    private $saleModel;
    private $customerModel;
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->saleModel = new Sale();
        $this->customerModel = new Customer();
        $this->productModel = new Product();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filter_customer = isset($_GET['customer']) ? $_GET['customer'] : '';
        $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
        
        $conditions = [];
        if ($filter_status) {
            $conditions['payment_status'] = $filter_status;
        }
        if ($filter_customer) {
            $conditions['customer_id'] = $filter_customer;
        }
        if ($filter_type) {
            $conditions['sale_type'] = $filter_type;
        }
        
        $sales_data = $this->saleModel->paginate($page, ITEMS_PER_PAGE, $conditions);
        $sales = $this->saleModel->getSalesWithCustomers($conditions);
        
        $data = [
            'page_title' => 'Gestión de Ventas',
            'sales' => $sales,
            'customers' => $this->customerModel->getActiveCustomers(),
            'filter_status' => $filter_status,
            'filter_customer' => $filter_customer,
            'filter_type' => $filter_type,
            'pagination' => $sales_data['pagination'],
            'stats' => $this->saleModel->getSalesStats()
        ];
        
        $this->loadView('sales/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateSale();
        } else {
            $data = [
                'page_title' => 'Nueva Venta',
                'customers' => $this->customerModel->getActiveCustomers(),
                'products' => $this->productModel->getProductsWithStock()
            ];
            
            $this->loadView('sales/create', $data);
        }
    }
    
    private function processCreateSale() {
        $sale_data = [
            'customer_id' => intval($_POST['customer_id'] ?? 0),
            'sale_date' => $_POST['sale_date'] ?? date('Y-m-d'),
            'sale_type' => $_POST['sale_type'] ?? 'direct',
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $this->user_id
        ];
        
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item_data) {
                if (!empty($item_data['product_id']) && !empty($item_data['quantity']) && !empty($item_data['price'])) {
                    $items[] = [
                        'product_id' => intval($item_data['product_id']),
                        'batch_id' => !empty($item_data['batch_id']) ? intval($item_data['batch_id']) : null,
                        'quantity' => floatval($item_data['quantity']),
                        'unit_price' => floatval($item_data['price'])
                    ];
                }
            }
        }
        
        // Validation
        $errors = $this->validateInput($sale_data, [
            'customer_id' => 'required|numeric',
            'sale_date' => 'required',
            'sale_type' => 'required',
            'payment_method' => 'required'
        ]);
        
        // Validate customer exists
        if (!$this->customerModel->findById($sale_data['customer_id'])) {
            $errors['customer_id'][] = 'Cliente no encontrado';
        }
        
        // Validate items
        if (empty($items)) {
            $errors['items'][] = 'Debe agregar al menos un producto a la venta';
        }
        
        // Check immediate payment
        $immediate_payment = floatval($_POST['immediate_payment'] ?? 0);
        if ($immediate_payment > 0) {
            $sale_data['paid_amount'] = $immediate_payment;
            $sale_data['payment_status'] = 'partial'; // Will be updated after calculating total
        }
        
        if (empty($errors)) {
            try {
                $sale_id = $this->saleModel->createSaleWithItems($sale_data, $items);
                
                // Add immediate payment if specified
                if ($immediate_payment > 0) {
                    $payment_data = [
                        'payment_date' => $sale_data['sale_date'],
                        'payment_method' => $sale_data['payment_method'],
                        'amount' => $immediate_payment,
                        'reference' => trim($_POST['payment_reference'] ?? ''),
                        'notes' => 'Pago inmediato al momento de la venta',
                        'created_by' => $this->user_id
                    ];
                    
                    $this->saleModel->addPayment($sale_id, $payment_data);
                }
                
                $this->logActivity('CREATE', 'sales', $sale_id, null, $sale_data);
                $this->redirect('sales/view/' . $sale_id, 'Venta registrada exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al registrar la venta: ' . $e->getMessage(), 'error');
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
    
    public function view($sale_id) {
        $sale = $this->saleModel->getSaleWithDetails($sale_id);
        if (!$sale) {
            $this->showError('Venta no encontrada', 'La venta solicitada no existe.', 404);
            return;
        }
        
        $data = [
            'page_title' => 'Venta ' . $sale['sale_number'],
            'sale' => $sale
        ];
        
        $this->loadView('sales/view', $data);
    }
    
    public function add_payment($sale_id) {
        $sale = $this->saleModel->getSaleWithDetails($sale_id);
        if (!$sale) {
            $this->showError('Venta no encontrada', 'La venta solicitada no existe.', 404);
            return;
        }
        
        if ($sale['payment_status'] === 'paid') {
            $this->redirect('sales/view/' . $sale_id, 'Esta venta ya está pagada completamente', 'info');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAddPayment($sale_id, $sale);
        } else {
            $pending_amount = $sale['total_amount'] - $sale['paid_amount'];
            
            $data = [
                'page_title' => 'Agregar Pago - ' . $sale['sale_number'],
                'sale' => $sale,
                'pending_amount' => $pending_amount
            ];
            
            $this->loadView('sales/add_payment', $data);
        }
    }
    
    private function processAddPayment($sale_id, $sale) {
        $payment_data = [
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'amount' => floatval($_POST['amount'] ?? 0),
            'reference' => trim($_POST['reference'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $this->user_id
        ];
        
        // Validation
        $errors = $this->validateInput($payment_data, [
            'payment_date' => 'required',
            'payment_method' => 'required',
            'amount' => 'required|numeric'
        ]);
        
        $pending_amount = $sale['total_amount'] - $sale['paid_amount'];
        
        if ($payment_data['amount'] <= 0) {
            $errors['amount'][] = 'El monto debe ser mayor a cero';
        } elseif ($payment_data['amount'] > $pending_amount) {
            $errors['amount'][] = 'El monto no puede ser mayor al saldo pendiente';
        }
        
        if (empty($errors)) {
            try {
                $this->saleModel->addPayment($sale_id, $payment_data);
                $this->logActivity('ADD_PAYMENT', 'sales', $sale_id, null, $payment_data);
                $this->redirect('sales/view/' . $sale_id, 'Pago agregado exitosamente', 'success');
            } catch (Exception $e) {
                $this->setFlashMessage('Error al agregar el pago: ' . $e->getMessage(), 'error');
            }
        } else {
            $error_messages = [];
            foreach ($errors as $field => $field_errors) {
                $error_messages = array_merge($error_messages, $field_errors);
            }
            $this->setFlashMessage(implode('<br>', $error_messages), 'error');
        }
        
        $this->add_payment($sale_id);
    }
    
    public function mark_paid($sale_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $reference = trim($_POST['reference'] ?? '');
            
            try {
                $this->saleModel->markAsPaid($sale_id, $payment_method, $reference, $this->user_id);
                $this->logActivity('MARK_PAID', 'sales', $sale_id);
                $this->redirect('sales/view/' . $sale_id, 'Venta marcada como pagada', 'success');
            } catch (Exception $e) {
                $this->redirect('sales/view/' . $sale_id, 'Error al marcar como pagada: ' . $e->getMessage(), 'error');
            }
        } else {
            $sale = $this->saleModel->getSaleWithDetails($sale_id);
            if (!$sale) {
                $this->showError('Venta no encontrada', 'La venta solicitada no existe.', 404);
                return;
            }
            
            $pending_amount = $sale['total_amount'] - $sale['paid_amount'];
            
            $data = [
                'page_title' => 'Marcar como Pagada',
                'sale' => $sale,
                'pending_amount' => $pending_amount
            ];
            
            $this->loadView('sales/mark_paid', $data);
        }
    }
    
    public function pending() {
        $sales = $this->saleModel->getPendingPayments();
        
        $data = [
            'page_title' => 'Ventas Pendientes de Pago',
            'sales' => $sales,
            'show_payment_actions' => true
        ];
        
        $this->loadView('sales/list', $data);
    }
    
    public function partial() {
        $sales = $this->saleModel->getPartialPayments();
        
        $data = [
            'page_title' => 'Ventas con Pago Parcial',
            'sales' => $sales,
            'show_payment_actions' => true
        ];
        
        $this->loadView('sales/list', $data);
    }
    
    public function reports() {
        $this->requireRole(['admin', 'manager']);
        
        $period = $_GET['period'] ?? 'daily';
        $start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
        $end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
        
        // Sales by period
        $sales_report = $this->saleModel->getSalesReportByPeriod($period);
        
        // Top selling products
        $top_products = $this->saleModel->getTopSellingProducts(10, $start_date, $end_date);
        
        // Sales by date range
        $sales_by_date = $this->saleModel->getSalesByDateRange($start_date, $end_date);
        
        $data = [
            'page_title' => 'Reportes de Ventas',
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'sales_report' => $sales_report,
            'top_products' => $top_products,
            'sales_by_date' => $sales_by_date,
            'stats' => $this->saleModel->getSalesStats()
        ];
        
        $this->loadView('sales/reports', $data);
    }
    
    public function print_sale($sale_id) {
        $sale = $this->saleModel->getSaleWithDetails($sale_id);
        if (!$sale) {
            $this->showError('Venta no encontrada', 'La venta solicitada no existe.', 404);
            return;
        }
        
        $data = [
            'page_title' => 'Imprimir Venta',
            'sale' => $sale,
            'print_mode' => true
        ];
        
        $this->loadView('sales/print', $data);
    }
    
    public function receipt($sale_id) {
        $sale = $this->saleModel->getSaleWithDetails($sale_id);
        if (!$sale) {
            $this->showError('Venta no encontrada', 'La venta solicitada no existe.', 404);
            return;
        }
        
        $data = [
            'page_title' => 'Recibo de Venta',
            'sale' => $sale,
            'print_mode' => true
        ];
        
        $this->loadView('sales/receipt', $data);
    }
    
    // API endpoints
    public function api_daily_sales() {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $sql = "SELECT s.*, c.name as customer_name
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                WHERE DATE(s.sale_date) = ?
                ORDER BY s.created_at DESC";
        
        $sales = $this->db->fetchAll($sql, [$date]);
        
        $this->jsonResponse([
            'date' => $date,
            'sales' => $sales,
            'total_count' => count($sales),
            'total_amount' => array_sum(array_column($sales, 'total_amount'))
        ]);
    }
    
    public function api_sales_chart() {
        $period = $_GET['period'] ?? 'daily';
        $limit = intval($_GET['limit'] ?? 30);
        
        $report = $this->saleModel->getSalesReportByPeriod($period, $limit);
        
        $labels = array_column($report, 'period');
        $revenue_data = array_column($report, 'total_revenue');
        $count_data = array_column($report, 'sales_count');
        
        $this->jsonResponse([
            'labels' => array_reverse($labels),
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => array_reverse($revenue_data),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Número de Ventas',
                    'data' => array_reverse($count_data),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1'
                ]
            ]
        ]);
    }
}
?>