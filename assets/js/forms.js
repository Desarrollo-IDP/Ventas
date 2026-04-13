// ===== MANEJO AVANZADO DE FORMULARIOS =====
class FormManager {
    constructor() {
        this.forms = new Map();
        this.init();
    }

    init() {
        this.initDynamicForms();
        this.initFileUploads();
        this.initSelect2();
        this.initDatePickers();
        this.initInputMasks();
    }

    // ===== FORMULARIOS DINÁMICOS =====
    initDynamicForms() {
        // Formularios con campos dinámicos
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-add-field]')) {
                this.addDynamicField(e.target);
            }
            
            if (e.target.matches('[data-remove-field]')) {
                this.removeDynamicField(e.target);
            }
        });

        // Validación en tiempo real
        document.addEventListener('input', this.debounce((e) => {
            if (e.target.matches('[data-validate]')) {
                this.validateField(e.target);
            }
        }, 300));
    }

    addDynamicField(button) {
        const templateId = button.getAttribute('data-add-field');
        const container = button.closest('[data-fields-container]');
        const template = document.getElementById(templateId);
        
        if (template && container) {
            const newField = template.content.cloneNode(true);
            const index = container.children.length;
            
            // Actualizar nombres e IDs
            newField.querySelectorAll('[name], [id]').forEach(element => {
                if (element.name) {
                    element.name = element.name.replace(/\[\d+\]/, `[${index}]`);
                }
                if (element.id) {
                    element.id = element.id.replace(/\d+/, index);
                }
            });
            
            container.appendChild(newField);
            this.reindexFields(container);
        }
    }

    removeDynamicField(button) {
        const field = button.closest('[data-field]');
        if (field && field.parentNode.children.length > 1) {
            field.remove();
            this.reindexFields(field.parentNode);
        }
    }

    reindexFields(container) {
        const fields = container.querySelectorAll('[data-field]');
        fields.forEach((field, index) => {
            field.querySelectorAll('[name]').forEach(element => {
                element.name = element.name.replace(/\[\d+\]/, `[${index}]`);
            });
        });
    }

    validateField(field) {
        const rules = field.getAttribute('data-validate').split(' ');
        let isValid = true;
        let message = '';

        rules.forEach(rule => {
            switch (rule) {
                case 'required':
                    if (!field.value.trim()) {
                        isValid = false;
                        message = 'Este campo es requerido';
                    }
                    break;
                    
                case 'email':
                    if (field.value && !this.isValidEmail(field.value)) {
                        isValid = false;
                        message = 'Ingrese un email válido';
                    }
                    break;
                    
                case 'number':
                    if (field.value && isNaN(field.value)) {
                        isValid = false;
                        message = 'Ingrese un número válido';
                    }
                    break;
                    
                case 'min-length':
                    const minLength = parseInt(field.getAttribute('data-min-length'));
                    if (field.value.length < minLength) {
                        isValid = false;
                        message = `Mínimo ${minLength} caracteres`;
                    }
                    break;
            }
        });

        this.setFieldValidation(field, isValid, message);
        return isValid;
    }

    setFieldValidation(field, isValid, message) {
        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        let feedback = field.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        
        feedback.textContent = message;
        feedback.style.display = isValid ? 'none' : 'block';
    }

    // ===== SUBIDA DE ARCHIVOS =====
    initFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFilePreview(e.target);
            });
        });
    }

    handleFilePreview(input) {
        const previewContainer = document.querySelector(input.getAttribute('data-preview'));
        if (!previewContainer) return;

        previewContainer.innerHTML = '';
        
        Array.from(input.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail me-2 mb-2';
                    img.style.maxHeight = '100px';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                const badge = document.createElement('span');
                badge.className = 'badge bg-secondary me-2 mb-2';
                badge.textContent = file.name;
                previewContainer.appendChild(badge);
            }
        });
    }

    // ===== SELECT2 PERSONALIZADO =====
    initSelect2() {
        const enhancedSelects = document.querySelectorAll('select[data-enhanced]');
        
        enhancedSelects.forEach(select => {
            // Simulación de Select2 con Bootstrap
            select.addEventListener('focus', () => {
                select.parentNode.classList.add('focus');
            });
            
            select.addEventListener('blur', () => {
                select.parentNode.classList.remove('focus');
            });
        });
    }

    // ===== SELECTORES DE FECHA =====
    initDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        
        dateInputs.forEach(input => {
            // Establecer fecha mínima/máxima
            const minDate = input.getAttribute('data-min-date');
            const maxDate = input.getAttribute('data-max-date');
            
            if (minDate) {
                input.min = minDate;
            }
            if (maxDate) {
                input.max = maxDate;
            }
            
            // Valor por defecto
            if (!input.value && input.getAttribute('data-default-today')) {
                input.value = new Date().toISOString().split('T')[0];
            }
        });
    }

    // ===== MÁSCARAS DE ENTRADA =====
    initInputMasks() {
        // RFC
        const rfcInputs = document.querySelectorAll('input[data-mask="rfc"]');
        rfcInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9&Ñ]/g, '');
                if (value.length > 13) value = value.substring(0, 13);
                e.target.value = value;
            });
        });

        // Teléfono
        const phoneInputs = document.querySelectorAll('input[data-mask="phone"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
                } else {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '+$1 $2 $3');
                }
                e.target.value = value;
            });
        });

        // Moneda
        const currencyInputs = document.querySelectorAll('input[data-mask="currency"]');
        currencyInputs.forEach(input => {
            input.addEventListener('blur', (e) => {
                let value = parseFloat(e.target.value.replace(/[^\d.]/g, ''));
                if (!isNaN(value)) {
                    e.target.value = new Intl.NumberFormat('es-MX', {
                        style: 'currency',
                        currency: 'MXN'
                    }).format(value);
                }
            });
            
            input.addEventListener('focus', (e) => {
                let value = e.target.value.replace(/[^\d.]/g, '');
                e.target.value = value;
            });
        });
    }

    // ===== UTILIDADES =====
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
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

    // ===== API PÚBLICA =====
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('[data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    getFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        return data;
    }

    resetForm(form) {
        form.reset();
        form.classList.remove('was-validated');
        form.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
        
        const feedbacks = form.querySelectorAll('.invalid-feedback');
        feedbacks.forEach(feedback => {
            feedback.style.display = 'none';
        });
    }

    setFormLoading(form, isLoading) {
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            if (isLoading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text') || 'Guardar';
            }
        });
    }
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    window.formManager = new FormManager();
});