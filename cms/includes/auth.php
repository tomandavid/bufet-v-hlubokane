<?php
/**
 * Authentication Functions
 * Restaurant Menu Management System
 */

defined('CMS_LOADED') or die('Direct access not allowed');

/**
 * Initialize session with security settings
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
    }
    
    // Check session lifetime
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    initSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user
 */
function login($username, $password) {
    $users = getUsers();
    
    if (!isset($users[$username])) {
        return ['success' => false, 'error' => 'Neplatné přihlašovací údaje'];
    }
    
    $user = $users[$username];
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Neplatné přihlašovací údaje'];
    }
    
    initSession();
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $username;
    $_SESSION['user_name'] = $user['name'] ?? $username;
    $_SESSION['authenticated'] = true;
    $_SESSION['last_activity'] = time();
    
    return ['success' => true];
}

/**
 * Logout user
 */
function logout() {
    initSession();
    
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name']
    ];
}

/**
 * Get all users
 */
function getUsers() {
    ensureDataDirectory();
    
    if (!file_exists(USERS_FILE)) {
        // Create default admin user
        $defaultUsers = [
            'admin' => [
                'name' => 'Administrátor',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin'
            ]
        ];
        saveUsers($defaultUsers);
        return $defaultUsers;
    }
    
    $content = file_get_contents(USERS_FILE);
    return json_decode($content, true) ?: [];
}

/**
 * Save users
 */
function saveUsers($users) {
    ensureDataDirectory();
    return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Change user password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $users = getUsers();
    
    if (!isset($users[$userId])) {
        return ['success' => false, 'error' => 'Uživatel nenalezen'];
    }
    
    if (!password_verify($currentPassword, $users[$userId]['password'])) {
        return ['success' => false, 'error' => 'Nesprávné aktuální heslo'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Nové heslo musí mít alespoň 6 znaků'];
    }
    
    $users[$userId]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    saveUsers($users);
    
    return ['success' => true];
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    initSession();
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    initSession();
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

