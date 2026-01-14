<?php
/**
 * Dashboard
 * Restaurant Menu Management System
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$user = getCurrentUser();
$flash = getFlashMessage();

// Get selected restaurant (default to first)
$selectedRestaurant = sanitize($_GET['restaurant'] ?? 'bufet');
if (!isset(RESTAURANTS[$selectedRestaurant])) {
    $selectedRestaurant = 'bufet';
}

// Get selected week (default to current)
$weekParam = sanitize($_GET['week'] ?? '');
if ($weekParam) {
    $weekStart = getWeekStartDate($weekParam);
} else {
    $weekStart = getWeekStartDate(date('Y-m-d'));
}

$prevWeek = getPreviousWeekStart($weekStart);
$nextWeek = getNextWeekStart($weekStart);

// Get menu data
$weekMenu = getWeekMenu($selectedRestaurant, $weekStart);
$restaurants = RESTAURANTS;
$categories = DISH_CATEGORIES;
$daysOfWeek = DAYS_OF_WEEK;

// Check if current week
$isCurrentWeek = isCurrentWeek($weekStart);
$isFutureWeek = isFutureWeek($weekStart);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">🍽️</div>
                <h1><?= SITE_NAME ?></h1>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title">Restaurace</span>
                    <?php foreach ($restaurants as $id => $restaurant): ?>
                        <a href="?restaurant=<?= $id ?>&week=<?= $weekStart ?>" 
                           class="nav-item <?= $selectedRestaurant === $id ? 'active' : '' ?>"
                           style="--accent-color: <?= $restaurant['color'] ?>">
                            <span class="nav-dot"></span>
                            <?= htmlspecialchars($restaurant['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="nav-section">
                    <span class="nav-section-title">Rychlé akce</span>
                    <a href="?restaurant=<?= $selectedRestaurant ?>&week=<?= getWeekStartDate(date('Y-m-d')) ?>" class="nav-item">
                        <span class="nav-icon">📅</span>
                        Aktuální týden
                    </a>
                    <a href="?restaurant=<?= $selectedRestaurant ?>&week=<?= getNextWeekStart(getWeekStartDate(date('Y-m-d'))) ?>" class="nav-item">
                        <span class="nav-icon">➡️</span>
                        Příští týden
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Odhlásit</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h2><?= htmlspecialchars($restaurants[$selectedRestaurant]['name']) ?></h2>
                    <span class="week-badge <?= $isCurrentWeek ? 'current' : ($isFutureWeek ? 'future' : 'past') ?>">
                        <?= $isCurrentWeek ? 'Aktuální týden' : ($isFutureWeek ? 'Budoucí' : 'Minulý') ?>
                    </span>
                </div>
                <div class="header-right">
                    <span class="menu-status status-<?= $weekMenu['status'] ?? 'draft' ?>">
                        <?= ($weekMenu['status'] ?? 'draft') === 'published' ? '✓ Publikováno' : '○ Koncept' ?>
                    </span>
                </div>
            </header>
            
            <!-- Week Navigation -->
            <div class="week-navigation">
                <a href="?restaurant=<?= $selectedRestaurant ?>&week=<?= $prevWeek ?>" class="week-nav-btn">
                    ← Předchozí týden
                </a>
                <div class="week-display">
                    <h3><?= formatWeekRange($weekStart) ?></h3>
                </div>
                <a href="?restaurant=<?= $selectedRestaurant ?>&week=<?= $nextWeek ?>" class="week-nav-btn">
                    Další týden →
                </a>
            </div>
            
            <!-- Week Calendar Grid -->
            <form id="menu-form" method="POST" action="save-menu.php">
                <?= csrfField() ?>
                <input type="hidden" name="restaurant" value="<?= $selectedRestaurant ?>">
                <input type="hidden" name="week_start" value="<?= $weekStart ?>">
                
                <div class="week-grid">
                    <?php for ($dayIndex = 0; $dayIndex < 7; $dayIndex++): 
                        $dayData = $weekMenu['days'][$dayIndex] ?? [];
                        $isClosed = $dayData['closed'] ?? in_array($dayIndex, DEFAULT_CLOSED_DAYS);
                        $isWeekend = $dayIndex >= 5;
                        $dayDate = new DateTime($weekStart);
                        $dayDate->modify("+$dayIndex days");
                        $isToday = $dayDate->format('Y-m-d') === date('Y-m-d');
                    ?>
                        <div class="day-card <?= $isWeekend ? 'weekend' : '' ?> <?= $isClosed ? 'closed' : '' ?> <?= $isToday ? 'today' : '' ?>" data-day="<?= $dayIndex ?>">
                            <div class="day-header">
                                <div class="day-title">
                                    <h4><?= $daysOfWeek[$dayIndex] ?></h4>
                                    <span class="day-date"><?= $dayDate->format('j. n.') ?></span>
                                    <?php if ($isToday): ?>
                                        <span class="today-badge">Dnes</span>
                                    <?php endif; ?>
                                </div>
                                <label class="closed-toggle">
                                    <input type="checkbox" 
                                           name="days[<?= $dayIndex ?>][closed]" 
                                           value="1" 
                                           <?= $isClosed ? 'checked' : '' ?>
                                           onchange="toggleDayClosed(this, <?= $dayIndex ?>)">
                                    <span class="toggle-label">Zavřeno</span>
                                </label>
                            </div>
                            
                            <div class="day-closed-section" style="<?= $isClosed ? '' : 'display: none;' ?>">
                                <input type="text" 
                                       name="days[<?= $dayIndex ?>][closedNote]" 
                                       value="<?= htmlspecialchars($dayData['closedNote'] ?? 'Zavřeno') ?>"
                                       placeholder="Poznámka (např. Státní svátek)"
                                       class="closed-note-input">
                            </div>
                            
                            <div class="day-menu-section" style="<?= $isClosed ? 'display: none;' : '' ?>">
                                <?php foreach ($categories as $catKey => $catName): ?>
                                    <div class="category-section" data-category="<?= $catKey ?>">
                                        <div class="category-header">
                                            <h5><?= $catName ?></h5>
                                            <button type="button" class="add-dish-btn" onclick="addDish(<?= $dayIndex ?>, '<?= $catKey ?>')">
                                                + Přidat
                                            </button>
                                        </div>
                                        <div class="dishes-list" id="dishes-<?= $dayIndex ?>-<?= $catKey ?>">
                                            <?php 
                                            $dishes = array_filter($dayData['dishes'] ?? [], function($d) use ($catKey) {
                                                return ($d['category'] ?? 'main') === $catKey;
                                            });
                                            $dishIndex = 0;
                                            foreach ($dishes as $dish): 
                                            ?>
                                                <div class="dish-item" data-dish-index="<?= $dishIndex ?>">
                                                    <input type="hidden" name="days[<?= $dayIndex ?>][dishes][<?= $catKey ?>][<?= $dishIndex ?>][category]" value="<?= $catKey ?>">
                                                    <input type="text" 
                                                           name="days[<?= $dayIndex ?>][dishes][<?= $catKey ?>][<?= $dishIndex ?>][name]" 
                                                           value="<?= htmlspecialchars($dish['name'] ?? '') ?>"
                                                           placeholder="Název jídla"
                                                           class="dish-name-input">
                                                    <input type="number" 
                                                           name="days[<?= $dayIndex ?>][dishes][<?= $catKey ?>][<?= $dishIndex ?>][price]" 
                                                           value="<?= htmlspecialchars($dish['price'] ?? '') ?>"
                                                           placeholder="Kč"
                                                           min="0"
                                                           step="1"
                                                           class="dish-price-input">
                                                    <label class="dish-tag-checkbox" title="Bez lepku">
                                                        <input type="checkbox" 
                                                               name="days[<?= $dayIndex ?>][dishes][<?= $catKey ?>][<?= $dishIndex ?>][glutenFree]" 
                                                               value="1"
                                                               <?= !empty($dish['glutenFree']) ? 'checked' : '' ?>>
                                                        <span class="tag-icon tag-gluten">🌾</span>
                                                    </label>
                                                    <label class="dish-tag-checkbox" title="Vegetariánské">
                                                        <input type="checkbox" 
                                                               name="days[<?= $dayIndex ?>][dishes][<?= $catKey ?>][<?= $dishIndex ?>][vegetarian]" 
                                                               value="1"
                                                               <?= !empty($dish['vegetarian']) ? 'checked' : '' ?>>
                                                        <span class="tag-icon tag-veg">🥬</span>
                                                    </label>
                                                    <button type="button" class="remove-dish-btn" onclick="removeDish(this)" title="Odebrat jídlo">×</button>
                                                </div>
                                            <?php 
                                            $dishIndex++;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Action Bar -->
                <div class="action-bar">
                    <div class="action-bar-left">
                        <?php if (isset($weekMenu['lastModified'])): ?>
                            <span class="last-modified">
                                Naposledy upraveno: <?= timeAgo($weekMenu['lastModified']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="action-bar-right">
                        <button type="button" class="btn btn-secondary" onclick="copyFromPreviousWeek()">
                            📋 Kopírovat z minulého týdne
                        </button>
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            💾 Uložit koncept
                        </button>
                        <button type="submit" name="action" value="publish" class="btn btn-success">
                            ✓ Publikovat
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    
    <!-- Dish Template (hidden) -->
    <template id="dish-template">
        <div class="dish-item" data-dish-index="__INDEX__">
            <input type="hidden" name="days[__DAY__][dishes][__CAT__][__INDEX__][category]" value="__CAT__">
            <input type="text" 
                   name="days[__DAY__][dishes][__CAT__][__INDEX__][name]" 
                   placeholder="Název jídla"
                   class="dish-name-input">
            <input type="number" 
                   name="days[__DAY__][dishes][__CAT__][__INDEX__][price]" 
                   placeholder="Kč"
                   min="0"
                   step="1"
                   class="dish-price-input">
            <label class="dish-tag-checkbox" title="Bez lepku">
                <input type="checkbox" name="days[__DAY__][dishes][__CAT__][__INDEX__][glutenFree]" value="1">
                <span class="tag-icon tag-gluten">🌾</span>
            </label>
            <label class="dish-tag-checkbox" title="Vegetariánské">
                <input type="checkbox" name="days[__DAY__][dishes][__CAT__][__INDEX__][vegetarian]" value="1">
                <span class="tag-icon tag-veg">🥬</span>
            </label>
            <button type="button" class="remove-dish-btn" onclick="removeDish(this)" title="Odebrat jídlo">×</button>
        </div>
    </template>
    
    <script src="js/admin.js"></script>
    <script>
        // Pass data to JavaScript
        const RESTAURANTS = <?= json_encode($restaurants) ?>;
        const CATEGORIES = <?= json_encode($categories) ?>;
        const SELECTED_RESTAURANT = <?= json_encode($selectedRestaurant) ?>;
        const WEEK_START = <?= json_encode($weekStart) ?>;
        const PREV_WEEK = <?= json_encode($prevWeek) ?>;
    </script>
</body>
</html>

