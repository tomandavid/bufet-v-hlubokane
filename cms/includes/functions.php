<?php
/**
 * Helper Functions
 * Restaurant Menu Management System
 */

defined('CMS_LOADED') or die('Direct access not allowed');

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message helper
 */
function setFlashMessage($type, $message) {
    initSession();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    initSession();
    
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    
    return $flash;
}

/**
 * Format Czech date
 */
function formatCzechDate($date) {
    $months = [
        1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna',
        5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna',
        9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince'
    ];
    
    $dt = new DateTime($date);
    $day = $dt->format('j');
    $month = $months[(int)$dt->format('n')];
    $year = $dt->format('Y');
    
    return "$day. $month $year";
}

/**
 * Format week range for display
 */
function formatWeekRange($weekStart) {
    $start = new DateTime($weekStart);
    $end = clone $start;
    $end->modify('+6 days');
    
    return formatCzechDate($start->format('Y-m-d')) . ' – ' . formatCzechDate($end->format('Y-m-d'));
}

/**
 * Check if date is in current week
 */
function isCurrentWeek($weekStart) {
    $currentWeekStart = getWeekStartDate(date('Y-m-d'));
    return $weekStart === $currentWeekStart;
}

/**
 * Check if date is in future
 */
function isFutureWeek($weekStart) {
    $currentWeekStart = getWeekStartDate(date('Y-m-d'));
    return $weekStart > $currentWeekStart;
}

/**
 * Log activity
 */
function logActivity($action, $details = []) {
    $logFile = DATA_DIR . 'activity.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => getCurrentUser()['id'] ?? 'anonymous',
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Get asset URL with cache busting
 */
function asset($path) {
    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return $path . '?v=' . $version;
}

/**
 * Simple template include
 */
function includeTemplate($name, $data = []) {
    extract($data);
    include __DIR__ . '/../templates/' . $name . '.php';
}

/**
 * Escape for JavaScript
 */
function jsEscape($string) {
    return json_encode($string, JSON_UNESCAPED_UNICODE);
}

/**
 * Get ordinal suffix for Czech numbers
 */
function getOrdinalCzech($number) {
    return $number . '.';
}

/**
 * Human readable time difference
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return 'před ' . $diff->y . ' ' . ($diff->y == 1 ? 'rokem' : 'lety');
    if ($diff->m > 0) return 'před ' . $diff->m . ' ' . ($diff->m == 1 ? 'měsícem' : 'měsíci');
    if ($diff->d > 0) return 'před ' . $diff->d . ' ' . ($diff->d == 1 ? 'dnem' : 'dny');
    if ($diff->h > 0) return 'před ' . $diff->h . ' ' . ($diff->h == 1 ? 'hodinou' : 'hodinami');
    if ($diff->i > 0) return 'před ' . $diff->i . ' ' . ($diff->i == 1 ? 'minutou' : 'minutami');
    
    return 'právě teď';
}

