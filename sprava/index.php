<?php
/**
 * CMS Entry Point
 * Redirects to login or dashboard
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If authenticated, go to dashboard; otherwise go to login
if (isAuthenticated()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}

