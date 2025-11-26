# Sistema Integral de Gestión de Servicios Automotrices (SIGSA)

Sistema de gestión para talleres automotrices desarrollado en PHP, MySQL y diseñado para XAMPP/phpMyAdmin.

##  Características Principales

- **Gestión de Clientes**: CRUD completo con historial de servicios
- **Gestión de Vehículos**: Registro de vehículos asociados a clientes
- **Órdenes de Servicio**: Creación, seguimiento y actualización de servicios
- **Inventario de Repuestos**: Control de stock con alertas automáticas
- **Gestión de Empleados**: Roles y permisos (Administrador/Empleado)
- **Dashboard Interactivo**: Métricas en tiempo real y gráficos
- **Reportes Avanzados**: Ventas, servicios, inventario y clientes
- **Base de Datos Avanzada**: Procedimientos almacenados, triggers, vistas, funciones e índices

##  Requisitos Previos

- **XAMPP** (versión 7.4 o superior)
- **PHP** 7.4 o superior
- **MySQL** 5.7 o superior
- **Navegador web** moderno (Chrome, Firefox, Edge)

##  Instalación Paso a Paso

### 1. Descargar e Instalar XAMPP

1. Descarga XAMPP desde: https://www.apachefriends.org/
2. Instala XAMPP en tu computadora (recomendado: `C:\xampp`)
3. Abre el **Panel de Control de XAMPP**

### 2. Configurar el Proyecto

1. **Descargar los archivos del proyecto**
   - Descarga todos los archivos del sistema SIGSA
   - Descomprime el archivo ZIP si es necesario

2. **Colocar archivos en htdocs**
   \`\`\`
   Copia la carpeta del proyecto a:
   C:\xampp\htdocs\sigsa
   \`\`\`

3. **Iniciar Servicios XAMPP**
   - Abre el Panel de Control de XAMPP
   - Haz clic en **"Start"** en Apache
   - Haz clic en **"Start"** en MySQL
   - Ambos deben mostrar luz verde

### 3. Crear la Base de Datos

1. **Abrir phpMyAdmin**
   - Ve a tu navegador y abre: `http://localhost/phpmyadmin`

2. **Crear nueva base de datos**
   - Haz clic en **"Nueva"** o **"New"** en el panel izquierdo
   - Nombre de la base de datos: `sigsa`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Haz clic en **"Crear"**

3. **Importar estructura de la base de datos**
   - Selecciona la base de datos `sigsa` que acabas de crear
   - Haz clic en la pestaña **"Importar"**
   - Haz clic en **"Seleccionar archivo"**
   - Busca y selecciona: `sigsa/database/sigsa_database.sql`
   - Desplázate hacia abajo y haz clic en **"Continuar"** o **"Go"**
   - Espera a que termine la importación (verás mensaje de éxito)

4. **Importar procedimientos almacenados**
   - En la misma base de datos `sigsa`
   - Haz clic nuevamente en **"Importar"**
   - Selecciona el archivo: `sigsa/database/procedimientos_almacenados.sql`
   - Haz clic en **"Continuar"**

5. **Importar triggers y funciones**
   - Repite el proceso de importación con: `sigsa/database/triggers_funciones.sql`

6. **Importar vistas**
   - Repite el proceso de importación con: `sigsa/database/vistas.sql`

### 4. Configurar Conexión a la Base de Datos

1. Abre el archivo: `sigsa/config/database.php`
2. Verifica que la configuración sea correcta:

