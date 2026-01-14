/**
 * Daily Menu Module for Bufet v Hlubokáně
 * 
 * Handles:
 * - Day selection UI
 * - Menu data fetching from CMS API
 * - Default to today's date
 * - Weekend/holiday handling
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        // CMS API endpoint for menu data
        menuEndpoint: 'sprava/api.php?restaurant=bufet',
        
        // Fallback to demo data if CMS is unavailable
        useFallbackData: true,
        
        // Days of the week (Czech)
        days: ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek'],
        
        // Animation duration
        animationDuration: 300
    };

    // Fallback menu data (used when CMS is unavailable)
    const FALLBACK_MENU = {
        0: { // Pondělí
            soup: [
                { name: 'Hovězí vývar s nudlemi', price: '25 Kč', glutenFree: true }
            ],
            main: [
                { name: 'Svíčková na smetaně, houskové knedlíky', price: '115 Kč', number: '1' },
                { name: 'Kuřecí řízek smažený, bramborová kaše', price: '105 Kč', number: '2', glutenFree: true },
                { name: 'Špagety bolognese, sýr', price: '95 Kč', number: '3' }
            ]
        },
        1: { // Úterý
            soup: [
                { name: 'Gulášová polévka', price: '25 Kč', glutenFree: true }
            ],
            main: [
                { name: 'Vepřový guláš, houskové knedlíky', price: '105 Kč', number: '1' },
                { name: 'Smažený sýr, hranolky, tatarská omáčka', price: '110 Kč', number: '2', vegetarian: true },
                { name: 'Kuřecí steak na žampionech, rýže', price: '115 Kč', number: '3', glutenFree: true }
            ]
        },
        2: { // Středa
            soup: [
                { name: 'Česneková polévka s krutony', price: '25 Kč', vegetarian: true }
            ],
            main: [
                { name: 'Pečené kuřecí stehno, dušená rýže', price: '100 Kč', number: '1', glutenFree: true },
                { name: 'Sekaná pečeně, bramborová kaše', price: '95 Kč', number: '2' },
                { name: 'Těstoviny s kuřecím masem a smetanou', price: '105 Kč', number: '3' }
            ]
        },
        3: { // Čtvrtek
            soup: [
                { name: 'Bramboračka', price: '25 Kč', glutenFree: true, vegetarian: true }
            ],
            main: [
                { name: 'Vepřová krkovice na grilu, hranolky', price: '115 Kč', number: '1', glutenFree: true },
                { name: 'Rizoto s kuřecím masem a zeleninou', price: '100 Kč', number: '2', glutenFree: true },
                { name: 'Hovězí roláda, houskové knedlíky', price: '120 Kč', number: '3' }
            ]
        },
        4: { // Pátek
            soup: [
                { name: 'Zeleninový krém', price: '25 Kč', glutenFree: true, vegetarian: true }
            ],
            main: [
                { name: 'Smažený vepřový řízek, bramborový salát', price: '110 Kč', number: '1' },
                { name: 'Pečený losos, bylinkové brambory', price: '135 Kč', number: '2', glutenFree: true },
                { name: 'Kuřecí nudličky na kari, rýže', price: '105 Kč', number: '3', glutenFree: true }
            ]
        }
    };

    // State
    let currentDay = null;
    let menuData = null;
    let todayIndex = null;

    /**
     * Get current day index (0 = Monday, 4 = Friday)
     * Returns -1 for weekends
     */
    function getTodayIndex() {
        const now = new Date();
        const dayOfWeek = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
        
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            return -1; // Weekend
        }
        return dayOfWeek - 1;
    }

    /**
     * Mark today's button with a visual indicator
     */
    function markTodayButton() {
        const buttons = document.querySelectorAll('.day-btn');
        
        buttons.forEach(btn => {
            btn.classList.remove('is-today');
            const existingBadge = btn.querySelector('.today-badge');
            if (existingBadge) existingBadge.remove();
        });
        
        if (todayIndex >= 0 && todayIndex < buttons.length) {
            const todayBtn = buttons[todayIndex];
            todayBtn.classList.add('is-today');
            
            const badge = document.createElement('span');
            badge.className = 'today-badge';
            badge.textContent = 'Dnes';
            todayBtn.appendChild(badge);
        }
    }

    /**
     * Fetch menu data from CMS API
     */
    async function fetchMenuFromCMS() {
        try {
            const response = await fetch(CONFIG.menuEndpoint);
            
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.menu && Object.keys(data.menu).length > 0) {
                console.log('✓ Menu loaded from CMS');
                return data.menu;
            }
            
            // CMS returned empty menu, use fallback
            if (CONFIG.useFallbackData) {
                console.warn('CMS menu empty, using fallback data');
                return FALLBACK_MENU;
            }
            
            return {};
        } catch (error) {
            console.warn('Failed to fetch from CMS, using fallback:', error.message);
            
            if (CONFIG.useFallbackData) {
                return FALLBACK_MENU;
            }
            
            return {};
        }
    }

    /**
     * Render menu items
     */
    function renderMenuItems(items, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!items || items.length === 0) {
            container.innerHTML = '<p class="no-items">Žádná jídla</p>';
            return;
        }
        
        items.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'menu-item';
            
            let html = '<div class="menu-item-info">';
            if (item.number) {
                html += `<span class="menu-item-number">${item.number}.</span>`;
            }
            html += `<span class="menu-item-name">${escapeHtml(item.name)}</span>`;
            
            // Add dietary icons
            if (item.glutenFree || item.vegetarian) {
                html += '<span class="menu-item-tags">';
                if (item.glutenFree) {
                    html += '<span class="diet-tag diet-gluten" title="Bez lepku">🌾</span>';
                }
                if (item.vegetarian) {
                    html += '<span class="diet-tag diet-veg" title="Vegetariánské">🥬</span>';
                }
                html += '</span>';
            }
            html += '</div>';
            
            if (item.price) {
                html += `<span class="menu-item-price">${escapeHtml(item.price)}</span>`;
            }
            
            itemEl.innerHTML = html;
            container.appendChild(itemEl);
        });
    }

    /**
     * Show menu for selected day
     */
    function showMenuForDay(dayIndex) {
        const loadingEl = document.getElementById('menu-loading');
        const closedEl = document.getElementById('menu-closed');
        const containerEl = document.getElementById('menu-container');
        const buttons = document.querySelectorAll('.day-btn');
        
        // Update button states
        buttons.forEach((btn, idx) => {
            const isActive = idx === dayIndex;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive.toString());
            btn.setAttribute('tabindex', isActive ? '0' : '-1');
        });
        
        currentDay = dayIndex;
        
        // Hide all sections first
        if (loadingEl) loadingEl.style.display = 'none';
        if (closedEl) closedEl.style.display = 'none';
        if (containerEl) containerEl.style.display = 'none';
        
        // Check if closed or no menu
        const dayData = menuData ? menuData[dayIndex] : null;
        
        if (dayIndex === -1 || !dayData || dayData.closed) {
            if (closedEl) {
                // Update closed message if there's a custom note
                if (dayData?.closedNote) {
                    const noteEl = closedEl.querySelector('p');
                    if (noteEl) noteEl.textContent = dayData.closedNote;
                }
                closedEl.style.display = 'block';
            }
            return;
        }
        
        // Render soup section
        const soupSection = document.querySelector('.menu-section:has(#soup-list), .menu-section:first-child');
        if (soupSection) {
            soupSection.style.display = dayData.soup?.length > 0 ? 'block' : 'none';
        }
        renderMenuItems(dayData.soup || [], 'soup-list');
        
        // Render main dishes
        const mainSection = document.querySelector('.menu-section:has(#main-dishes-list), .menu-section:last-child');
        if (mainSection) {
            mainSection.style.display = dayData.main?.length > 0 ? 'block' : 'none';
        }
        renderMenuItems(dayData.main || [], 'main-dishes-list');
        
        // Show menu container with animation
        if (containerEl) {
            containerEl.style.opacity = '0';
            containerEl.style.display = 'block';
            containerEl.offsetHeight; // Trigger reflow
            containerEl.style.transition = `opacity ${CONFIG.animationDuration}ms ease-in-out`;
            containerEl.style.opacity = '1';
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialize day selector buttons
     */
    function initDaySelector() {
        const buttons = document.querySelectorAll('.day-btn');
        
        buttons.forEach((btn, idx) => {
            btn.addEventListener('click', function() {
                showMenuForDay(idx);
            });
            
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    showMenuForDay(idx);
                }
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIdx = (idx + 1) % buttons.length;
                    buttons[nextIdx].focus();
                }
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIdx = (idx - 1 + buttons.length) % buttons.length;
                    buttons[prevIdx].focus();
                }
            });
            
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-selected', 'false');
            btn.setAttribute('tabindex', idx === 0 ? '0' : '-1');
        });
        
        const container = document.querySelector('.weekday-selector');
        if (container) {
            container.setAttribute('role', 'tablist');
            container.setAttribute('aria-label', 'Výběr dne v týdnu');
        }
    }

    /**
     * Initialize the menu module
     */
    async function initMenu() {
        const loadingEl = document.getElementById('menu-loading');
        
        if (loadingEl) {
            loadingEl.style.display = 'block';
        }
        
        todayIndex = getTodayIndex();
        initDaySelector();
        markTodayButton();
        
        // Fetch menu data from CMS
        menuData = await fetchMenuFromCMS();
        
        // Show today's menu or closed message
        if (todayIndex === -1) {
            showMenuForDay(-1);
        } else {
            showMenuForDay(todayIndex);
        }
    }

    /**
     * Jump to today's menu
     */
    function goToToday() {
        if (todayIndex >= 0) {
            showMenuForDay(todayIndex);
        }
    }

    // Export for global access
    window.initMenu = initMenu;
    window.showMenuForDay = showMenuForDay;
    window.goToToday = goToToday;

})();
