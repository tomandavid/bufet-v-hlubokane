<?php
/**
 * Save Menu Handler
 * Restaurant Menu Management System
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

// Verify CSRF
if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlashMessage('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');
    redirect('dashboard.php');
}

$restaurant = sanitize($_POST['restaurant'] ?? '');
$weekStart = sanitize($_POST['week_start'] ?? '');
$action = sanitize($_POST['action'] ?? 'save');
$daysData = $_POST['days'] ?? [];

// Validate restaurant
if (!isset(RESTAURANTS[$restaurant])) {
    setFlashMessage('error', 'Neplatná restaurace.');
    redirect('dashboard.php');
}

// Validate week start
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
    setFlashMessage('error', 'Neplatné datum týdne.');
    redirect('dashboard.php');
}

// Build week menu data
$weekMenu = createEmptyWeek($weekStart);
$errors = [];

foreach ($daysData as $dayIndex => $dayData) {
    $dayIndex = (int)$dayIndex;
    
    if ($dayIndex < 0 || $dayIndex > 6) {
        continue;
    }
    
    // Check if closed
    $isClosed = isset($dayData['closed']) && $dayData['closed'] === '1';
    $weekMenu['days'][$dayIndex]['closed'] = $isClosed;
    $weekMenu['days'][$dayIndex]['closedNote'] = sanitize($dayData['closedNote'] ?? '');
    
    // Process dishes
    $dishes = [];
    
    if (isset($dayData['dishes']) && is_array($dayData['dishes'])) {
        foreach ($dayData['dishes'] as $category => $categoryDishes) {
            if (!isset(DISH_CATEGORIES[$category])) {
                continue;
            }
            
            foreach ($categoryDishes as $index => $dish) {
                $name = trim(sanitize($dish['name'] ?? ''));
                $price = trim($dish['price'] ?? '');
                
                // Skip empty dishes
                if (empty($name)) {
                    continue;
                }
                
                // Validate price
                if ($price !== '' && !is_numeric($price)) {
                    $errors[] = DAYS_OF_WEEK[$dayIndex] . ': Cena musí být číslo';
                    continue;
                }
                
                if ($price !== '' && (float)$price < 0) {
                    $errors[] = DAYS_OF_WEEK[$dayIndex] . ': Cena nemůže být záporná';
                    continue;
                }
                
                // Dietary options
                $glutenFree = isset($dish['glutenFree']) && $dish['glutenFree'] === '1';
                $vegetarian = isset($dish['vegetarian']) && $dish['vegetarian'] === '1';
                
                $dishes[] = [
                    'category' => $category,
                    'name' => $name,
                    'price' => $price !== '' ? (int)$price : 0,
                    'glutenFree' => $glutenFree,
                    'vegetarian' => $vegetarian
                ];
            }
        }
    }
    
    $weekMenu['days'][$dayIndex]['dishes'] = $dishes;
}

// If there are validation errors, show them and redirect back
if (!empty($errors)) {
    setFlashMessage('error', 'Chyby: ' . implode('; ', $errors));
    redirect("dashboard.php?restaurant=$restaurant&week=$weekStart");
}

// Set status based on action
if ($action === 'publish') {
    $weekMenu['status'] = 'published';
    $weekMenu['publishedAt'] = date('Y-m-d H:i:s');
    $message = 'Menu bylo úspěšně publikováno!';
} else {
    $weekMenu['status'] = 'draft';
    $message = 'Menu bylo uloženo jako koncept.';
}

// Save menu
$result = saveWeekMenu($restaurant, $weekStart, $weekMenu);

if ($result) {
    logActivity('menu_saved', [
        'restaurant' => $restaurant,
        'week' => $weekStart,
        'action' => $action
    ]);
    setFlashMessage('success', $message);
} else {
    setFlashMessage('error', 'Nepodařilo se uložit menu. Zkontrolujte oprávnění k zápisu.');
}

redirect("dashboard.php?restaurant=$restaurant&week=$weekStart");

