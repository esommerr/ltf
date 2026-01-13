<?php
// /public_html/backend/auth/register.php

session_start();
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

// Function to generate unique User ID
function generateUserID($pdo) {
    // Try to generate a unique ID
    $maxAttempts = 10;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // Generate random 6-digit number
        $randomNum = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $user_id = 'USR-' . $randomNum;
        
        // Check if ID already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        if (!$stmt->fetch()) {
            return $user_id;
        }
        
        $attempt++;
    }
    
    // Fallback: use timestamp + random
    return 'USR-' . time() . '-' . mt_rand(100, 999);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $user_id_from_form = trim($_POST['user_id'] ?? '');
    
    // Combine first and last name
    $full_name = trim($first_name . ' ' . $last_name);
    
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $errors[] = 'First and last name are required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            }
            
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already taken.';
            }
            
            // Generate or validate User ID
            if (!empty($user_id_from_form) && preg_match('/^USR-\d{6}$/', $user_id_from_form)) {
                // Check if provided User ID already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ?");
                $stmt->execute([$user_id_from_form]);
                if ($stmt->fetch()) {
                    $user_id = generateUserID($pdo); // Generate new one if duplicate
                } else {
                    $user_id = $user_id_from_form;
                }
            } else {
                $user_id = generateUserID($pdo);
            }
            
            if (empty($errors)) {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with User ID
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_id, email, username, password_hash, full_name, phone, zipcode, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $user_id,
                    $email,
                    $username,
                    $password_hash,
                    $full_name,
                    $phone,
                    $zipcode
                ]);
                
                $db_user_id = $pdo->lastInsertId();
                
                // Auto-login
                $_SESSION['user_id'] = $db_user_id;
                $_SESSION['user_uid'] = $user_id; // Store the unique User ID too
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $full_name ?: $username;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'user'; // User type
                
                // Store User ID in session for display
                $_SESSION['generated_user_id'] = $user_id;
                                
                
                // ============================================
                // SEND CONFIRMATION EMAIL
                // ============================================
                $to = $email;
                $subject = "Welcome to Learn the Fix! Account Confirmation";
                $full_name = $first_name . ' ' . $last_name;
                
                // Email body
                $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #BF0A30; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
                        .user-id { background: #e0f2fe; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 1.1em; }
                        .button { display: inline-block; background: #BF0A30; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
                        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Welcome to Learn the Fix!</h1>
                        </div>
                        <div class='content'>
                            <h2>Hello {$first_name},</h2>
                            <p>Thank you for creating an account with Learn the Fix. Your registration is complete and your account is now active.</p>
                            
                            <h3>Your Account Details:</h3>
                            <p><strong>Name:</strong> {$full_name}</p>
                            <p><strong>Email:</strong> {$email}</p>
                            <p><strong>Username:</strong> {$username}</p>
                            <p><strong>User ID:</strong> <span class='user-id'>{$user_id}</span></p>
                            
                            <p><strong>Important:</strong> Save your User ID for future reference. You can use it along with your username to sign in.</p>
                            
                            <h3>Get Started:</h3>
                            <p>You can now:</p>
                            <ul>
                                <li>Search for professionals</li>
                                <li>Book services</li>
                                <li>Learn new skills</li>
                                <li>Update your profile</li>
                            </ul>
                            
                            <a href='https://learnthefix.com/sign-in/' class='button'>Sign In to Your Account</a>
                            
                            <div class='footer'>
                                <p>If you have any questions, contact us at <a href='mailto:office@learnthefix.com'>office@learnthefix.com</a></p>
                                <p>© " . date('Y') . " Learn the Fix. All rights reserved.</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Email headers
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Learn the Fix <noreply@learnthefix.com>" . "\r\n";
                $headers .= "Reply-To: office@learnthefix.com" . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                // Send email
                $mail_sent = mail($to, $subject, $message, $headers);
                
                if ($mail_sent) {
                    $success = 'Registration successful! A confirmation email has been sent. You are now logged in.';
                } else {
                    $success = 'Registration successful! You are now logged in. (Email notification failed)';
                }
                header('Refresh: 3; url=/dashboard/');
                
                
            }
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

// Simple HTML response with User ID display
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Processing | Learn the Fix</title>
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            padding: 40px; 
            background: #f8fafc;
            color: #0f172a;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        .success { 
            background: #ecfdf5; 
            padding: 24px; 
            border-radius: 12px;
            border: 1px solid #a7f3d0;
            margin: 20px 0;
        }
        .error { 
            background: #fef2f2; 
            padding: 24px; 
            border-radius: 12px; 
            border: 1px solid #fecaca;
            margin: 20px 0;
        }
        .user-id-box {
            background: #f0f9ff;
            border: 1px solid #7dd3fc;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .btn { 
            background: #BF0A30; 
            color: white; 
            padding: 12px 24px; 
            border-radius: 8px; 
            text-decoration: none; 
            display: inline-block;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: inherit;
            margin-top: 16px;
        }
        .btn:hover {
            background: #000000;
        }
        h2 { color: #0f172a; margin-bottom: 24px; }
        h3 { color: #1e293b; margin-top: 0; }
        ul { margin: 12px 0; padding-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registration Processing</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <h3>Please fix these errors:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="/user-registration/" class="btn">← Go back to fix</a></p>
            </div>
        <?php elseif ($success): ?>
            <div class="success">
                <h3>✅ Registration Successful!</h3>
                <p><?php echo htmlspecialchars($success); ?></p>
                
                <?php if (isset($_SESSION['generated_user_id'])): ?>
                <div class="user-id-box">
                    <h4 style="margin-top: 0; color: #0c4a6e;">Your User ID</h4>
                    <p style="font-size: 1.2rem; font-weight: 700; color: #0369a1; margin: 8px 0;">
                        <?php echo htmlspecialchars($_SESSION['generated_user_id']); ?>
                    </p>
                    <p style="color: #475569; font-size: 0.9rem; margin: 8px 0;">
                        Save this ID for future reference. You can use it to sign in.
                    </p>
                </div>
                <?php endif; ?>
                
                <p>Redirecting to dashboard in 3 seconds...</p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/dashboard/';
                }, 3000);
            </script>
        <?php else: ?>
            <p>No form data received.</p>
            <p><a href="/user-registration/" class="btn">← Go to Registration</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
