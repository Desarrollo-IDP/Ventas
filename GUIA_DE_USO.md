# Guía de Funcionalidades - Sistema de Punto de Venta

## 🎯 Apartado de Cotizaciones

### Crear Nueva Cotización
1. Acceder a **Cotizaciones → Nueva Cotización**
2. Seleccionar el **cliente** de la lista
3. Buscar y agregar **productos** usando el buscador
4. Establecer **cantidades** y **precios unitarios**
5. El sistema calcula automáticamente:
   - Subtotal
   - IVA (16%)
   - Total
6. Añadir **notas** si es necesario
7. Hacer clic en **"Crear Cotización"**
8. El cliente recibirá automáticamente un email con la cotización

### Listar Cotizaciones
- Ver todas las cotizaciones creadas
- Filtrar por:
  - Estatus (Pendiente, Aceptada, Rechazada, Expirada)
  - Fecha (desde - hasta)
  - Cliente
- Ver estadísticas rápidas:
  - Cotizaciones pendientes
  - Cotizaciones aceptadas
  - Cotizaciones rechazadas
  - Total cotizado

### Detalle de Cotización
- Ver información completa de la cotización
- Ver detalles de productos
- **Acciones disponibles**:
  - **Aceptar** (si está pendiente) - Reserva stock automáticamente
  - **Rechazar** (si está pendiente)
  - **Reenviar Email** - Envía nuevamente la cotización
  - **Editar** - Modificar datos
  - **Descargar PDF** - Exportar en PDF
  - **Duplicar** - Crear copia (para rechazadas o expiradas)

### Cambio de Estatus
- **Pendiente**: Estado inicial al crear
- **Aceptada**: Cliente acepta la cotización
  - Se reserva automáticamente el stock
  - Se envía email de confirmación
- **Rechazada**: Cliente rechaza la cotización
  - Se envía email de rechazo
  - Permite duplicar para crear nueva
- **Expirada**: Cuando vence la fecha de vencimiento

---

## 👥 Apartado de Clientes

### Crear Cliente
1. Acceder a **Clientes → Nuevo Cliente**
2. Ingresar:
   - **Nombre completo** (requerido)
   - **Email** (debe ser válido)
   - **Teléfono** (10-15 dígitos)
   - **Dirección** (requerida)
3. Seleccionar estado:
   - **Activo**: Cliente disponible para cotizaciones
   - **Inactivo**: Cliente no disponible
4. Hacer clic en **"Guardar Cliente"**

### Listar Clientes
- Ver todos los clientes registrados
- Buscar por nombre, email, teléfono
- Filtrar por estado (Activo/Inactivo)
- Ver información de contacto

### Editar Cliente
- Modificar datos del cliente
- Cambiar estado
- Visualizar historial de cambios

---

## 📦 Apartado de Productos

### Crear Producto
1. Acceder a **Productos → Nuevo Producto**
2. Ingresar:
   - **Código** (identificador único)
   - **Nombre** (requerido)
   - **Descripción** (opcional)
   - **Precio** (requerido)
   - **Stock inicial**
   - **Stock mínimo** (para alertas)
3. Guardar producto

### Listar Productos
- Ver todos los productos disponibles
- Buscar por código o nombre
- Filtrar por:
  - Estado (Activo/Inactivo)
  - Disponibilidad (Disponible/Bajo stock/Agotado)
- Ver información de stock

### Gestión de Stock
- **Actualización automática**: Al aceptar cotización, se descuenta automáticamente
- **Ajuste manual**: Desde "Ajustar Stock" si hay discrepancias
- **Alertas**: Stock bajo muestra en rojo

### Búsqueda para Cotizaciones
- Al crear cotización, buscar productos en tiempo real
- Muestra:
  - Código
  - Nombre
  - Precio actual
  - Stock disponible

---

## 📧 Sistema de Notificaciones

El sistema envía emails automáticamente en los siguientes casos:

1. **Creación de Cotización**
   - Cliente recibe la cotización con detalles
   - Incluye plazo de vencimiento
   - Permite aceptar o rechazar

2. **Aceptación de Cotización**
   - Confirmación de aceptación
   - Aviso de reserva de stock

3. **Rechazo de Cotización**
   - Notificación de rechazo
   - Disponibilidad para nuevas propuestas

---

## 📊 Características Técnicas

### Base de Datos
- **Tablas principales**:
  - `cotizaciones` - Cabecera de cotizaciones
  - `cotizacion_detalles` - Productos en cotización
  - `clientes` - Información de clientes
  - `productos` - Catálogo de productos
  - `notificaciones` - Historial de emails
  - `auditoria` - Historial de cambios

### Generación de Folios
- Formato: `COT-YYYYMMDD-XXXX`
- Ejemplo: `COT-20251118-0547`
- Único por cada cotización creada

### Cálculos
- **IVA fijo**: 16%
- **Total**: Subtotal + IVA
- **Stock**: Se resta automáticamente al aceptar

---

## 🔍 Búsqueda y Filtros

### Filtro de Cotizaciones
```
Estatus: Todos/Pendiente/Aceptada/Rechazada/Expirada
Fecha desde: [date picker]
Fecha hasta: [date picker]
Cliente: [búsqueda texto]
```

### Búsqueda de Productos
- Mínimo 2 caracteres
- Busca en código y nombre
- Mostrará hasta 10 resultados
- Muestra stock disponible

---

## ⚠️ Validaciones

### Cliente
- Nombre: máximo 255 caracteres
- Email: debe ser válido
- Teléfono: 10-15 dígitos

### Producto
- Código: único
- Precio: debe ser mayor a 0
- Stock: no puede ser negativo

### Cotización
- Cliente: requerido
- Al menos 1 producto: requerido
- Cantidad: no puede exceder stock disponible

---

## 📝 Notas y Observaciones

- Las cotizaciones pueden incluir notas/términos y condiciones
- Las notas aparecen en:
  - Vista de detalles
  - Email enviado
  - PDF generado

---

## 🆘 Solución de Problemas

### No aparecen productos al buscar
- Asegúrese de escribir al menos 2 caracteres
- Verifique que el producto esté activo
- Verifique la ortografía

### Email no se envía
- Revisar configuración de mail en el servidor
- Verificar email válido del cliente
- Revisar logs de errores

### Stock no se actualiza
- Asegúrese de aceptar la cotización (no solo crear)
- Verificar que hay stock suficiente
- Revisar en listado de productos

---

## 📌 Atajos Rápidos

- **Cotizaciones** → `/views/cotizaciones/listar.php`
- **Crear Cotización** → `/views/cotizaciones/crear.php`
- **Clientes** → `/views/clientes/listar.php`
- **Productos** → `/views/productos/listar.php`

---

Última actualización: 18 de Noviembre de 2025