\`\`\`php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Usuario por defecto de XAMPP
define('DB_PASS', '');              // Sin contraseña por defecto
define('DB_NAME', 'sigsa');
\`\`\`

**IMPORTANTE**: Si tu XAMPP tiene una contraseña para MySQL diferente, cámbiala en `DB_PASS`.

### 5. Acceder al Sistema

1. **Abrir el sistema en el navegador**
   \`\`\`
   http://localhost/sigsa
   \`\`\`

2. **Credenciales de acceso por defecto**

   **Administrador:**
   - Usuario: `admin`
   - Contraseña: `password`

   **Empleado:**
   - Usuario: `mecanico1`
   - Contraseña: `password`

##  Estructura del Proyecto

\`\`\`
sigsa/
├── assets/
│   ├── css/
│   │   └── style.css           # Estilos del sistema
│   └── js/
│       └── main.js             # JavaScript principal
├── config/
│   ├── database.php            # Configuración de BD
│   └── session.php             # Gestión de sesiones
├── database/
│   ├── sigsa_database.sql      # Estructura de la BD
│   ├── procedimientos_almacenados.sql
│   ├── triggers_funciones.sql
│   └── vistas.sql
├── includes/
│   ├── header.php              # Encabezado común
│   └── footer.php              # Pie de página común
├── pages/
│   ├── clientes/               # Módulo de clientes
│   ├── vehiculos/              # Módulo de vehículos
│   ├── ordenes/                # Módulo de órdenes
│   ├── repuestos/              # Módulo de repuestos
│   ├── empleados/              # Módulo de empleados
│   └── reportes/               # Módulo de reportes
├── dashboard.php               # Panel principal
├── login.php                   # Página de inicio de sesión
├── logout.php                  # Cerrar sesión
├── index.php                   # Redirección a login
└── README.md                   # Este archivo
\`\`\`

##  Funcionalidades Implementadas

### Base de Datos Avanzada

 **Procedimientos Almacenados**
- `sp_crear_orden_servicio`: Crear órdenes con validaciones
- `sp_registrar_pago`: Procesar pagos y actualizar estados
- `sp_actualizar_stock`: Gestión automática de inventario
- `sp_calcular_comisiones`: Cálculo de comisiones por empleado

 **Triggers**
- `tr_actualizar_stock_repuesto`: Actualización automática de stock
- `tr_actualizar_total_orden`: Recalcular totales automáticamente
- `tr_historial_cambios_orden`: Auditoría de cambios
- `tr_alerta_stock_bajo`: Notificaciones de stock mínimo

 **Funciones**
- `fn_calcular_total_orden`: Calcular total de una orden
- `fn_obtener_stock_repuesto`: Consultar stock disponible
- `fn_dias_desde_servicio`: Calcular días desde último servicio
- `fn_servicios_pendientes_cliente`: Contar servicios pendientes

 **Vistas**
- `vw_ordenes_completas`: Vista completa de órdenes
- `vw_clientes_frecuentes`: Clientes con más servicios
- `vw_repuestos_bajo_stock`: Repuestos con alerta
- `vw_rendimiento_empleados`: Métricas por empleado

 **Índices Optimizados**
- Índices en claves foráneas
- Índices en campos de búsqueda frecuente
- Índices compuestos para consultas complejas

### Módulos del Sistema

 **Gestión de Clientes**
- Alta, baja, modificación y consulta
- Historial de servicios por cliente
- Búsqueda y filtros avanzados

 **Gestión de Vehículos**
- Registro de vehículos por cliente
- Historial de servicios del vehículo
- Información detallada (marca, modelo, año, placas)

 **Órdenes de Servicio**
- Creación de órdenes con detalles
- Asignación de empleados
- Estados: Pendiente, En Proceso, Completado, Cancelado
- Registro de repuestos utilizados
- Cálculo automático de totales

 **Inventario de Repuestos**
- Control de stock en tiempo real
- Alertas de stock mínimo
- Historial de movimientos
- Precios y proveedores

 **Gestión de Empleados**
- Roles: Administrador y Empleado
- Control de acceso por rol
- Registro de actividades

 **Reportes e Informes**
- Reporte de ventas por período
- Servicios más solicitados
- Clientes frecuentes
- Estado de inventario
- Rendimiento de empleados

 **Dashboard Interactivo**
- Métricas en tiempo real
- Gráficos de ventas
- Órdenes pendientes
- Alertas de stock bajo

##  Seguridad

- Validación de sesiones en todas las páginas
- Protección contra inyección SQL (prepared statements)
- Control de acceso basado en roles
- Sanitización de entradas de usuario
- Encriptación de contraseñas (password_hash)

##  Solución de Problemas

### Apache no inicia
- **Causa**: Puerto 80 ocupado por otro programa (Skype, IIS)
- **Solución**: 
  1. Cierra Skype o el programa que usa el puerto 80
  2. O cambia el puerto de Apache en XAMPP (Config > Apache > httpd.conf)

### MySQL no inicia
- **Causa**: Puerto 3306 ocupado
- **Solución**: 
  1. Verifica que no tengas otro MySQL instalado
  2. Cierra otros programas que usen el puerto 3306

### Error de conexión a la base de datos
- Verifica que MySQL esté iniciado en XAMPP (luz verde)
- Revisa las credenciales en `config/database.php`
- Asegúrate de haber importado todos los archivos SQL

### No puedo iniciar sesión
- Verifica que hayas importado `sigsa_database.sql` correctamente
- Los usuarios se crean automáticamente con la importación
- Usa las credenciales por defecto (ver sección "Acceder al Sistema")

### Las páginas no cargan estilos
- Verifica que la carpeta `assets` esté completa
- Revisa que Apache esté funcionando correctamente
- Limpia la caché del navegador (Ctrl + F5)

### Error "Call to undefined function"
- Asegúrate de haber importado todos los archivos SQL
- Verifica que `procedimientos_almacenados.sql` se importó correctamente
- Revisa los logs de PHP en XAMPP

##  Soporte

Para reportar problemas o solicitar ayuda:
1. Verifica que seguiste todos los pasos de instalación
2. Revisa la sección "Solución de Problemas"
3. Consulta los logs de error de XAMPP

##  Notas Adicionales

- **Cambiar credenciales**: Se recomienda cambiar las contraseñas por defecto después de la primera instalación
- **Respaldos**: Exporta la base de datos regularmente desde phpMyAdmin
- **Desarrollo**: El sistema está listo para usar en localhost
- **Producción**: Para producción, configura un servidor web con HTTPS

##  Características Técnicas

- **Lenguaje**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Arquitsectura**: MVC simplificado
- **Patrones**: Prepared Statements, Sesiones seguras
- **Características SQL**: Procedimientos, Triggers, Funciones, Vistas, Índices

---

**Versión**: 1.0.0  
**Fecha**: 2024  
**Sistema**: SIGSA - Sistema Integral de Gestión de Servicios Automotrices

```

