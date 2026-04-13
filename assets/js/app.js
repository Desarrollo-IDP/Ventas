// ===== APLICACIÓN PRINCIPAL =====
class SistemaPOS {
    constructor() {
        this.init();
    }

    init() {
        this.initSidebar();
        this.initTooltips();
        this.initAlerts();
        this.initForms();
        this.initNotifications();
        this.initGlobalHandlers();
    }

    // ===== SIDEBAR Y NAVEGACIÓN =====
    initSidebar() {
        // Toggle sidebar en móvil
        const sidebarToggle = document.querySelector('[data-bs-toggle="sidebar"]');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.mobile-overlay');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            });
        }

        // Cerrar sidebar al hacer clic en overlay
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });
        }

        // Navegación activa
        this.setActiveNavigation();
    }

    setActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (currentPath.includes(linkPath) && linkPath !== '/') {
                link.classList.add('active');
            }
        });
    }

    // ===== TOOLTIPS =====
    initTooltips() {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
            });
        });
    }

    // ===== SISTEMA DE ALERTAS =====
    initAlerts() {
        // Auto-ocultar alertas después de 5 segundos
        const autoAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        autoAlerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    bootstrap.Alert.getInstance(alert)?.close();
                }
            }, 5000);
        });
    }

    // ===== MANEJO DE FORMULARIOS =====
    initForms() {
        // Validación de formularios
        const forms = document.querySelectorAll('form[needs-validation]');
        forms.forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });

        // Formato automático de moneda
        const currencyInputs = document.querySelectorAll('input[data-currency]');
        currencyInputs.forEach(input => {
            input.addEventListener('blur', this.formatCurrency.bind(this));
            input.addEventListener('focus', this.clearCurrencyFormat.bind(this));
        });

        // Formato automático de teléfono
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', this.formatPhoneNumber.bind(this));
        });
    }

    handleFormSubmit(event) {
        const form = event.target;
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            this.showAlert('Por favor, complete todos los campos requeridos correctamente.', 'warning');
        }
        
        form.classList.add('was-validated');
    }

    formatCurrency(event) {
        const input = event.target;
        let value = input.value.replace(/[^\d.]/g, '');
        
        if (value) {
            value = parseFloat(value).toFixed(2);
            input.value = new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(value);
        }
    }

    clearCurrencyFormat(event) {
        const input = event.target;
        let value = input.value.replace(/[^\d.]/g, '');
        input.value = value;
    }

    formatPhoneNumber(event) {
        const input = event.target;
        let value = input.value.replace(/\D/g, '');
        
        if (value.length <= 10) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
        } else {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '+$1 $2 $3');
        }
        
        input.value = value;
    }

    // ===== NOTIFICACIONES =====
    initNotifications() {
        // Sistema de notificaciones push
        if ('Notification' in window) {
            this.requestNotificationPermission();
        }
    }

    requestNotificationPermission() {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Permisos de notificación concedidos');
                }
            });
        }
    }

    showNotification(title, options = {}) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                icon: '/assets/images/logo.png',
                badge: '/assets/images/logo.png',
                ...options
            });
        }
    }

    // ===== MANEJADORES GLOBALES =====
    initGlobalHandlers() {
        // Confirmación para acciones destructivas
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-confirm]')) {
                const message = e.target.getAttribute('data-confirm') || 
                              '¿Está seguro de realizar esta acción?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });

        // Cargar datos automáticamente
        this.loadAutoRefreshData();

        // Manejar errores globales
        window.addEventListener('error', this.handleGlobalError.bind(this));
    }

    loadAutoRefreshData() {
        // Elementos que necesitan actualización automática
        const autoRefreshElements = document.querySelectorAll('[data-auto-refresh]');
        
        autoRefreshElements.forEach(element => {
            const interval = element.getAttribute('data-refresh-interval') || 30000;
            setInterval(() => {
                this.refreshElementData(element);
            }, parseInt(interval));
        });
    }

    refreshElementData(element) {
        const url = element.getAttribute('data-refresh-url');
        if (url) {
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    element.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error al actualizar datos:', error);
                });
        }
    }

    handleGlobalError(event) {
        console.error('Error global:', event.error);
        this.showAlert('Ocurrió un error inesperado. Por favor, recargue la página.', 'danger');
    }

    // ===== API PÚBLICA =====
    showAlert(message, type = 'info', duration = 5000) {
        // Usar la función global mostrarAlerta si existe
        if (typeof window.mostrarAlerta === 'function') {
            window.mostrarAlerta(message, type, duration);
        } else {
            // Fallback básico
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.main-content') || document.body;
            container.insertBefore(alert, container.firstChild);
            
            if (duration > 0) {
                setTimeout(() => {
                    if (alert.parentNode) {
                        bootstrap.Alert.getInstance(alert)?.close();
                    }
                }, duration);
            }
        }
    }

    confirmAction(message) {
        return new Promise((resolve) => {
            if (typeof window.confirmarAccion === 'function') {
                window.confirmarAccion(message, resolve);
            } else {
                resolve(confirm(message));
            }
        });
    }

    showLoading(selector = 'body') {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('loading');
        }
    }

    hideLoading(selector = 'body') {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.remove('loading');
        }
    }

    // ===== UTILIDADES =====
    formatMoney(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount);
    }

    formatDate(date, format = 'short') {
        const dateObj = new Date(date);
        return new Intl.DateTimeFormat('es-MX', {
            dateStyle: format
        }).format(dateObj);
    }

    debounce(func, wait) {
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

    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar la aplicación
    window.sistemaPOS = new SistemaPOS();

    // Exponer funciones globales
    window.mostrarAlerta = function(message, type = 'success', duration = 5000) {
        window.sistemaPOS.showAlert(message, type, duration);
    };

    window.confirmarAccion = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };

    window.formatearMoneda = function(monto) {
        return window.sistemaPOS.formatMoney(monto);
    };

    window.formatearFecha = function(fecha, formato = 'short') {
        return window.sistemaPOS.formatDate(fecha, formato);
    };

    // Configuración global
    console.log(`
        🛍️ Sistema de Punto de Venta
        🚀 Versión 1.0.0
        📧 Módulos: Cotizaciones, Productos, Clientes
        💡 Desarrollado con PHP y Bootstrap 5
    `);
});

// ===== MANEJADOR DE ERRORES NO CAPTURADOS =====
window.addEventListener('unhandledrejection', function(event) {
    console.error('Promise rechazada no manejada:', event.reason);
    window.sistemaPOS.showAlert('Error en la aplicación. Por favor, recargue la página.', 'danger');
});