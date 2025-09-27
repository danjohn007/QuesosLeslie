-- Sistema de Logística de Entregas Quesos y Productos Leslie
-- Sample Data

USE `quesos_leslie`;

-- Insert sample users
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `phone`, `is_active`) VALUES
('admin', 'admin@quesosleslie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'admin', '555-0001', 1),
('gerente', 'gerente@quesosleslie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María González', 'manager', '555-0002', 1),
('vendedor1', 'vendedor1@quesosleslie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez', 'sales', '555-0003', 1),
('chofer1', 'chofer1@quesosleslie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Rodríguez', 'driver', '555-0004', 1),
('vendedor2', 'vendedor2@quesosleslie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Martínez', 'sales', '555-0005', 1);

-- Insert sample products
INSERT INTO `products` (`code`, `name`, `description`, `category`, `unit_type`, `unit_measure`, `price`, `shelf_life_days`, `is_active`) VALUES
('Q001', 'Queso Oaxaca', 'Queso Oaxaca tradicional', 'Quesos', 'piece', 'kg', 120.00, 15, 1),
('Q002', 'Queso Panela', 'Queso Panela fresco', 'Quesos', 'piece', 'kg', 80.00, 10, 1),
('Q003', 'Queso Manchego', 'Queso Manchego curado', 'Quesos', 'piece', 'kg', 150.00, 30, 1),
('Q004', 'Crema Ácida', 'Crema ácida natural', 'Lácteos', 'package', 'lt', 45.00, 7, 1),
('Q005', 'Requesón', 'Requesón fresco', 'Lácteos', 'bulk', 'kg', 60.00, 5, 1),
('Q006', 'Queso Fresco', 'Queso fresco del día', 'Quesos', 'piece', 'kg', 90.00, 8, 1),
('Q007', 'Queso Doble Crema', 'Queso doble crema premium', 'Quesos', 'piece', 'kg', 180.00, 20, 1),
('Q008', 'Suero Dulce', 'Suero dulce natural', 'Subproductos', 'bulk', 'lt', 25.00, 3, 1);

-- Insert sample customers
INSERT INTO `customers` (`code`, `name`, `contact_person`, `phone`, `email`, `address`, `location_lat`, `location_lng`, `customer_type`, `credit_limit`, `payment_terms`, `is_active`) VALUES
('C001', 'Tienda La Esquina', 'Roberto Sánchez', '555-1001', 'robertoesquina@email.com', 'Av. Principal 123, Centro', 19.4326, -99.1332, 'retail', 5000.00, 15, 1),
('C002', 'Supermercado Mi Pueblo', 'Laura García', '555-1002', 'laura@mipueblo.com', 'Calle Juárez 456, Norte', 19.4512, -99.1253, 'wholesale', 15000.00, 30, 1),
('C003', 'Restaurante El Buen Sabor', 'Miguel Torres', '555-1003', 'miguel@buensabor.com', 'Plaza Central 789, Sur', 19.4123, -99.1445, 'regular', 8000.00, 0, 1),
('C004', 'Carnicería Don Juan', 'Juan López', '555-1004', 'juan@donjuan.com', 'Mercado Municipal Local 12', 19.4267, -99.1287, 'retail', 3000.00, 7, 1),
('C005', 'Hotel Las Flores', 'Carmen Ruiz', '555-1005', 'carmen@lasflores.com', 'Zona Hotelera 321', 19.4445, -99.1156, 'regular', 12000.00, 15, 1),
('C006', 'Quesería Familiar', 'Pedro Morales', '555-1006', 'pedro@familiar.com', 'Barrio Tradicional 654', 19.4189, -99.1398, 'wholesale', 10000.00, 30, 1);

-- Insert sample production batches
INSERT INTO `production_batches` (`batch_code`, `product_id`, `production_date`, `expiration_date`, `quantity_produced`, `quantity_available`, `quantity_assigned`, `quality_status`, `notes`, `created_by`) VALUES
('LOTE-Q001-240901', 1, '2024-09-01', '2024-09-16', 50.00, 35.00, 15.00, 'good', 'Lote de producción matutina', 1),
('LOTE-Q002-240901', 2, '2024-09-01', '2024-09-11', 30.00, 20.00, 10.00, 'good', 'Lote de producción vespertina', 1),
('LOTE-Q003-240902', 3, '2024-09-02', '2024-10-02', 25.00, 25.00, 0.00, 'good', 'Lote especial fin de semana', 1),
('LOTE-Q004-240903', 4, '2024-09-03', '2024-09-10', 40.00, 32.00, 8.00, 'good', 'Lote semanal crema', 1),
('LOTE-Q005-240903', 5, '2024-09-03', '2024-09-08', 35.00, 28.00, 7.00, 'warning', 'Próximo a caducar', 1),
('LOTE-Q006-240904', 6, '2024-09-04', '2024-09-12', 45.00, 40.00, 5.00, 'good', 'Lote fresco diario', 1),
('LOTE-Q001-240902', 1, '2024-09-02', '2024-09-17', 55.00, 48.00, 7.00, 'good', 'Segundo lote Oaxaca', 1),
('LOTE-Q007-240905', 7, '2024-09-05', '2024-09-25', 20.00, 18.00, 2.00, 'good', 'Lote premium doble crema', 1);

-- Insert sample orders
INSERT INTO `orders` (`order_number`, `customer_id`, `order_date`, `delivery_date`, `status`, `order_type`, `total_amount`, `notes`, `qr_code`, `created_by`) VALUES
('ORD-2024-0001', 1, '2024-09-05', '2024-09-06', 'confirmed', 'presale', 960.00, 'Pedido semanal regular', 'QR-ORD-2024-0001', 3),
('ORD-2024-0002', 2, '2024-09-05', '2024-09-07', 'in_route', 'presale', 1800.00, 'Pedido mayoreo mensual', 'QR-ORD-2024-0002', 3),
('ORD-2024-0003', 3, '2024-09-06', '2024-09-06', 'pending', 'presale', 720.00, 'Pedido restaurante', 'QR-ORD-2024-0003', 5),
('ORD-2024-0004', 4, '2024-09-06', '2024-09-07', 'confirmed', 'presale', 480.00, 'Pedido carnicería', 'QR-ORD-2024-0004', 3),
('ORD-2024-0005', 5, '2024-09-07', '2024-09-08', 'pending', 'presale', 1440.00, 'Pedido hotel fin de semana', 'QR-ORD-2024-0005', 5);

-- Insert sample order items
INSERT INTO `order_items` (`order_id`, `product_id`, `batch_id`, `quantity_ordered`, `quantity_delivered`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 4.00, 4.00, 120.00, 480.00),
(1, 2, 2, 6.00, 6.00, 80.00, 480.00),
(2, 1, 1, 8.00, 8.00, 120.00, 960.00),
(2, 3, 3, 6.00, 0.00, 150.00, 900.00),
(3, 6, 6, 8.00, 0.00, 90.00, 720.00),
(4, 2, 2, 4.00, 0.00, 80.00, 320.00),
(4, 4, 4, 4.00, 0.00, 45.00, 180.00),
(5, 7, 8, 8.00, 0.00, 180.00, 1440.00);

-- Insert sample routes
INSERT INTO `routes` (`route_name`, `route_date`, `driver_id`, `vehicle`, `status`, `start_time`, `end_time`, `total_orders`, `completed_orders`, `notes`, `created_by`) VALUES
('Ruta Centro', '2024-09-06', 4, 'Camión 001', 'in_progress', '08:00:00', NULL, 2, 1, 'Ruta matutina zona centro', 2),
('Ruta Norte', '2024-09-07', 4, 'Camión 001', 'planned', '09:00:00', NULL, 2, 0, 'Ruta zona norte', 2),
('Ruta Sur-Este', '2024-09-08', 4, 'Camión 002', 'planned', '08:30:00', NULL, 1, 0, 'Ruta combinada sur-este', 2);

-- Insert sample route orders
INSERT INTO `route_orders` (`route_id`, `order_id`, `sequence_order`, `estimated_time`, `actual_time`, `delivery_status`, `delivery_notes`) VALUES
(1, 1, 1, '08:30:00', '08:35:00', 'delivered', 'Entrega exitosa, cliente satisfecho'),
(1, 3, 2, '09:15:00', NULL, 'pending', NULL),
(2, 2, 1, '09:30:00', NULL, 'pending', NULL),
(2, 4, 2, '10:15:00', NULL, 'pending', NULL),
(3, 5, 1, '09:00:00', NULL, 'pending', NULL);

-- Insert sample sales
INSERT INTO `sales` (`sale_number`, `order_id`, `customer_id`, `sale_date`, `sale_type`, `total_amount`, `payment_method`, `payment_status`, `paid_amount`, `qr_code`, `created_by`) VALUES
('VNT-2024-0001', 1, 1, '2024-09-06', 'presale', 960.00, 'cash', 'paid', 960.00, 'QR-VNT-2024-0001', 4),
('VNT-2024-0002', NULL, 2, '2024-09-06', 'direct', 300.00, 'transfer', 'paid', 300.00, 'QR-VNT-2024-0002', 4),
('VNT-2024-0003', NULL, 3, '2024-09-06', 'route', 180.00, 'cash', 'paid', 180.00, 'QR-VNT-2024-0003', 4);

-- Insert sample sale items
INSERT INTO `sale_items` (`sale_id`, `product_id`, `batch_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 4.00, 120.00, 480.00),
(1, 2, 2, 6.00, 80.00, 480.00),
(2, 6, 6, 3.00, 90.00, 270.00),
(2, 4, 4, 1.00, 45.00, 45.00),
(3, 2, 2, 2.00, 80.00, 160.00),
(3, 5, 5, 1.00, 60.00, 60.00);

-- Insert sample payments
INSERT INTO `payments` (`sale_id`, `payment_date`, `payment_method`, `amount`, `reference`, `created_by`) VALUES
(1, '2024-09-06', 'cash', 960.00, 'Efectivo entrega', 4),
(2, '2024-09-06', 'transfer', 300.00, 'TRANS-20240906-001', 4),
(3, '2024-09-06', 'cash', 180.00, 'Efectivo ruta', 4);

-- Insert sample returns
INSERT INTO `returns` (`return_number`, `order_id`, `customer_id`, `return_date`, `return_type`, `total_amount`, `status`, `resolution`, `notes`, `created_by`) VALUES
('DEV-2024-0001', NULL, 1, '2024-09-05', 'quality_issue', 120.00, 'approved', 'refund', 'Producto con defecto de calidad', 3);

-- Insert sample return items
INSERT INTO `return_items` (`return_id`, `product_id`, `batch_id`, `quantity`, `unit_price`, `subtotal`, `quality_status`, `resolution`) VALUES
(1, 1, 1, 1.00, 120.00, 120.00, 'damaged', 'waste');

-- Insert sample customer feedback
INSERT INTO `customer_feedback` (`customer_id`, `order_id`, `sale_id`, `rating`, `feedback_type`, `comments`, `feedback_channel`) VALUES
(1, 1, 1, 5, 'delivery', 'Excelente servicio, entrega puntual y producto fresco', 'web'),
(2, NULL, 2, 4, 'product_quality', 'Buen producto, pero el empaque podría mejorar', 'whatsapp'),
(3, NULL, 3, 5, 'service', 'Muy buen trato del vendedor, seguiré comprando', 'in_person');

-- Insert sample notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `reference_id`, `reference_type`, `priority`) VALUES
(1, 'expiration_alert', 'Lote próximo a caducar', 'El lote LOTE-Q005-240903 de Requesón caduca en 2 días', 5, 'production_batches', 'high'),
(2, 'delivery_reminder', 'Entregas pendientes', 'Tienes 3 entregas programadas para mañana', 2, 'routes', 'medium'),
(3, 'low_stock', 'Stock bajo', 'El producto Queso Panela tiene menos de 10 kg disponibles', 2, 'products', 'medium');

-- Insert sample inventory movements
INSERT INTO `inventory_movements` (`batch_id`, `movement_type`, `quantity`, `reference_id`, `reference_type`, `notes`, `created_by`) VALUES
(1, 'production', 50.00, NULL, NULL, 'Producción inicial del lote', 1),
(1, 'assignment', -15.00, 1, 'order', 'Asignación a pedido ORD-2024-0001', 3),
(2, 'production', 30.00, NULL, NULL, 'Producción inicial del lote', 1),
(2, 'assignment', -10.00, 1, 'order', 'Asignación a pedido ORD-2024-0001', 3),
(1, 'sale', -4.00, 1, 'sale', 'Venta VNT-2024-0001', 4),
(2, 'sale', -6.00, 1, 'sale', 'Venta VNT-2024-0001', 4);

-- Insert sample system logs
INSERT INTO `system_logs` (`user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`) VALUES
(1, 'CREATE', 'production_batches', 1, NULL, '{"batch_code":"LOTE-Q001-240901","product_id":1,"quantity_produced":50.00}', '192.168.1.100', 'Mozilla/5.0'),
(3, 'CREATE', 'orders', 1, NULL, '{"order_number":"ORD-2024-0001","customer_id":1,"total_amount":960.00}', '192.168.1.101', 'Mozilla/5.0'),
(4, 'UPDATE', 'orders', 1, '{"status":"confirmed"}', '{"status":"in_route"}', '192.168.1.102', 'Mozilla/5.0');