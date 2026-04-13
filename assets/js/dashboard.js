// ===== DASHBOARD INTERACTIVO =====
class Dashboard {
    constructor() {
        this.charts = new Map();
        this.init();
    }

    init() {
        this.initCharts();
        this.initRealTimeUpdates();
        this.initQuickActions();
        this.initStatsCounters();
    }

    // ===== GRÁFICOS =====
    initCharts() {
        // Gráfico de ventas mensuales
        this.initSalesChart();
        
        // Gráfico de productos más vendidos
        this.initTopProductsChart();
        
        // Gráfico de estado de cotizaciones
        this.initQuotesChart();
    }

    initSalesChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Ventas 2023',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 32000, 30000, 35000, 40000, 45000],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Ventas: $${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        this.charts.set('sales', chart);
    }

    initTopProductsChart() {
        const ctx = document.getElementById('topProductsChart');
        if (!ctx) return;

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Laptop Dell', 'Monitor 24"', 'Teclado RGB', 'Mouse Inalámbrico', 'Impresora Láser'],
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: [45, 32, 28, 25, 18],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(230, 126, 34, 0.8)'
                    ],
                    borderColor: [
                        '#3498db',
                        '#2ecc71',
                        '#9b59b6',
                        '#f1c40f',
                        '#e67e22'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        this.charts.set('products', chart);
    }

    initQuotesChart() {
        const ctx = document.getElementById('quotesChart');
        if (!ctx) return;

        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Aceptadas', 'Pendientes', 'Rechazadas', 'Expiradas'],
                datasets: [{
                    data: [45, 25, 15, 5],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(149, 165, 166, 0.8)'
                    ],
                    borderColor: [
                        '#2ecc71',
                        '#f1c40f',
                        '#e74c3c',
                        '#95a5a6'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        this.charts.set('quotes', chart);
    }

    // ===== ACTUALIZACIONES EN TIEMPO REAL =====
    initRealTimeUpdates() {
        // Actualizar estadísticas cada 30 segundos
        setInterval(() => {
            this.updateStats();
        }, 30000);

        // Notificaciones en tiempo real (simulación)
        this.initRealTimeNotifications();
    }

    updateStats() {
        // Simular actualización de datos
        const stats = document.querySelectorAll('[data-stat]');
        
        stats.forEach(stat => {
            const currentValue = parseInt(stat.textContent);
            const randomChange = Math.floor(Math.random() * 10) - 2; // -2 to +7
            const newValue = Math.max(0, currentValue + randomChange);
            
            this.animateCounter(stat, currentValue, newValue);
        });
    }

    animateCounter(element, start, end) {
        const duration = 1000;
        const stepTime = 20;
        const steps = duration / stepTime;
        const increment = (end - start) / steps;
        let current = start;
        let step = 0;

        const timer = setInterval(() => {
            current += increment;
            step++;
            
            if (step >= steps) {
                current = end;
                clearInterval(timer);
            }
            
            element.textContent = Math.floor(current);
        }, stepTime);
    }

    initRealTimeNotifications() {
        // Simular notificaciones cada 2 minutos
        setInterval(() => {
            this.showRandomNotification();
        }, 120000);
    }

    showRandomNotification() {
        const notifications = [
            {
                title: 'Nueva cotización',
                message: 'Se ha recibido una nueva cotización',
                type: 'info'
            },
            {
                title: 'Stock bajo',
                message: 'El producto "Mouse Inalámbrico" tiene stock bajo',
                type: 'warning'
            },
            {
                title: 'Cotización aceptada',
                message: 'Una cotización ha sido aceptada por el cliente',
                type: 'success'
            }
        ];

        const notification = notifications[Math.floor(Math.random() * notifications.length)];
        
        if (window.sistemaPOS) {
            window.sistemaPOS.showNotification(notification.title, {
                body: notification.message,
                icon: '/assets/images/logo.png'
            });
        }
    }

    // ===== ACCIONES RÁPIDAS =====
    initQuickActions() {
        const quickActions = document.querySelectorAll('[data-quick-action]');
        
        quickActions.forEach(action => {
            action.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleQuickAction(action);
            });
        });
    }

    handleQuickAction(action) {
        const actionType = action.getAttribute('data-quick-action');
        
        switch (actionType) {
            case 'new-quote':
                window.location.href = '../cotizaciones/crear.php';
                break;
                
            case 'new-product':
                window.location.href = '../productos/crear.php';
                break;
                
            case 'new-client':
                window.location.href = '../clientes/crear.php';
                break;
                
            case 'view-reports':
                this.showQuickReport();
                break;
                
            case 'export-data':
                this.exportDashboardData();
                break;
        }
    }

    showQuickReport() {
        // Mostrar modal de reporte rápido
        const modal = new bootstrap.Modal(document.getElementById('quickReportModal'));
        modal.show();
    }

    exportDashboardData() {
        // Simular exportación de datos
        window.sistemaPOS.showAlert('Preparando exportación de datos...', 'info');
        
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(this.generateCSVData());
            link.download = `dashboard-export-${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            window.sistemaPOS.showAlert('Datos exportados correctamente', 'success');
        }, 1000);
    }

    generateCSVData() {
        // Generar datos CSV de ejemplo
        const headers = ['Métrica', 'Valor', 'Fecha'];
        const data = [
            ['Total Ventas', '$45,000', new Date().toISOString()],
            ['Cotizaciones Activas', '25', new Date().toISOString()],
            ['Clientes Nuevos', '8', new Date().toISOString()],
            ['Productos con Stock Bajo', '3', new Date().toISOString()]
        ];
        
        return [headers, ...data].map(row => row.join(',')).join('\n');
    }

    // ===== CONTADORES ANIMADOS =====
    initStatsCounters() {
        const counters = document.querySelectorAll('[data-counter]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounterFromZero(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    animateCounterFromZero(element) {
        const target = parseInt(element.getAttribute('data-counter'));
        const duration = 2000;
        const stepTime = 20;
        const steps = duration / stepTime;
        const increment = target / steps;
        let current = 0;
        let step = 0;

        const timer = setInterval(() => {
            current += increment;
            step++;
            
            if (step >= steps) {
                current = target;
                clearInterval(timer);
            }
            
            element.textContent = Math.floor(current).toLocaleString();
        }, stepTime);
    }

    // ===== API PÚBLICA =====
    refreshCharts() {
        this.charts.forEach(chart => {
            chart.update();
        });
    }

    updateChartData(chartName, newData) {
        const chart = this.charts.get(chartName);
        if (chart) {
            chart.data.datasets[0].data = newData;
            chart.update();
        }
    }

    getDashboardStats() {
        // Obtener estadísticas actuales del dashboard
        return {
            totalSales: 45000,
            activeQuotes: 25,
            newClients: 8,
            lowStockProducts: 3,
            pendingTasks: 12
        };
    }
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.dashboard-page')) {
        window.dashboard = new Dashboard();
    }
});