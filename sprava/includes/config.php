<?php
/**
 * CMS Configuration
 * Restaurant Menu Management System
 */

// Prevent direct access
defined('CMS_LOADED') or die('Direct access not allowed');

// Site configuration
define('SITE_NAME', 'Menu');
define('SITE_URL', ''); // Will be auto-detected if empty

// Security
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('CSRF_TOKEN_NAME', 'cms_csrf_token');

// Data storage paths
define('DATA_DIR', dirname(__DIR__) . '/data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('MENUS_FILE', DATA_DIR . 'menus.json');

// Restaurants configuration
define('RESTAURANTS', [
    'bufet' => [
        'id' => 'bufet',
        'name' => 'Bufet v Hlubokáně',
        'slug' => 'bufet-v-hlubokane',
        'color' => '#c8860a'
    ],
    'caffe' => [
        'id' => 'caffe',
        'name' => 'Nejen Caffé u Páji',
        'slug' => 'caffe-u-paji',
        'color' => '#7c6145'
    ]
]);

// Dish categories
define('DISH_CATEGORIES', [
    'soup' => 'Polévka',
    'main' => 'Hlavní jídlo',
    'dessert' => 'Dezert'
]);

// Days of week (Czech)
define('DAYS_OF_WEEK', [
    0 => 'Pondělí',
    1 => 'Úterý',
    2 => 'Středa',
    3 => 'Čtvrtek',
    4 => 'Pátek',
    5 => 'Sobota',
    6 => 'Neděle'
]);

// Default closed days (Saturday = 5, Sunday = 6)
define('DEFAULT_CLOSED_DAYS', [5, 6]);

// Timezone
date_default_timezone_set('Europe/Prague');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * Get base URL
 */
function getBaseUrl() {
    if (SITE_URL) {
        return SITE_URL;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Get path up to /sprava/
    $path = preg_replace('#/sprava.*$#', '/sprava', $path);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Get data directory, create if needed
 */
function ensureDataDirectory() {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    // Create .htaccess to protect data directory
    $htaccess = DATA_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }
}

