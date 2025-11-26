<?php
/**
 * Configuración de sesiones y autenticación
 * Sistema SIGSA
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Función para verificar si el usuario es admin
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Función para requerir login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /sigsa/login.php');  // CAMBIADO
        exit();
    }
}

// Función para requerir rol de admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /sigsa/dashboard.php');  // CAMBIADO
        exit();
    }
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nombre' => $_SESSION['nombre'] ?? '',
            'rol' => $_SESSION['rol'] ?? 'empleado',
            'id_empleado' => $_SESSION['id_empleado'] ?? null
        ];
    }
    return null;
}

// Función para hacer login
function login($username, $password) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT u.*, e.nombre 
            FROM Usuarios u 
            LEFT JOIN Empleados e ON u.id_empleado = e.id_empleado 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último acceso
            $updateStmt = $db->prepare("UPDATE Usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
            $updateStmt->execute([$user['id_usuario']]);
            
            // Establecer variables de sesión
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre'] = $user['nombre'] ?? $user['username'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['id_empleado'] = $user['id_empleado'];
            
            return true;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

// Función para hacer logout
function logout() {
    session_destroy();
    header('Location: /sigsa/login.php');  // CAMBIADO
    exit();
}

// Función para prevenir CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>