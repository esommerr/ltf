<?php
// /public_html/backend/auth/login.php - UPDATED VERSION WITH USER ID SUPPORT

session_start();
require_once __DIR__ . '/../config/database.php';

$errors = [];
$username = '';
$remember_me = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
    
    if (empty($identifier) || empty($password)) {
        $errors[] = 'Please enter both username and password.';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if identifier is a User ID (starts with USR- or PRO-)
            if (preg_match('/^(USR|PRO)-/', $identifier)) {
                // Try to find by User/Professional ID
                // First check users table
                $stmt = $pdo->prepare("
                    SELECT id, user_id, email, username, password_hash, full_name 
                    FROM users 
                    WHERE user_id = ?
                ");
                $stmt->execute([$identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $user_type = 'user';
                    $user_id_field = 'user_id';
                } else {
                    // Check professionals table
                    $stmt = $pdo->prepare("
                        SELECT id, professional_id, email, username, password_hash, full_name, status 
                        FROM professionals 
                        WHERE professional_id = ?
                    ");
                    $stmt->execute([$identifier]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $user_type = 'professional';
                        $user_id_field = 'professional_id';
                    }
                }
            } else {
                // Try to find by username or email
                $stmt = $pdo->prepare("
                    SELECT id, user_id, email, username, password_hash, full_name 
                    FROM users 
                    WHERE username = ? OR email = ?
                ");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $user_type = 'user';
                    $user_id_field = 'user_id';
                } else {
                    // Check professionals table
                    $stmt = $pdo->prepare("
                        SELECT id, professional_id, email, username, password_hash, full_name, status 
                        FROM professionals 
                        WHERE username = ? OR email = ?
                    ");
                    $stmt->execute([$identifier, $identifier]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $user_type = 'professional';
                        $user_id_field = 'professional_id';
                    }
                }
            }
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if professional is approved
                if ($user_type === 'professional' && $user['status'] !== 'approved') {
                    $errors[] = 'Your professional account is pending approval. Please wait for admin approval.';
                } else {
                    // Login successful
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_uid'] = $user[$user_id_field] ?? null;
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
                    
                    // Set longer session if remember me is checked
                    if ($remember_me) {
                        ini_set('session.gc_maxlifetime', 86400); // 24 hours
                        session_set_cookie_params(86400);
                    }
                    
                    // Redirect based on user type
                    if ($user_type === 'professional') {
                        $redirect_url = '/pro-dashboard/';
                    } else {
                        $redirect_url = '/dashboard/';
                    }
                    
                    // Store for display
                    $success = 'Login successful! Redirecting to dashboard...';
                    $username = $identifier;
                    
                    // Redirect after 2 seconds
                    header('Refresh: 2; url=' . $redirect_url);
                }
            } else {
                $errors[] = 'Invalid username or password.';
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = 'Database error. Please try again later.';
        }
    }
}

// Set content type to HTML with proper charset
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Processing - Learn the Fix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red-primary: #BF0A30;
            --red-hover: #a00828;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --bg-main: #ffffff;
            --bg-soft: #f9fafb;
            --border-soft: #e5e7eb;
            --success-green: #059669;
            --success-bg: #ecfdf5;
            --error-red: #dc2626;
            --error-bg: #fef2f2;
            --info-blue: #3b82f6;
            --info-bg: #eff6ff;
            --warning-orange: #d97706;
            --warning-bg: #fffbeb;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-soft);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 500px;
            background: var(--bg-main);
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 12px 32px rgba(0,0,0,.08);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: var(--red-primary);
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .logo p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
        
        .error {
            background: var(--error-bg);
            color: var(--error-red);
            border: 1px solid #fecaca;
        }
        
        .success {
            background: var(--success-bg);
            color: var(--success-green);
            border: 1px solid #a7f3d0;
        }
        
        .warning {
            background: var(--warning-bg);
            color: var(--warning-orange);
            border: 1px solid #fde68a;
        }
        
        .info {
            background: var(--info-bg);
            color: var(--info-blue);
            border: 1px solid #dbeafe;
        }
        
        .form-data {
            background: var(--bg-soft);
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .form-data h3 {
            font-size: 1rem;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        
        .data-item {
            display: flex;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .data-label {
            font-weight: 600;
            width: 140px;
            color: var(--text-secondary);
        }
        
        .data-value {
            flex: 1;
        }
        
        .user-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .badge-user {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-pro {
            background: #f0f9ff;
            color: #0c4a6e;
        }
        
        .password-masked {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--red-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--bg-soft);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        
        .btn-secondary:hover {
            background: var(--border-soft);
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--bg-soft);
            border-top-color: var(--red-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 24px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .data-item {
                flex-direction: column;
            }
            
            .data-label {
                width: 100%;
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Learn the Fix</h1>
            <p>Login Processing</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <strong>Error:</strong> 
                <?php foreach ($errors as $error): ?>
                    <p style="margin: 4px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="loading">
                <div class="spinner"></div>
                <p>Redirecting to <?php echo isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'professional' ? 'Professional' : 'User'; ?> Dashboard...</p>
            </div>
        <?php endif; ?>
        
        <div class="message info">
            <p><strong>Login System Status:</strong> Enhanced authentication active.</p>
            <p>Now accepting: Username, Email, or User ID (USR-XXXXXX / PRO-XXXXXX)</p>
        </div>
        
        <div class="form-data">
            <h3>Authentication Details:</h3>
            <div class="data-item">
                <div class="data-label">Login Method:</div>
                <div class="data-value">
                    <?php 
                    if (!empty($identifier)) {
                        if (preg_match('/^(USR|PRO)-/', $identifier)) {
                            echo 'User ID';
                        } elseif (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                            echo 'Email';
                        } else {
                            echo 'Username';
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="data-item">
                <div class="data-label">Identifier:</div>
                <div class="data-value"><?php echo htmlspecialchars($username); ?></div>
            </div>
            <div class="data-item">
                <div class="data-label">Password:</div>
                <div class="data-value">
                    <?php if (!empty($_POST['password'])): ?>
                        <span class="password-masked">••••••••</span>
                        <small>(<?php echo strlen($_POST['password']); ?> characters)</small>
                    <?php else: ?>
                        <span class="password-masked">Not provided</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="data-item">
                <div class="data-label">Remember Me:</div>
                <div class="data-value"><?php echo $remember_me ? 'Yes' : 'No'; ?></div>
            </div>
            
            <?php if (isset($_SESSION['user_type'])): ?>
            <div class="data-item">
                <div class="data-label">User Type:</div>
                <div class="data-value">
                    <?php 
                    echo ucfirst($_SESSION['user_type']);
                    echo '<span class="user-type-badge ' . ($_SESSION['user_type'] === 'professional' ? 'badge-pro' : 'badge-user') . '">';
                    echo $_SESSION['user_type'] === 'professional' ? 'PRO' : 'USER';
                    echo '</span>';
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_uid'])): ?>
            <div class="data-item">
                <div class="data-label">User ID:</div>
                <div class="data-value">
                    <strong><?php echo htmlspecialchars($_SESSION['user_uid']); ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="/sign-in/" class="btn btn-secondary">← Back to Sign In</a>
            <a href="/" class="btn btn-secondary">Go to Homepage</a>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <?php if ($_SESSION['user_type'] === 'professional'): ?>
                    <a href="/pro-dashboard/" class="btn btn-primary">Go to Pro Dashboard</a>
                <?php else: ?>
                    <a href="/dashboard/" class="btn btn-primary">Go to Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-redirect on success after 2 seconds
        <?php if (isset($success)): ?>
            setTimeout(function() {
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'professional'): ?>
                    window.location.href = '/pro-dashboard/';
                <?php else: ?>
                    window.location.href = '/dashboard/';
                <?php endif; ?>
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
