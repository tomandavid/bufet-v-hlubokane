/**
 * Admin CMS JavaScript
 * Restaurant Menu Management System
 */

(function() {
    'use strict';

    // Track dish indices for each day/category
    const dishIndices = {};

    /**
     * Initialize dish indices from existing dishes
     */
    function initDishIndices() {
        document.querySelectorAll('.dishes-list').forEach(list => {
            const id = list.id; // e.g., "dishes-0-soup"
            const items = list.querySelectorAll('.dish-item');
            dishIndices[id] = items.length;
        });
    }

    /**
     * Toggle day closed state
     */
    window.toggleDayClosed = function(checkbox, dayIndex) {
        const dayCard = document.querySelector(`.day-card[data-day="${dayIndex}"]`);
        const closedSection = dayCard.querySelector('.day-closed-section');
        const menuSection = dayCard.querySelector('.day-menu-section');
        
        if (checkbox.checked) {
            dayCard.classList.add('closed');
            closedSection.style.display = 'block';
            menuSection.style.display = 'none';
        } else {
            dayCard.classList.remove('closed');
            closedSection.style.display = 'none';
            menuSection.style.display = 'block';
        }
    };

    /**
     * Add a new dish to a day/category
     */
    window.addDish = function(dayIndex, category) {
        const listId = `dishes-${dayIndex}-${category}`;
        const list = document.getElementById(listId);
        
        if (!list) return;
        
        // Get current index
        if (!dishIndices[listId]) {
            dishIndices[listId] = 0;
        }
        const index = dishIndices[listId]++;
        
        // Get template
        const template = document.getElementById('dish-template');
        let html = template.innerHTML;
        
        // Replace placeholders
        html = html.replace(/__DAY__/g, dayIndex);
        html = html.replace(/__CAT__/g, category);
        html = html.replace(/__INDEX__/g, index);
        
        // Create element
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newDish = wrapper.firstElementChild;
        
        // Add to list
        list.appendChild(newDish);
        
        // Focus on the name input
        const nameInput = newDish.querySelector('.dish-name-input');
        if (nameInput) {
            nameInput.focus();
        }
    };

    /**
     * Remove a dish
     */
    window.removeDish = function(button) {
        const dishItem = button.closest('.dish-item');
        if (dishItem) {
            // Animate removal
            dishItem.style.opacity = '0';
            dishItem.style.transform = 'translateX(10px)';
            dishItem.style.transition = 'all 0.2s ease';
            
            setTimeout(() => {
                dishItem.remove();
            }, 200);
        }
    };

    /**
     * Copy menu from previous week
     */
    window.copyFromPreviousWeek = function() {
        if (!confirm('Opravdu chcete zkopírovat menu z předchozího týdne? Tím přepíšete aktuální neuložené změny.')) {
            return;
        }
        
        const url = `copy-menu.php?restaurant=${SELECTED_RESTAURANT}&source_week=${PREV_WEEK}&target_week=${WEEK_START}`;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Chyba: ' + (data.error || 'Nepodařilo se zkopírovat menu'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Nepodařilo se zkopírovat menu. Zkuste to znovu.');
        });
    };

    /**
     * Form validation before submit
     */
    function validateForm(form) {
        const errors = [];
        
        // Check each day's dishes
        form.querySelectorAll('.day-card').forEach(dayCard => {
            const dayName = dayCard.querySelector('.day-title h4').textContent;
            const isClosed = dayCard.querySelector('input[type="checkbox"]').checked;
            
            if (!isClosed) {
                dayCard.querySelectorAll('.dish-item').forEach((dish, index) => {
                    const nameInput = dish.querySelector('.dish-name-input');
                    const priceInput = dish.querySelector('.dish-price-input');
                    
                    const name = nameInput?.value.trim();
                    const price = priceInput?.value;
                    
                    // If name is filled, price should be too
                    if (name && !price) {
                        errors.push(`${dayName}: Jídlo "${name}" nemá cenu`);
                        priceInput?.classList.add('error');
                    }
                    
                    // Price validation
                    if (price && (isNaN(price) || parseFloat(price) < 0)) {
                        errors.push(`${dayName}: Neplatná cena pro "${name || 'jídlo'}"`);
                        priceInput?.classList.add('error');
                    }
                });
            }
        });
        
        return errors;
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        const form = e.target;
        const errors = validateForm(form);
        
        if (errors.length > 0) {
            e.preventDefault();
            alert('Prosím opravte následující chyby:\n\n' + errors.join('\n'));
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]:focus, button[type="submit"]:hover') || 
                         form.querySelector('button[name="action"][value="publish"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Ukládám...';
        }
        
        return true;
    }

    /**
     * Clear error state on input
     */
    function clearInputError(e) {
        e.target.classList.remove('error');
    }

    /**
     * Auto-save draft (optional feature)
     */
    let autoSaveTimeout;
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Could implement auto-save here
            console.log('Auto-save would trigger here');
        }, 30000); // 30 seconds
    }

    /**
     * Keyboard shortcuts
     */
    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const form = document.getElementById('menu-form');
            const saveBtn = form?.querySelector('button[value="save"]');
            if (saveBtn) {
                saveBtn.click();
            }
        }
        
        // Ctrl/Cmd + P to publish
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            const form = document.getElementById('menu-form');
            const publishBtn = form?.querySelector('button[value="publish"]');
            if (publishBtn && confirm('Opravdu chcete publikovat menu?')) {
                publishBtn.click();
            }
        }
    }

    /**
     * Mobile menu toggle
     */
    function setupMobileNav() {
        // Create mobile menu button if needed
        if (window.innerWidth <= 768) {
            const existingBtn = document.querySelector('.mobile-menu-btn');
            if (!existingBtn) {
                const btn = document.createElement('button');
                btn.className = 'mobile-menu-btn';
                btn.innerHTML = '☰';
                btn.style.cssText = `
                    position: fixed;
                    top: 1rem;
                    left: 1rem;
                    z-index: 200;
                    background: var(--color-primary);
                    color: white;
                    border: none;
                    width: 40px;
                    height: 40px;
                    border-radius: 8px;
                    font-size: 1.25rem;
                    cursor: pointer;
                `;
                
                btn.addEventListener('click', () => {
                    document.querySelector('.sidebar')?.classList.toggle('open');
                });
                
                document.body.appendChild(btn);
            }
        }
    }

    /**
     * Initialize
     */
    function init() {
        initDishIndices();
        
        // Form submission
        const form = document.getElementById('menu-form');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
        
        // Input error clearing
        document.querySelectorAll('.dish-price-input, .dish-name-input').forEach(input => {
            input.addEventListener('input', clearInputError);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
        
        // Mobile nav
        setupMobileNav();
        window.addEventListener('resize', setupMobileNav);
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const menuBtn = document.querySelector('.mobile-menu-btn');
                
                if (sidebar?.classList.contains('open') && 
                    !sidebar.contains(e.target) && 
                    !menuBtn?.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        console.log('🍽️ Menu CMS Admin initialized');
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

