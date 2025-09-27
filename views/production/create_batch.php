<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle"></i> Nuevo Lote de Producción
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_id" class="form-label">Producto *</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-unit="<?php echo $product['unit_measure']; ?>"
                                        data-shelf-life="<?php echo $product['shelf_life_days']; ?>">
                                    [<?php echo htmlspecialchars($product['code']); ?>] <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor selecciona un producto.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="production_date" class="form-label">Fecha de Producción *</label>
                            <input type="date" class="form-control" id="production_date" name="production_date" 
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback">
                                Por favor ingresa una fecha de producción válida.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity_produced" class="form-label">Cantidad Producida *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantity_produced" name="quantity_produced" 
                                       step="0.01" min="0.01" placeholder="0.00" required>
                                <span class="input-group-text" id="unit-display">kg</span>
                            </div>
                            <div class="invalid-feedback">
                                Por favor ingresa una cantidad válida mayor a cero.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="expiration_date" class="form-label">Fecha de Caducidad</label>
                            <input type="date" class="form-control" id="expiration_date" name="expiration_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <small class="form-text text-muted">
                                Se calculará automáticamente basado en la vida útil del producto si se deja vacío.
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Notas adicionales sobre el lote de producción..."></textarea>
                    </div>
                    
                    <!-- Product Info Display -->
                    <div id="product-info" class="alert alert-info" style="display: none;">
                        <h6><i class="bi bi-info-circle"></i> Información del Producto</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Categoría:</strong> <span id="product-category"></span><br>
                                <strong>Tipo de Unidad:</strong> <span id="product-unit-type"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Precio:</strong> $<span id="product-price"></span><br>
                                <strong>Vida Útil:</strong> <span id="product-shelf-life"></span> días
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>production" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Lote
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Guide -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Guía Rápida</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Tipos de Unidad:</h6>
                        <ul class="list-unstyled">
                            <li><strong>A Granel:</strong> Se registra por peso total</li>
                            <li><strong>Por Pieza:</strong> Unidades individuales</li>
                            <li><strong>Por Paquete:</strong> Cajas o empaques predefinidos</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Consejos:</h6>
                        <ul class="list-unstyled">
                            <li>• El código del lote se genera automáticamente</li>
                            <li>• La fecha de caducidad se calcula automáticamente</li>
                            <li>• Revisa la información antes de crear el lote</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const productionDate = document.getElementById('production_date');
    const expirationDate = document.getElementById('expiration_date');
    const unitDisplay = document.getElementById('unit-display');
    const productInfo = document.getElementById('product-info');
    
    // Product data (would be loaded from server in real implementation)
    const productData = {
        <?php foreach ($products as $product): ?>
        '<?php echo $product['id']; ?>': {
            unit_measure: '<?php echo $product['unit_measure']; ?>',
            category: '<?php echo htmlspecialchars($product['category']); ?>',
            unit_type: '<?php echo $product['unit_type']; ?>',
            price: '<?php echo $product['price']; ?>',
            shelf_life_days: <?php echo $product['shelf_life_days']; ?>
        },
        <?php endforeach; ?>
    };
    
    productSelect.addEventListener('change', function() {
        const productId = this.value;
        
        if (productId && productData[productId]) {
            const product = productData[productId];
            
            // Update unit display
            unitDisplay.textContent = product.unit_measure;
            
            // Calculate expiration date
            if (productionDate.value) {
                calculateExpirationDate();
            }
            
            // Show product info
            document.getElementById('product-category').textContent = product.category;
            document.getElementById('product-unit-type').textContent = getUnitTypeDisplay(product.unit_type);
            document.getElementById('product-price').textContent = parseFloat(product.price).toFixed(2);
            document.getElementById('product-shelf-life').textContent = product.shelf_life_days;
            
            productInfo.style.display = 'block';
        } else {
            unitDisplay.textContent = 'kg';
            productInfo.style.display = 'none';
            expirationDate.value = '';
        }
    });
    
    productionDate.addEventListener('change', calculateExpirationDate);
    
    function calculateExpirationDate() {
        const productId = productSelect.value;
        const productionDateValue = productionDate.value;
        
        if (productId && productionDateValue && productData[productId]) {
            const shelfLifeDays = productData[productId].shelf_life_days;
            const prodDate = new Date(productionDateValue);
            const expDate = new Date(prodDate.getTime() + (shelfLifeDays * 24 * 60 * 60 * 1000));
            
            expirationDate.value = expDate.toISOString().split('T')[0];
        }
    }
    
    function getUnitTypeDisplay(unitType) {
        const types = {
            'bulk': 'A Granel',
            'piece': 'Por Pieza',
            'package': 'Por Paquete'
        };
        return types[unitType] || unitType;
    }
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>