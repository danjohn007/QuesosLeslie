/**
 * Main JavaScript for Sistema Quesos Leslie
 */

// Global variables
const BASE_URL = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/';

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    initializeEventListeners();
    initializeTooltips();
});

// Initialize components
function initializeComponents() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Initialize event listeners
function initializeEventListeners() {
    // Confirm deletion
    const deleteButtons = document.querySelectorAll('.btn-delete, .delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        });
    }
    
    // Auto-refresh for certain pages
    if (document.body.classList.contains('auto-refresh')) {
        setInterval(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    }
}

// Initialize tooltips
function initializeTooltips() {
    // Add tooltips to buttons without explicit tooltip
    const buttons = document.querySelectorAll('button[title], a[title]');
    buttons.forEach(function(button) {
        if (!button.hasAttribute('data-bs-toggle')) {
            button.setAttribute('data-bs-toggle', 'tooltip');
        }
    });
}

// Utility functions
const Utils = {
    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount);
    },
    
    // Format date
    formatDate: function(date) {
        return new Intl.DateTimeFormat('es-MX').format(new Date(date));
    },
    
    // Show loading spinner
    showLoading: function(element) {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        element.insertBefore(spinner, element.firstChild);
        element.disabled = true;
    },
    
    // Hide loading spinner
    hideLoading: function(element) {
        const spinner = element.querySelector('.spinner-border');
        if (spinner) {
            spinner.remove();
        }
        element.disabled = false;
    },
    
    // Show toast notification
    showToast: function(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toast = toastContainer.lastElementChild;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    },
    
    // AJAX helper
    ajax: function(options) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(config.url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX error:', error);
                Utils.showToast('Error en la comunicación con el servidor', 'danger');
                throw error;
            });
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// QR Code functions
const QRCode = {
    generate: function(data, element) {
        // This would integrate with a QR code library
        // For now, we'll just show the QR code text
        if (element) {
            element.innerHTML = `<div class="qr-code text-center p-3 border">
                <div class="mb-2">📱</div>
                <small>${data}</small>
            </div>`;
        }
    },
    
    scan: function(callback) {
        // This would integrate with a QR code scanner
        // For now, we'll simulate with a prompt
        const scannedData = prompt('Ingresa el código QR o escanea:');
        if (scannedData && callback) {
            callback(scannedData);
        }
    }
};

// Data tables helper
const DataTables = {
    init: function(selector, options = {}) {
        const table = document.querySelector(selector);
        if (!table) return;
        
        const defaults = {
            responsive: true,
            pageLength: 25,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-MX.json'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        // This would integrate with DataTables library
        // For now, we'll add basic functionality
        this.addSearch(table);
        this.addSort(table);
    },
    
    addSearch: function(table) {
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control mb-3';
        searchInput.placeholder = 'Buscar...';
        
        table.parentNode.insertBefore(searchInput, table);
        
        searchInput.addEventListener('input', Utils.debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }, 300));
    },
    
    addSort: function(table) {
        const headers = table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
        });
    }
};

// Sort table function
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const sorted = rows.sort((a, b) => {
        const aText = a.cells[column].textContent.trim();
        const bText = b.cells[column].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        return aText.localeCompare(bText);
    });
    
    // Clear tbody and append sorted rows
    tbody.innerHTML = '';
    sorted.forEach(row => tbody.appendChild(row));
}

// Chart helpers
const Charts = {
    colors: {
        primary: '#667eea',
        success: '#27ae60',
        warning: '#f39c12',
        danger: '#e74c3c',
        info: '#3498db'
    },
    
    defaultOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    },
    
    createChart: function(canvas, type, data, options = {}) {
        const ctx = canvas.getContext('2d');
        const config = {
            type: type,
            data: data,
            options: Object.assign(this.defaultOptions, options)
        };
        
        return new Chart(ctx, config);
    }
};

// Export to global scope
window.Utils = Utils;
window.QRCode = QRCode;
window.DataTables = DataTables;
window.Charts = Charts;