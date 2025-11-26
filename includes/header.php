<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGSA - Sistema de Gestión Automotriz</title>
    <!--  nombre de archivo CSS de styles.css a style.css -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <!-- Corregidas rutas usando variable $baseUrl -->
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

                <!--  NUEVO: Enlace a Gestión de Pagos -->
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
                <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                <div class="top-bar-actions">
                    <span class="date-time" id="currentDateTime"></span>
                </div>
            </header>

            <div class="content-wrapper">