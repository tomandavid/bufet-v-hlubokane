<?php
/**
 * Public API Endpoint
 * Restaurant Menu Management System
 * 
 * Provides menu data for frontend websites
 * 
 * Endpoints:
 * - GET api.php?restaurant=bufet - Get current week menu for Bufet v Hlubokáně
 * - GET api.php?restaurant=caffe - Get current week menu for Nejen Caffé u Páji
 * - GET api.php?restaurant=bufet&week=2024-01-15 - Get specific week menu
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/functions.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get restaurant parameter
$restaurant = sanitize($_GET['restaurant'] ?? '');

if (empty($restaurant)) {
    jsonResponse(['error' => 'Missing restaurant parameter'], 400);
}

// Validate restaurant
if (!isset(RESTAURANTS[$restaurant])) {
    jsonResponse(['error' => 'Invalid restaurant'], 400);
}

// Get week parameter (optional)
$week = sanitize($_GET['week'] ?? '');

if ($week) {
    // Validate date format
    $dt = DateTime::createFromFormat('Y-m-d', $week);
    if (!$dt) {
        jsonResponse(['error' => 'Invalid date format. Use Y-m-d'], 400);
    }
    $weekStart = getWeekStartDate($week);
} else {
    $weekStart = getWeekStartDate(date('Y-m-d'));
}

// Get menu data
$weekMenu = getWeekMenu($restaurant, $weekStart);

// Check if menu is published (or if we should show draft for current/future weeks)
$showDraft = isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$showDraft && isset($weekMenu['status']) && $weekMenu['status'] !== 'published') {
    // For unpublished menus, check if it's current or future week
    $currentWeekStart = getWeekStartDate(date('Y-m-d'));
    
    if ($weekStart < $currentWeekStart) {
        // Past week, unpublished - return empty
        jsonResponse([
            'restaurant' => RESTAURANTS[$restaurant],
            'week' => $weekStart,
            'weekRange' => formatWeekRange($weekStart),
            'status' => 'not_available',
            'menu' => []
        ]);
    }
}

// Transform and return menu data
$menu = transformMenuForFrontend($weekMenu);

jsonResponse([
    'restaurant' => RESTAURANTS[$restaurant],
    'week' => $weekStart,
    'weekRange' => formatWeekRange($weekStart),
    'status' => $weekMenu['status'] ?? 'draft',
    'lastModified' => $weekMenu['lastModified'] ?? null,
    'menu' => $menu
]);

