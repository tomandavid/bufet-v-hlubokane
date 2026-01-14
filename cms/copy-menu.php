<?php
/**
 * Copy Menu Handler
 * Restaurant Menu Management System
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

// Handle both GET (AJAX) and POST
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Get parameters
$restaurant = sanitize($_REQUEST['restaurant'] ?? '');
$sourceWeek = sanitize($_REQUEST['source_week'] ?? '');
$targetWeek = sanitize($_REQUEST['target_week'] ?? '');

// Validate
if (!isset(RESTAURANTS[$restaurant])) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Neplatná restaurace'], 400);
    }
    setFlashMessage('error', 'Neplatná restaurace.');
    redirect('dashboard.php');
}

if (empty($sourceWeek) || empty($targetWeek)) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Chybí parametry'], 400);
    }
    setFlashMessage('error', 'Chybí parametry.');
    redirect('dashboard.php');
}

// Copy the menu
$result = copyWeekMenu($restaurant, $sourceWeek, $targetWeek);

if ($result['success']) {
    logActivity('menu_copied', [
        'restaurant' => $restaurant,
        'source' => $sourceWeek,
        'target' => $targetWeek
    ]);
    
    if ($isAjax) {
        jsonResponse(['success' => true, 'message' => 'Menu bylo zkopírováno']);
    }
    
    setFlashMessage('success', 'Menu z předchozího týdne bylo zkopírováno.');
} else {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
    
    setFlashMessage('error', $result['error']);
}

redirect("dashboard.php?restaurant=$restaurant&week=$targetWeek");

