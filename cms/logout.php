<?php
/**
 * Logout Handler
 * Restaurant Menu Management System
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logActivity('logout');
logout();
redirect('login.php');

