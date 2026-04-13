            </main>
        </div>
    </div>

    <!-- Modal para confirmaciones -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">¿Está seguro de realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmButton">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        // Sistema de alertas
        const alertQueue = [];
        let isShowingAlert = false;

        function mostrarAlerta(mensaje, tipo = 'success', tiempo = 5000) {
            alertQueue.push({ mensaje, tipo, tiempo });
            if (!isShowingAlert) {
                procesarSiguienteAlerta();
            }
        }

        function procesarSiguienteAlerta() {
            if (alertQueue.length === 0) {
                isShowingAlert = false;
                return;
            }

            isShowingAlert = true;
            const { mensaje, tipo, tiempo } = alertQueue.shift();
            
            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
            alerta.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            
            alerta.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${obtenerIconoAlerta(tipo)} me-2"></i>
                    <div class="flex-grow-1">${mensaje}</div>
                    <button type="button" class="btn-close" onclick="cerrarAlerta(this)"></button>
                </div>
            `;
            
            document.body.appendChild(alerta);
            
            // Animar entrada
            setTimeout(() => {
                alerta.style.transform = 'translateX(0)';
                alerta.style.opacity = '1';
            }, 100);
            
            // Auto cerrar después del tiempo especificado
            setTimeout(() => {
                if (alerta.parentNode) {
                    cerrarAlerta(alerta.querySelector('.btn-close'));
                }
            }, tiempo);
        }

        function obtenerIconoAlerta(tipo) {
            const iconos = {
                'success': 'fa-check-circle',
                'danger': 'fa-exclamation-triangle',
                'warning': 'fa-exclamation-circle',
                'info': 'fa-info-circle'
            };
            return iconos[tipo] || 'fa-bell';
        }

        function cerrarAlerta(boton) {
            const alerta = boton.closest('.alert');
            alerta.style.transform = 'translateX(100%)';
            alerta.style.opacity = '0';
            setTimeout(() => {
                if (alerta.parentNode) {
                    alerta.parentNode.removeChild(alerta);
                }
                procesarSiguienteAlerta();
            }, 300);
        }

        // Sistema de confirmaciones
        function confirmarAccion(mensaje, callback) {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmButton = document.getElementById('confirmButton');
            
            confirmMessage.textContent = mensaje || '¿Está seguro de realizar esta acción?';
            
            // Remover event listeners anteriores
            const newConfirmButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
            
            newConfirmButton.addEventListener('click', function() {
                confirmModal.hide();
                if (typeof callback === 'function') {
                    callback();
                }
            });
            
            confirmModal.show();
            return false;
        }

        // Funciones de utilidad
        function formatearMoneda(monto) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(monto);
        }

        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        function validarEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        function mostrarCargando(mensaje = 'Cargando...') {
            // Puedes implementar un spinner global aquí
            console.log(mensaje);
        }

        function ocultarCargando() {
            // Ocultar spinner global
            console.log('Cargando completado');
        }

        // Manejo de errores global
        window.addEventListener('error', function(e) {
            console.error('Error global:', e.error);
            mostrarAlerta('Ocurrió un error inesperado', 'danger');
        });

        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar estilos para las alertas
            const estilos = document.createElement('style');
            estilos.textContent = `
                .alert.position-fixed {
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s ease-in-out;
                }
                
                .sidebar {
                    min-height: 100vh;
                    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                }
                
                .main-content {
                    background-color: #f8f9fa;
                    min-height: 100vh;
                }
                
                .stat-card {
                    border: none;
                    border-radius: 10px;
                    transition: all 0.3s ease;
                }
                
                .stat-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                }
                
                .table-hover tbody tr:hover {
                    background-color: rgba(0,0,0,0.02);
                }
                
                .btn {
                    border-radius: 6px;
                    transition: all 0.2s ease;
                }
                
                .btn:hover {
                    transform: translateY(-1px);
                }
            `;
            document.head.appendChild(estilos);

            // Inicializar tooltips de Bootstrap
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });

            // Manejar formularios con validación
            const forms = document.querySelectorAll('form[needs-validation]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        mostrarAlerta('Por favor, complete todos los campos requeridos correctamente', 'warning');
                    }
                    form.classList.add('was-validated');
                });
            });

            // Auto-ocultar alertas después de 5 segundos
            const alertasAuto = document.querySelectorAll('.alert:not(.alert-permanent)');
            alertasAuto.forEach(alerta => {
                setTimeout(() => {
                    if (alerta.parentNode) {
                        const bsAlert = new bootstrap.Alert(alerta);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // API helper functions
        const Api = {
            async get(url) {
                mostrarCargando();
                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    ocultarCargando();
                    return data;
                } catch (error) {
                    ocultarCargando();
                    mostrarAlerta('Error al cargar los datos', 'danger');
                    throw error;
                }
            },

            async post(url, data) {
                mostrarCargando();
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    ocultarCargando();
                    return result;
                } catch (error) {
                    ocultarCargando();
                    mostrarAlerta('Error al enviar los datos', 'danger');
                    throw error;
                }
            },

            async postForm(url, formData) {
                mostrarCargando();
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    ocultarCargando();
                    return result;
                } catch (error) {
                    ocultarCargando();
                    mostrarAlerta('Error al enviar el formulario', 'danger');
                    throw error;
                }
            }
        };

        // Exportar funciones globalmente
        window.mostrarAlerta = mostrarAlerta;
        window.confirmarAccion = confirmarAccion;
        window.formatearMoneda = formatearMoneda;
        window.formatearFecha = formatearFecha;
        window.validarEmail = validarEmail;
        window.Api = Api;

    </script>
</body>
</html>