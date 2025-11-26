# Instalación del Sistema SIGSA

## Requisitos Previos

- XAMPP instalado (incluye Apache, MySQL y PHP)
- Navegador web moderno
- Editor de texto (opcional, para configuración)

## Pasos de Instalación

### 1. Iniciar XAMPP

1. Abre el panel de control de XAMPP
2. Inicia los servicios **Apache** y **MySQL**
3. Verifica que ambos estén en color verde (activos)

### 2. Copiar Archivos del Sistema

1. Descarga y extrae el archivo ZIP del sistema SIGSA
2. Copia la carpeta completa `sigsa` a:
   - Windows: `C:\xampp\htdocs\sigsa`
   - Mac/Linux: `/opt/lampp/htdocs/sigsa`

### 3. Crear la Base de Datos

1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Haz clic en "Nueva" en el panel izquierdo
3. Nombre de la base de datos: `sigsa_db`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Haz clic en "Crear"

### 4. Importar la Base de Datos

1. Selecciona la base de datos `sigsa_db` que acabas de crear
2. Haz clic en la pestaña "Importar"
3. Haz clic en "Seleccionar archivo"
4. Busca y selecciona el archivo `database/sigsa_database.sql`
5. Haz clic en "Continuar" al final de la página
6. Espera a que termine la importación

### 5. Configurar la Conexión (Opcional)

Si tus credenciales de MySQL son diferentes, edita el archivo `config/database.php`:

\`\`\`php
$host = 'localhost';
$dbname = 'sigsa_db';
$username = 'root';        // Cambia si es necesario
$password = '';             // Cambia si tienes contraseña en MySQL
\`\`\`

### 6. Acceder al Sistema

1. Abre tu navegador
2. Ve a: `http://localhost/sigsa`
3. Deberías ver la página de inicio de sesión

## Credenciales de Acceso

### Administrador
- **Usuario:** admin
- **Contraseña:** admin123

### Empleado (Opcional)
- **Usuario:** empleado
- **Contraseña:** empleado123

## Estructura de Carpetas

\`\`\`
sigsa/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   ├── database.php
│   └── session.php
├── database/
│   └── sigsa_database.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── clientes/
│   ├── vehiculos/
│   ├── ordenes/
│   ├── repuestos/
│   ├── empleados/
│   └── reportes/
├── login.php
├── logout.php
├── dashboard.php
└── INSTALACION.md
\`\`\`

## Funcionalidades del Sistema

### Módulos Principales

1. **Gestión de Clientes**
   - CRUD completo de clientes
   - Historial de vehículos y servicios

2. **Gestión de Vehículos**
   - Registro de vehículos por cliente
   - Historial de servicios

3. **Órdenes de Servicio**
   - Creación y seguimiento de órdenes
   - Asignación de empleados
   - Control de repuestos utilizados
   - Estados: Pendiente, En Proceso, Completado, Entregado, Cancelado

4. **Inventario de Repuestos**
   - Control de stock
   - Alertas de stock mínimo
   - Registro de proveedores

5. **Gestión de Empleados** (Solo Administradores)
   - CRUD de usuarios del sistema
   - Roles: Administrador y Empleado

6. **Reportes** (Solo Administradores)
   - Reporte de ventas por período
   - Reporte de inventario
   - Reporte de clientes frecuentes
   - Reporte de desempeño de empleados
   - Reporte financiero

### Características Técnicas

- **Base de Datos:** MySQL con características avanzadas
  - Procedimientos almacenados
  - Triggers para auditoría
  - Funciones personalizadas
  - Vistas para reportes
  - Índices optimizados

- **Seguridad:**
  - Sistema de autenticación con sesiones PHP
  - Control de acceso por roles
  - Protección contra SQL injection (PDO con prepared statements)
  - Validación de datos

- **Interfaz:**
  - Diseño responsive (adaptable a móviles)
  - Interfaz intuitiva y profesional
  - Colores corporativos del sector automotriz

## Solución de Problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté activo en XAMPP
- Confirma las credenciales en `config/database.php`
- Asegúrate de haber creado la base de datos `sigsa_db`

### Página en blanco
- Verifica los logs de errores de PHP en `C:\xampp\php\logs\php_error_log`
- Asegúrate de que todas las rutas de archivos sean correctas

### No puedo iniciar sesión
- Verifica que los datos se hayan importado correctamente
- Usa las credenciales por defecto: admin/admin123

### Errores al guardar datos
- Verifica permisos de escritura en las carpetas
- Revisa que todos los campos requeridos estén llenos

## Soporte

Para reportar problemas o solicitar ayuda:
1. Verifica los logs de error de PHP
2. Revisa la consola del navegador (F12)
3. Documenta los pasos para reproducir el error

## Próximos Pasos

1. Cambia las contraseñas por defecto
2. Crea usuarios empleados según tu equipo
3. Configura los datos de tu taller
4. Ingresa tu inventario de repuestos
5. Registra tus clientes existentes
6. Comienza a crear órdenes de servicio

¡El sistema está listo para usar!
