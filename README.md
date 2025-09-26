# 🧀 Sistema de Logística - Quesos y Productos Leslie

Sistema integral de gestión logística para la empresa Quesos y Productos Leslie, desarrollado en PHP puro con arquitectura MVC, MySQL 5.7 y Bootstrap 5.

## 📋 Características Principales

### 🏭 Módulo de Gestión de Producción e Inventario
- Registro de producción en 3 modalidades (granel, pieza, paquete)
- Gestión de inventario por lotes con trazabilidad completa
- Asignación inteligente de lotes a pedidos
- Alertas proactivas por proximidad de caducidad

### 📦 Módulo de Gestión de Pedidos (Preventas)
- Captura multicanal de pedidos (web, WhatsApp)
- Flexibilidad para ajustar cantidades en entrega
- Validación con códigos QR únicos
- Seguimiento de estatus en tiempo real

### 🚛 Módulo de Optimización Logística y Rutas
- Gestión de recursos (vendedores-choferes)
- Planificación de rutas optimizadas
- Monitoreo en tiempo real
- Protocolos de validación (QR, WhatsApp)

### 💰 Módulo de Ventas en Punto de Entrega
- Ventas directas durante la entrega
- Verificación mediante QR y WhatsApp
- Gestión de múltiples métodos de pago

### 🔄 Módulo de Control de Retornos y Calidad
- Registro de devoluciones con trazabilidad
- Evaluación de calidad para reingreso a inventario
- Control de mermas

### 😊 Módulo de Experiencia del Cliente
- Encuestas multicanal de satisfacción
- Análisis de feedback y calificaciones
- Reportes segmentados

### 📊 Módulo de Analítica y Reportes
- Cierre operativo diario automatizado
- Reportes especializados con filtros avanzados
- Dashboards interactivos con gráficas

### 👥 Módulo de Gestión de Clientes
- Base de datos centralizada
- Histórico integral de pedidos y entregas
- Comunicación integrada

### 💼 Módulo de Administración Financiera
- Control detallado de ingresos por canal
- Gestión de cobranza y conciliación
- Exportación en múltiples formatos

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7+ (puro, sin framework)
- **Base de Datos:** MySQL 5.7
- **Frontend:** HTML5, CSS3, JavaScript ES6
- **Framework CSS:** Bootstrap 5
- **Gráficas:** Chart.js
- **Arquitectura:** MVC (Model-View-Controller)
- **Autenticación:** Sesiones PHP + password_hash()

## 📥 Instalación

### Requisitos del Sistema

- Apache 2.4+
- PHP 7.4+ con extensiones:
  - PDO
  - PDO_MySQL
  - Session
  - JSON
  - mbstring
- MySQL 5.7+

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/danjohn007/QuesosLeslie.git
cd QuesosLeslie
```

2. **Configurar el servidor web**
   - Copiar los archivos a tu directorio web (ej: `/var/www/html/quesos-leslie/`)
   - Asegurar que Apache tenga `mod_rewrite` habilitado
   - El archivo `.htaccess` ya está configurado para URL amigables

3. **Configurar la base de datos**
```bash
# Acceder a MySQL
mysql -u root -p

# Crear la base de datos
CREATE DATABASE quesos_leslie CHARACTER SET utf8 COLLATE utf8_general_ci;

# Importar el schema
mysql -u root -p quesos_leslie < sql/schema.sql

# Importar datos de ejemplo
mysql -u root -p quesos_leslie < sql/sample_data.sql
```

4. **Configurar credenciales de base de datos**
   - Editar el archivo `config/config.php`
   - Actualizar las constantes de conexión:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'quesos_leslie');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

5. **Verificar la instalación**
   - Acceder a `http://tu-dominio/quesos-leslie/test_connection.php`
   - Verificar que todas las conexiones estén correctas
   - El sistema detectará automáticamente la URL base

6. **Configurar permisos**
```bash
# Dar permisos de escritura a directorios necesarios
chmod 755 logs/
chmod 755 assets/uploads/
chmod 755 assets/qr_codes/
```

## 🚀 Uso del Sistema

### Acceso al Sistema

Acceder a: `http://tu-dominio/quesos-leslie/`

### Credenciales por Defecto

