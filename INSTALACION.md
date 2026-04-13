# Instalación y Configuración - Sistema POS

## 📋 Requisitos Previos

- PHP 7.4 o superior
- MySQL/MariaDB 5.7 o superior
- Apache con mod_rewrite habilitado
- Extensiones PHP recomendadas:
  - php-pdo
  - php-pdo_mysql
  - php-json
  - php-curl
  - php-mbstring

---

## 🔧 Instalación

### 1. Configuración de la Base de Datos

#### Crear la base de datos
```sql
CREATE DATABASE IF NOT EXISTS sistema_pos_development;
USE sistema_pos_development;
```

#### Importar la estructura (incluida en `config/sistema_pos_development.sql`)
```bash
mysql -u root -p sistema_pos_development < config/sistema_pos_development.sql
```

### 2. Configuración de Ambiente

Editar `config/database.php`:

```php
'development' => [
    'host' => 'localhost',
    'database' => 'sistema_pos_development',
    'username' => 'root',
    'password' => '',  // Tu contraseña de MySQL
    'charset' => 'utf8mb4',
    'port' => 3306
]
```

### 3. Verificar Carpetas Necesarias

```
✓ /uploads/      - Para subir archivos
✓ /logs/         - Para registros del sistema
✓ /backups/database/ - Para backups automáticos
✓ /temp/         - Para archivos temporales
✓ /cache/        - Para caché
```

Crear si no existen:
```bash
mkdir -p uploads logs backups/database temp cache
chmod 755 uploads logs temp cache
```

### 4. Configuración de Email

En `models/Notificacion.php`, línea 70:
```php
$headers .= "From: sistema@empresa.com\r\n";  // Cambiar por tu email
```

Para producción, configurar un servidor SMTP real en PHP.

---

## 🚀 Iniciar el Sistema

### Desde XAMPP
1. Colocar la carpeta `P.V` en `C:\xampp\htdocs\`
2. Abrir `http://localhost/P.V/`
3. Sistema redirigirá automáticamente al dashboard

### Desde línea de comandos
```bash
cd C:\xampp\htdocs\P.V
php -S localhost:8000
```

Luego acceder a `http://localhost:8000`

---

## 🧪 Pruebas Iniciales

### 1. Verificar Conexión a BD
```php
<?php
require_once 'config/database.php';
$db = new Database();
if ($db->getConnection()) {
    echo "Conexión exitosa";
} else {
    echo "Error de conexión";
}
?>
```

### 2. Crear un Cliente Prueba
- Ir a **Clientes → Nuevo Cliente**
- Llenar formulario
- Debe mostrar mensaje de éxito

### 3. Crear una Cotización
- Ir a **Cotizaciones → Nueva Cotización**
- Seleccionar cliente de prueba
- Buscar un producto
- Debe agregar a la lista
- Crear cotización

### 4. Cambiar Estatus
- Ir a **Cotizaciones → Listar**
- Hacer clic en una cotización
- Aceptarla o rechazarla
- Debe cambiar estado

---

## 📧 Configuración de Email

### Opción 1: Email nativo de PHP (desarrollo)
Ya está configurado por defecto usando `mail()` de PHP

### Opción 2: SMTP Real (producción)
Editar `models/Notificacion.php`:

```php
// Usar PHPMailer o SwiftMailer para SMTP
require_once 'vendor/autoload.php';

$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'tu_email@gmail.com';
$mail->Password = 'tu_contraseña_app';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

---

## 🔐 Seguridad

### 1. Cambiar Contraseña de MySQL
```bash
mysql -u root -p
ALTER USER 'root'@'localhost' IDENTIFIED BY 'contraseña_fuerte';
FLUSH PRIVILEGES;
```

### 2. Crear Usuario dedicado
```sql
CREATE USER 'pos_user'@'localhost' IDENTIFIED BY 'password_fuerte';
GRANT ALL PRIVILEGES ON sistema_pos_development.* TO 'pos_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configurar HTTPS
En producción, usar HTTPS (SSL/TLS)

