<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';

$currentUser = getCurrentUser();
$currentPage = 'consultas-complejas';
$pageTitle = 'Analisis';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$pathParts = explode('/', $scriptName);
array_pop($pathParts); // Eliminar el archivo actual
$basePath = implode('/', $pathParts);

// Determinar la ruta base del proyecto
if (strpos($basePath, 'pages') !== false) {
    $baseUrl = '/sigsa'; // Si estamos en una subcarpeta pages
} else {
    $baseUrl = rtrim(str_replace('/pages', '', $basePath), '/');
}

$consulta_actual = $_GET['consulta'] ?? 'clientes_vehiculos';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGSA - Consultas Complejas</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .consultas-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .consulta-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #007bff;
            cursor: pointer;
            transition: all 0.3s;
        }

        .consulta-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .consulta-card h4 {
            margin: 0 0 10px 0;
            color: #007bff;
        }

        .consulta-card p {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }

        .filtros-complejos {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .filtro-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-car-side"></i>
                <h2>SIGSA</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= $baseUrl ?>/dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Pagina Principal</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/clientes/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'clientes') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/vehiculos/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'vehiculos') ? 'active' : '' ?>">
                    <i class="fas fa-car"></i>
                    <span>Vehículos</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/ordenes/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'ordenes') ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Órdenes</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/pagos/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'pagos') ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Gestión de Pagos</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/repuestos/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'repuestos') ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Repuestos</span>
                </a>

                <a href="<?= $baseUrl ?>/pages/reportes/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reportes') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
                <a href="<?= $baseUrl ?>/pages/consultas-filtradas/consultas-complejas/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'consultas-complejas') ? 'active' : '' ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>Analisis</span>
                </a>

                <?php if (isAdmin()): ?>
                    <a href="<?= $baseUrl ?>/pages/empleados/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'empleados') ? 'active' : '' ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Empleados</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($currentUser['nombre']) ?></div>
                        <div class="user-role"><?= ucfirst($currentUser['rol']) ?></div>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1><?= $pageTitle ?></h1>
                <div class="top-bar-actions">
                    <span class="date-time" id="currentDateTime"></span>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- El contenido de la página va aquí -->

                <!-- Selector de Consultas -->
                <div class="consultas-container">
                    <div class="consulta-card" onclick="cambiarConsulta('clientes_vehiculos')">
                        <h4>👥 Clientes con Vehículos</h4>
                        <p>Clientes filtrados por características de sus vehículos</p>
                    </div>

                    <div class="consulta-card" onclick="cambiarConsulta('clientes_ordenes')">
                        <h4>👥📋 Clientes con Órdenes</h4>
                        <p>Clientes filtrados por estado y monto de órdenes</p>
                    </div>

                    <div class="consulta-card" onclick="cambiarConsulta('empleados_ordenes')">
                        <h4>👨‍💼📋 Empleados con Órdenes</h4>
                        <p>Rendimiento de empleados por órdenes completadas</p>
                    </div>

                    <div class="consulta-card" onclick="cambiarConsulta('vehiculos_clientes')">
                        <h4>🚗👥 Vehículos con Clientes</h4>
                        <p>Vehículos filtrados por tipo de cliente dueño</p>
                    </div>

                    <div class="consulta-card" onclick="cambiarConsulta('productos_ordenes')">
                        <h4>📦📋 Productos en Órdenes</h4>
                        <p>Productos filtrados por ventas y stock</p>
                    </div>

                    <div class="consulta-card" onclick="cambiarConsulta('servicios_demanda')">
                        <h4>🔧📊 Servicios por Demanda</h4>
                        <p>Análisis de servicios por precio y demanda</p>
                    </div>
                </div>

                <!-- Filtros y Resultados Dinámicos -->
                <div class="filtros-complejos" id="filtrosComplejosContainer">
                    <?php include __DIR__ . "/filtros_$consulta_actual.php"; ?>
                </div>

                <div class="resultados-container" id="resultadosComplejosContainer">
                    <?php include __DIR__ . "/resultados_$consulta_actual.php"; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function cambiarConsulta(consulta) {
            window.location.href = `index.php?consulta=${consulta}`;
        }

        function aplicarFiltrosComplejos() {
            const formData = new FormData();
            const consulta = '<?= $consulta_actual ?>';

            // Recoger todos los valores de los filtros
            document.querySelectorAll('#filtrosComplejosContainer input, #filtrosComplejosContainer select').forEach(input => {
                if (input.value) {
                    formData.append(input.name, input.value);
                }
            });

            // AJAX para actualizar resultados
            fetch(`actualizar_resultados_complejos.php?consulta=${consulta}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('resultadosComplejosContainer').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Aplicar al cambiar cualquier filtro
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('#filtrosComplejosContainer input, #filtrosComplejosContainer select').forEach(element => {
                element.addEventListener('change', aplicarFiltrosComplejos);
                element.addEventListener('input', aplicarFiltrosComplejos);
            });

            // Actualizar fecha y hora
            function updateDateTime() {
                const now = new Date();
                document.getElementById('currentDateTime').textContent = now.toLocaleString('es-ES');
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);
        });
    </script>
</body>

</html>