| Rol | Usuario | Contraseña | Descripción |
|-----|---------|------------|-------------|
| Admin | `admin` | `password` | Acceso completo al sistema |
| Gerente | `gerente` | `password` | Gestión operativa y reportes |
| Vendedor | `vendedor1` | `password` | Ventas y pedidos |
| Chofer | `chofer1` | `password` | Entregas y rutas |

> ⚠️ **Importante:** Cambiar las contraseñas por defecto después del primer acceso.

### Navegación Principal

- **Dashboard:** Resumen general y métricas clave
- **Producción:** Gestión de lotes e inventario
- **Pedidos:** Crear y gestionar pedidos
- **Rutas:** Planificación y seguimiento de entregas
- **Ventas:** Registro de transacciones
- **Clientes:** Base de datos de clientes
- **Devoluciones:** Control de retornos
- **Reportes:** Analítica y dashboards

## 🗂️ Estructura del Proyecto

```
QuesosLeslie/
├── 📁 assets/
│   ├── 📁 css/          # Estilos personalizados
│   ├── 📁 js/           # JavaScript personalizado
│   ├── 📁 images/       # Imágenes del sistema
│   ├── 📁 uploads/      # Archivos subidos
│   └── 📁 qr_codes/     # Códigos QR generados
├── 📁 config/
│   ├── config.php       # Configuración general
│   └── database.php     # Clase de conexión DB
├── 📁 controllers/
│   ├── BaseController.php
│   ├── HomeController.php
│   ├── AuthController.php
│   └── ...
├── 📁 models/
│   ├── BaseModel.php
│   ├── User.php
│   └── ...
├── 📁 views/
│   ├── 📁 layout/       # Plantillas generales
│   ├── 📁 home/         # Vistas del dashboard
│   ├── 📁 auth/         # Vistas de autenticación
│   └── ...
├── 📁 sql/
│   ├── schema.sql       # Estructura de la DB
│   └── sample_data.sql  # Datos de ejemplo
├── 📁 logs/             # Archivos de log
├── .htaccess            # Configuración Apache
├── index.php            # Punto de entrada
├── test_connection.php  # Test de conexión
└── README.md
```

## 🔧 Configuración Avanzada

### URL Base Automática

El sistema detecta automáticamente la URL base, pero si necesitas configurarla manualmente:

```php
// En config/config.php
define('MANUAL_BASE_URL', 'http://tu-dominio/quesos-leslie/');
```

### Configuración de WhatsApp (Futuro)

```php
// En config/config.php
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/');
define('WHATSAPP_TOKEN', 'tu_token_de_whatsapp');
```

### Configuración de Correo (Futuro)

```php
// Para notificaciones por email
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'tu_email@gmail.com');
define('MAIL_PASSWORD', 'tu_contraseña');
```

## 📱 Características Técnicas

### Seguridad
- Protección contra inyección SQL (PDO preparado)
- Validación de entrada en cliente y servidor
- Hashing seguro de contraseñas (PHP password_hash)
- Protección CSRF
- Sanitización de salidas XSS

### Performance
- Consultas optimizadas con índices
- Paginación automática
- Compresión de assets
- Cache de headers HTTP

### Responsividad
- Diseño completamente responsive
- Optimizado para móviles y tablets
- Interface touch-friendly

## 🚦 Roadmap de Desarrollo

### Fase 1 - Completada ✅
- [x] Estructura base MVC
- [x] Sistema de autenticación
- [x] Dashboard principal
- [x] Configuración automática de URL base
- [x] Sistema de testing

### Fase 2 - En Desarrollo 🚧
- [ ] Módulo de Producción completo
- [ ] Módulo de Pedidos avanzado
- [ ] Sistema de QR codes
- [ ] Gestión de rutas básica

### Fase 3 - Planificado 📋
- [ ] Integración WhatsApp
- [ ] Sistema de notificaciones
- [ ] Reportes avanzados
- [ ] App móvil complementaria

### Fase 4 - Futuro 🔮
- [ ] API REST
- [ ] Integración con sistemas contables
- [ ] Machine Learning para optimización
- [ ] IoT para sensores de temperatura

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request

## 📞 Soporte

Para soporte técnico o consultas:

- 📧 Email: soporte@quesosleslie.com
- 📱 WhatsApp: +52 555 123 4567
- 🌐 Sitio web: https://quesosleslie.com

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

**Desarrollado con ❤️ para Quesos y Productos Leslie**