### 4. Permisos de Archivos
```bash
chmod 644 config/database.php
chmod 755 logs/ backups/ temp/ cache/
chmod 644 config/init.php
```

---

## 📊 Base de Datos

### Tablas Principales

#### `cotizaciones`
```sql
- id (PRIMARY KEY)
- folio (UNIQUE, COT-YYYYMMDD-XXXX)
- cliente_id (FK → clientes)
- fecha_creacion
- fecha_vencimiento
- subtotal
- iva
- total
- estatus (pendiente, aceptada, rechazada, expirada)
- notas
```

#### `cotizacion_detalles`
```sql
- id (PRIMARY KEY)
- cotizacion_id (FK → cotizaciones)
- producto_id (FK → productos)
- cantidad
- precio_unitario
- importe
```

#### `clientes`
```sql
- id (PRIMARY KEY)
- nombre
- email (UNIQUE)
- telefono
- direccion
- rfc
- activo (BOOLEAN)
- created_at
```

#### `productos`
```sql
- id (PRIMARY KEY)
- codigo (UNIQUE)
- nombre
- descripcion
- precio
- stock
- stock_minimo
- activo (BOOLEAN)
- created_at
- updated_at
```

#### `notificaciones`
```sql
- id (PRIMARY KEY)
- cotizacion_id (FK → cotizaciones)
- tipo (creacion, aceptacion, rechazo, envio, entrega)
- destinatario_email
- asunto
- mensaje
- enviado (BOOLEAN)
- fecha_envio
- created_at
```

#### `auditoria`
```sql
- id (PRIMARY KEY)
- tabla_afectada
- registro_id
- accion (INSERT, UPDATE, DELETE)
- valores_anteriores (JSON)
- valores_nuevos (JSON)
- usuario_id
- ip_address
- created_at
```

---

## 🆘 Solución de Problemas

### Error: "No se pudo conectar a la base de datos"
```
1. Verificar que MySQL está corriendo
2. Verificar credenciales en config/database.php
3. Verificar que la BD existe
4. Revisar logs del servidor MySQL
```

### Error: "Call to undefined function"
```
1. Verificar que require_once está correcto
2. Verificar que el archivo existe
3. Revisar que no hay errores de sintaxis
4. Limpiar cache si existe
```

### Campos vacíos en formularios
```
1. Limpiar caché del navegador (Ctrl+Shift+Del)
2. Verificar que JavaScript está habilitado
3. Revisar console del navegador (F12)
4. Revisar logs del servidor
```

### Email no se envía
```
1. Verificar función mail() está habilitada en php.ini
2. Verificar email válido del cliente
3. Revisar logs (logs/security_errors.log)
4. Probar con cuenta local primero
```

---

## 📈 Mantenimiento

### Backups Automáticos
El sistema crea backups automáticamente en `backups/database/`

Para backup manual:
```bash
mysqldump -u root -p sistema_pos_development > backup_manual.sql
```

### Limpiar Logs
```bash
# Linux/Mac
find logs/ -name "*.log" -mtime +30 -delete

# Windows
forfiles /S /D +30 /C "cmd /c del @file"
```

### Optimizar Base de Datos
```sql
OPTIMIZE TABLE cotizaciones;
OPTIMIZE TABLE cotizacion_detalles;
OPTIMIZE TABLE clientes;
OPTIMIZE TABLE productos;
```

---

## 🔄 Actualizar Sistema

### Paso a paso
1. Hacer backup de BD: `mysqldump -u root -p sistema_pos_development > backup.sql`
2. Hacer backup de archivos: `cp -r . backup/`
3. Descargar última versión
4. Copiar nuevos archivos
5. Ejecutar nuevas migraciones (si las hay)
6. Probar funcionalidades

---

## 📞 Soporte

Para problemas de configuración:
1. Revisar `logs/security_errors.log`
2. Revisar consola del navegador (F12)
3. Revisar logs de MySQL
4. Consultar la documentación en `GUIA_DE_USO.md`

---

**Configuración Completada** ✅

Última actualización: 18 de Noviembre de 2025
