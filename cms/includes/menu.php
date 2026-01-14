<?php
/**
 * Menu Data Functions
 * Restaurant Menu Management System
 */

defined('CMS_LOADED') or die('Direct access not allowed');

/**
 * Get all menus data
 */
function getAllMenus() {
    ensureDataDirectory();
    
    if (!file_exists(MENUS_FILE)) {
        return [];
    }
    
    $content = file_get_contents(MENUS_FILE);
    return json_decode($content, true) ?: [];
}

/**
 * Save all menus data
 */
function saveAllMenus($menus) {
    ensureDataDirectory();
    return file_put_contents(MENUS_FILE, json_encode($menus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Get menu for specific restaurant and week
 * 
 * @param string $restaurantId Restaurant identifier (bufet/caffe)
 * @param string $weekStart Week start date (Y-m-d format, Monday)
 * @return array Menu data
 */
function getWeekMenu($restaurantId, $weekStart) {
    $menus = getAllMenus();
    
    if (!isset($menus[$restaurantId][$weekStart])) {
        // Return default empty week structure
        return createEmptyWeek($weekStart);
    }
    
    return $menus[$restaurantId][$weekStart];
}

/**
 * Save menu for specific restaurant and week
 */
function saveWeekMenu($restaurantId, $weekStart, $weekData) {
    $menus = getAllMenus();
    
    if (!isset($menus[$restaurantId])) {
        $menus[$restaurantId] = [];
    }
    
    // Add metadata
    $weekData['lastModified'] = date('Y-m-d H:i:s');
    $weekData['modifiedBy'] = getCurrentUser()['id'] ?? 'system';
    
    $menus[$restaurantId][$weekStart] = $weekData;
    
    return saveAllMenus($menus);
}

/**
 * Create empty week structure
 */
function createEmptyWeek($weekStart) {
    $week = [
        'weekStart' => $weekStart,
        'status' => 'draft',
        'days' => []
    ];
    
    $startDate = new DateTime($weekStart);
    
    for ($i = 0; $i < 7; $i++) {
        $date = clone $startDate;
        $date->modify("+$i days");
        $dateStr = $date->format('Y-m-d');
        $dayIndex = $i; // 0 = Monday, 6 = Sunday
        
        $week['days'][$dayIndex] = [
            'date' => $dateStr,
            'dayName' => DAYS_OF_WEEK[$dayIndex],
            'closed' => in_array($dayIndex, DEFAULT_CLOSED_DAYS),
            'closedNote' => in_array($dayIndex, DEFAULT_CLOSED_DAYS) ? 'Zavřeno' : '',
            'dishes' => []
        ];
    }
    
    return $week;
}

/**
 * Get current week menu for a restaurant (for frontend API)
 */
function getCurrentWeekMenu($restaurantId) {
    $weekStart = getWeekStartDate(date('Y-m-d'));
    $menu = getWeekMenu($restaurantId, $weekStart);
    
    // Transform to frontend format
    return transformMenuForFrontend($menu);
}

/**
 * Transform menu data for frontend consumption
 */
function transformMenuForFrontend($weekMenu) {
    $result = [];
    
    if (!isset($weekMenu['days'])) {
        return $result;
    }
    
    foreach ($weekMenu['days'] as $dayIndex => $dayData) {
        // Skip weekends if closed (frontend only shows Mon-Fri)
        if ($dayIndex > 4) {
            continue;
        }
        
        if ($dayData['closed']) {
            $result[$dayIndex] = [
                'closed' => true,
                'closedNote' => $dayData['closedNote'] ?? 'Zavřeno'
            ];
            continue;
        }
        
        $dayResult = [
            'closed' => false,
            'soup' => [],
            'main' => [],
            'dessert' => []
        ];
        
        if (isset($dayData['dishes'])) {
            $dishNumber = 1;
            foreach ($dayData['dishes'] as $dish) {
                if (empty($dish['name'])) continue;
                
                $category = $dish['category'] ?? 'main';
                $dishData = [
                    'name' => $dish['name'],
                    'price' => formatPrice($dish['price'] ?? 0)
                ];
                
                // Add numbering for main dishes
                if ($category === 'main') {
                    $dishData['number'] = (string)$dishNumber++;
                }
                
                // Add dietary tags
                if (!empty($dish['glutenFree'])) {
                    $dishData['glutenFree'] = true;
                }
                if (!empty($dish['vegetarian'])) {
                    $dishData['vegetarian'] = true;
                }
                
                $dayResult[$category][] = $dishData;
            }
        }
        
        $result[$dayIndex] = $dayResult;
    }
    
    return $result;
}

/**
 * Format price for display
 */
function formatPrice($price) {
    if (is_numeric($price)) {
        return number_format((float)$price, 0, ',', ' ') . ' Kč';
    }
    
    // If already formatted or has Kč, return as is
    if (strpos($price, 'Kč') !== false) {
        return $price;
    }
    
    return $price . ' Kč';
}

/**
 * Get Monday of the week for a given date
 */
function getWeekStartDate($date) {
    $dt = new DateTime($date);
    $dayOfWeek = (int)$dt->format('N'); // 1 = Monday, 7 = Sunday
    
    if ($dayOfWeek !== 1) {
        $dt->modify('-' . ($dayOfWeek - 1) . ' days');
    }
    
    return $dt->format('Y-m-d');
}

/**
 * Get next week start date
 */
function getNextWeekStart($currentWeekStart) {
    $dt = new DateTime($currentWeekStart);
    $dt->modify('+7 days');
    return $dt->format('Y-m-d');
}

/**
 * Get previous week start date
 */
function getPreviousWeekStart($currentWeekStart) {
    $dt = new DateTime($currentWeekStart);
    $dt->modify('-7 days');
    return $dt->format('Y-m-d');
}

/**
 * Validate day menu data
 */
function validateDayMenu($dayData) {
    $errors = [];
    
    if (isset($dayData['dishes']) && is_array($dayData['dishes'])) {
        foreach ($dayData['dishes'] as $index => $dish) {
            if (!empty($dish['name']) && empty($dish['price'])) {
                $errors[] = "Jídlo " . ($index + 1) . ": Cena je povinná";
            }
            
            if (isset($dish['price']) && $dish['price'] !== '' && !is_numeric($dish['price'])) {
                // Try to extract number from price string
                $numericPrice = preg_replace('/[^0-9.]/', '', $dish['price']);
                if (!is_numeric($numericPrice) || $numericPrice < 0) {
                    $errors[] = "Jídlo " . ($index + 1) . ": Cena musí být kladné číslo";
                }
            }
        }
    }
    
    return $errors;
}

/**
 * Publish week menu (change status from draft to published)
 */
function publishWeekMenu($restaurantId, $weekStart) {
    $menus = getAllMenus();
    
    if (!isset($menus[$restaurantId][$weekStart])) {
        return ['success' => false, 'error' => 'Menu nenalezeno'];
    }
    
    $menus[$restaurantId][$weekStart]['status'] = 'published';
    $menus[$restaurantId][$weekStart]['publishedAt'] = date('Y-m-d H:i:s');
    
    saveAllMenus($menus);
    
    return ['success' => true];
}

/**
 * Copy menu from one week to another
 */
function copyWeekMenu($restaurantId, $sourceWeek, $targetWeek) {
    $sourceMenu = getWeekMenu($restaurantId, $sourceWeek);
    
    if (empty($sourceMenu['days'])) {
        return ['success' => false, 'error' => 'Zdrojové menu je prázdné'];
    }
    
    // Create new week with copied dishes but updated dates
    $newWeek = createEmptyWeek($targetWeek);
    
    foreach ($sourceMenu['days'] as $dayIndex => $dayData) {
        if (isset($newWeek['days'][$dayIndex])) {
            $newWeek['days'][$dayIndex]['closed'] = $dayData['closed'];
            $newWeek['days'][$dayIndex]['closedNote'] = $dayData['closedNote'] ?? '';
            $newWeek['days'][$dayIndex]['dishes'] = $dayData['dishes'] ?? [];
        }
    }
    
    $newWeek['status'] = 'draft';
    
    saveWeekMenu($restaurantId, $targetWeek, $newWeek);
    
    return ['success' => true];
}

/**
 * Get available weeks for a restaurant
 */
function getAvailableWeeks($restaurantId, $limit = 10) {
    $menus = getAllMenus();
    $weeks = [];
    
    if (isset($menus[$restaurantId])) {
        $weeks = array_keys($menus[$restaurantId]);
        rsort($weeks);
        $weeks = array_slice($weeks, 0, $limit);
    }
    
    return $weeks;
}

