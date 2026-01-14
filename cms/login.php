<?php
/**
 * Login Page
 * Restaurant Menu Management System
 */

define('CMS_LOADED', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    redirect('dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Verify CSRF token
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.';
    } else if (empty($username) || empty($password)) {
        $error = 'Vyplňte prosím uživatelské jméno a heslo.';
    } else {
        $result = login($username, $password);
        
        if ($result['success']) {
            logActivity('login', ['username' => $username]);
            redirect('dashboard.php');
        } else {
            $error = $result['error'];
            logActivity('login_failed', ['username' => $username]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení | <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .form-group input::placeholder {
            color: #9ca3af;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(102, 126, 234, 0.5);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 1px solid #fecaca;
        }
        
        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <a href="../index.html" class="back-link">
                ← Zpět na web
            </a>
            
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">🍽️</div>
                    <h1><?= SITE_NAME ?></h1>
                    <p>Správa denního menu restaurací</p>
                </div>
                
                <form class="login-form" method="POST" action="">
                    <?= csrfField() ?>
                    
                    <?php if ($error): ?>
                        <div class="error-message">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Uživatelské jméno</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Zadejte uživatelské jméno"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Heslo</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Zadejte heslo"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="login-btn">
                        Přihlásit se
                    </button>
                </form>
                
                <div class="login-footer">
                    <!-- <small>Výchozí přihlášení: <strong>admin</strong> / <strong>admin123</strong></small> -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>