```
sigsa
├─ assets
│  ├─ css
│  │  └─ style.css
│  └─ js
│     └─ main.js
├─ config
│  ├─ database.php
│  └─ session.php
├─ dashboard.php
├─ database
│  ├─ funciones.sql
│  ├─ procedimientos_almacenados.sql
│  ├─ sigsa_database.sql
│  ├─ triggers.sql
│  └─ vistas.sql
├─ includes
│  ├─ footer.php
│  └─ header.php
├─ INSTALACION.md
├─ login.php
├─ logout.php
├─ pages
│  ├─ clientes
│  │  ├─ create.php
│  │  ├─ delete.php
│  │  ├─ edit.php
│  │  └─ index.php
│  ├─ consultas-filtradas
│  │  └─ consultas-complejas
│  │     ├─ actualizar_resultados_complejos.php
│  │     ├─ filtros_clientes_ordenes.php
│  │     ├─ filtros_clientes_vehiculos.php
│  │     ├─ filtros_empleados_ordenes.php
│  │     ├─ filtros_productos_ordenes.php
│  │     ├─ filtros_servicios_demanda.php
│  │     ├─ filtros_vehiculos_clientes.php
│  │     ├─ index.php
│  │     ├─ resultados_clientes_ordenes.php
│  │     ├─ resultados_clientes_vehiculos.php
│  │     ├─ resultados_empleados_ordenes.php
│  │     ├─ resultados_productos_ordenes.php
│  │     ├─ resultados_servicios_demanda.php
│  │     └─ resultados_vehiculos_clientes.php
│  ├─ empleados
│  │  ├─ create.php
│  │  ├─ delete.php
│  │  ├─ edit.php
│  │  └─ index.php
│  ├─ ordenes
│  │  ├─ create.php
│  │  ├─ delete.php
│  │  ├─ edit.php
│  │  ├─ index.php
│  │  ├─ update_estado.php
│  │  └─ view.php
│  ├─ pagos
│  │  ├─ create.php
│  │  └─ index.php
│  ├─ reportes
│  │  ├─ clientes.php
│  │  ├─ empleados.php
│  │  ├─ financiero.php
│  │  ├─ index.php
│  │  ├─ inventario.php
│  │  ├─ ordenes.php
│  │  └─ ventas.php
│  ├─ repuestos
│  │  ├─ create.php
│  │  ├─ delete.php
│  │  ├─ edit.php
│  │  └─ index.php
│  └─ vehiculos
│     ├─ create.php
│     ├─ delete.php
│     ├─ edit.php
│     └─ index.php
└─ README.md

```
