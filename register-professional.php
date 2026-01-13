<?php
// /public_html/backend/auth/register-professional.php

session_start();
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

// Function to generate unique Professional ID
function generateProfessionalID($pdo) {
    // Try to generate a unique ID
    $maxAttempts = 10;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // Generate random 6-digit number
        $randomNum = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $pro_id = 'PRO-' . $randomNum;
        
        // Check if ID already exists (in professionals table)
        $stmt = $pdo->prepare("SELECT id FROM professionals WHERE professional_id = ?");
        $stmt->execute([$pro_id]);
        
        if (!$stmt->fetch()) {
            return $pro_id;
        }
        
        $attempt++;
    }
    
    // Fallback: use timestamp + random
    return 'PRO-' . time() . '-' . mt_rand(100, 999);
}

// Function to send confirmation email
function sendConfirmationEmail($email, $full_name, $professional_id) {
    // Email configuration (update these with your actual SMTP settings)
    $to = $email;
    $subject = "Welcome to Learn the Fix - Professional Registration Confirmation";
    
    // Email headers
    $headers = "From: Learn the Fix <office@learnthefix.com>\r\n";
    $headers .= "Reply-To: office@learnthefix.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email body with HTML formatting
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #BF0A30; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .highlight-box { background: #fff; border: 2px solid #BF0A30; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .credentials { background: #f0f9ff; padding: 15px; border-left: 4px solid #0369a1; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Learn the Fix</h1>
                <h2>Professional Registration Confirmation</h2>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($full_name) . ',</p>
                
                <p>Thank you for registering as a professional with <strong>Learn the Fix</strong>! We\'re excited to have you join our community of skilled professionals.</p>
                
                <div class="highlight-box">
                    <h3>Your Application Status</h3>
                    <p>Your application has been received and is currently <strong>pending review</strong> by our team.</p>
                </div>
                
                <div class="credentials">
                    <h3>Your Professional ID</h3>
                    <p style="font-size: 18px; font-weight: bold; color: #0369a1;">' . htmlspecialchars($professional_id) . '</p>
                    <p><em>Please save this ID - you\'ll need it to access your professional dashboard.</em></p>
                </div>
                
                <h3>What Happens Next?</h3>
                <ol>
                    <li><strong>Application Review:</strong> Our team will review your application within <strong>24-48 hours</strong></li>
                    <li><strong>Approval Notification:</strong> You\'ll receive another email once your application is approved</li>
                    <li><strong>Dashboard Access:</strong> Use your Professional ID to log in to your dashboard</li>
                    <li><strong>Complete Your Profile:</strong> Add more details about your services and availability</li>
                </ol>
                
                <h3>Need Help?</h3>
                <p>If you have any questions or need assistance, please contact our support team:</p>
                <ul>
                    <li>Email: support@learnthefix.com</li>
                    <li>Phone: (123) 456-7890</li>
                    <li>Hours: Mon-Fri, 9AM-5PM EST</li>
                </ul>
                
                <p>We look forward to working with you!</p>
                
                <p>Best regards,<br>
                <strong>The Learn the Fix Team</strong></p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Learn the Fix. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

// Function to send admin notification email
function sendAdminNotification($professional_id, $full_name, $email) {
    $admin_email = "office@learnthefix.com"; // Change to your admin email
    $subject = "New Professional Registration - " . $professional_id;
    
    $headers = "From: Learn the Fix <office@learnthefix.com>\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0369a1; color: white; padding: 15px; text-align: center; }
            .content { background: #f4f4f4; padding: 20px; }
            .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #BF0A30; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New Professional Registration</h2>
            </div>
            <div class="content">
                <p>A new professional has registered on Learn the Fix.</p>
                
                <div class="info-box">
                    <h3>Registration Details:</h3>
                    <p><strong>Professional ID:</strong> ' . htmlspecialchars($professional_id) . '</p>
                    <p><strong>Name:</strong> ' . htmlspecialchars($full_name) . '</p>
                    <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
                    <p><strong>Registration Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
                    <p><strong>Status:</strong> Pending Review</p>
                </div>
                
                <p>Please review this application in the admin panel:</p>
                <p><a href="https://yourdomain.com/admin/professionals/review/' . urlencode($professional_id) . '">Review Application</a></p>
            </div>
        </div>
    </body>
    </html>';
    
    return mail($admin_email, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = trim($_POST['first-name'] ?? '');
    $last_name = trim($_POST['last-name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $hourly_fee = floatval($_POST['hourly-fee'] ?? 0);
    $professional_id_from_form = trim($_POST['professional_id'] ?? '');
    
    // Skills data
    $skills = $_POST['skills'] ?? [];
    $experience = $_POST['experience'] ?? [];
    
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
    
    if ($hourly_fee <= 0) {
        $errors[] = 'Please enter a valid hourly fee.';
    }
    
    // Validate skills
    $skill_data = [];
    if (!empty($skills) && is_array($skills)) {
        foreach ($skills as $index => $skill) {
            $skill_name = trim($skill);
            $exp = isset($experience[$index]) ? intval($experience[$index]) : 0;
            
            if (!empty($skill_name)) {
                $skill_data[] = [
                    'skill' => $skill_name,
                    'experience' => $exp
                ];
            }
        }
    }
    
    if (empty($skill_data)) {
        $errors[] = 'Please add at least one skill.';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM professionals WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            }
            
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM professionals WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already taken.';
            }
            
            // Generate or validate Professional ID
            if (!empty($professional_id_from_form) && preg_match('/^PRO-\d{6}$/', $professional_id_from_form)) {
                // Check if provided Professional ID already exists
                $stmt = $pdo->prepare("SELECT id FROM professionals WHERE professional_id = ?");
                $stmt->execute([$professional_id_from_form]);
                if ($stmt->fetch()) {
                    $professional_id = generateProfessionalID($pdo); // Generate new one if duplicate
                } else {
                    $professional_id = $professional_id_from_form;
                }
            } else {
                $professional_id = generateProfessionalID($pdo);
            }
            
            if (empty($errors)) {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Handle file uploads
                $certificates_path = null;
                $photo_path = null;
                
                // Process certificates upload
                if (isset($_FILES['certificates']) && $_FILES['certificates']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../uploads/certificates/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $certificates_name = $professional_id . '_certificates_' . time() . '.pdf';
                    $certificates_path = '/uploads/certificates/' . $certificates_name;
                    move_uploaded_file($_FILES['certificates']['tmp_name'], $upload_dir . $certificates_name);
                }
                
                // Process photo upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../uploads/professional_photos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $photo_name = $professional_id . '_photo_' . time() . '.jpg';
                    $photo_path = '/uploads/professional_photos/' . $photo_name;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name);
                }
                
                // Insert professional with ALL data
                $stmt = $pdo->prepare("
                    INSERT INTO professionals (
                        professional_id, email, username, password_hash, 
                        full_name, phone, zipcode, hourly_rate, 
                        certificates_path, photo_path, status, created_at
                    ) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");

                $stmt->execute([
                    $professional_id,
                    $email,
                    $username,
                    $password_hash,
                    $full_name,
                    $phone,
                    $zipcode,
                    $hourly_fee,
                    $certificates_path,
                    $photo_path
                ]);
                
                $db_pro_id = $pdo->lastInsertId();
                
                // Insert skills
                foreach ($skill_data as $skill) {
                    $stmt = $pdo->prepare("
                        INSERT INTO professional_skills (professional_id, skill_name, years_experience)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$db_pro_id, $skill['skill'], $skill['experience']]);
                }
                
                // Send confirmation email to professional
                $email_sent = sendConfirmationEmail($email, $full_name, $professional_id);
                
                // Send admin notification email
                $admin_notification_sent = sendAdminNotification($professional_id, $full_name, $email);
                
                // Auto-login
                $_SESSION['professional_id'] = $db_pro_id;
                $_SESSION['professional_uid'] = $professional_id;
                $_SESSION['professional_email'] = $email;
                $_SESSION['professional_name'] = $full_name ?: $username;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'professional';
                
                // Store Professional ID in session for display
                $_SESSION['generated_professional_id'] = $professional_id;
                
                $success = 'Professional registration submitted successfully! Your application is pending review.';
                
                // Add email status to success message
                if ($email_sent) {
                    $success .= ' A confirmation email has been sent to ' . htmlspecialchars($email) . '.';
                } else {
                    $success .= ' <strong>Note:</strong> Confirmation email could not be sent. Please save your Professional ID.';
                }
                
                header('Refresh: 10; url=/pro-dashboard/');
            }
            
        } catch (PDOException $e) {
            error_log("Professional registration error: " . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Professional Registration Processing | Learn the Fix</title>
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
        .warning { 
            background: #fffbeb; 
            padding: 24px; 
            border-radius: 12px;
            border: 1px solid #fde68a;
            margin: 20px 0;
        }
        .error { 
            background: #fef2f2; 
            padding: 24px; 
            border-radius: 12px; 
            border: 1px solid #fecaca;
            margin: 20px 0;
        }
        .pro-id-box {
            background: #f0f9ff;
            border: 1px solid #7dd3fc;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .email-note {
            background: #f0f9ff;
            border: 1px solid #0369a1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9rem;
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
        .btn-secondary {
            background: #64748b;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        h2 { color: #0f172a; margin-bottom: 24px; }
        h3 { color: #1e293b; margin-top: 0; }
        h4 { color: #334155; margin-top: 0; }
        ul { margin: 12px 0; padding-left: 20px; }
        li { margin: 8px 0; }
        .checkmark {
            color: #10b981;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Professional Registration Processing</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <h3>Please fix these errors:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="/pro-registration/" class="btn">← Go back to fix</a></p>
            </div>
        <?php elseif ($success): ?>
            <div class="success">
                <h3>✅ Registration Successful!</h3>
                <p><?php echo $success; ?></p>
                
                <?php if (isset($_SESSION['generated_professional_id'])): ?>
                <div class="pro-id-box">
                    <h4>Your Professional ID</h4>
                    <p style="font-size: 1.2rem; font-weight: 700; color: #0369a1; margin: 8px 0;">
                        <?php echo htmlspecialchars($_SESSION['generated_professional_id']); ?>
                    </p>
                    <p style="color: #475569; font-size: 0.9rem; margin: 8px 0;">
                        <strong>Important:</strong> Save this ID for future reference. You'll need it to access your professional dashboard.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="email-note">
                    <h4 style="color: #0369a1; margin-top: 0;">📧 Email Confirmation</h4>
                    <p>A confirmation email has been sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>
                    <p style="font-size: 0.85rem; color: #475569; margin: 8px 0;">
                        If you don't see the email, please check your spam folder. The email contains:
                    </p>
                    <ul style="font-size: 0.85rem; color: #475569;">
                        <li>Your Professional ID</li>
                        <li>Application status</li>
                        <li>Next steps</li>
                        <li>Support contact information</li>
                    </ul>
                </div>
                
                <div style="margin: 20px 0; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #475569;">📋 Next Steps:</h4>
                    <ol style="margin: 8px 0; padding-left: 20px;">
                        <li><span class="checkmark">✓</span> Check your email for confirmation</li>
                        <li><span class="checkmark">✓</span> Save your Professional ID above</li>
                        <li>Wait for application review (24-48 hours)</li>
                        <li>Receive approval notification</li>
                        <li>Complete your profile in the dashboard</li>
                    </ol>
                </div>
                
                <p style="color: #64748b; font-size: 0.9rem;">
                    You will be redirected to your dashboard in 10 seconds...
                </p>
                
                <div style="margin-top: 24px;">
                    <a href="/pro-dashboard/" class="btn">Go to Dashboard Now</a>
                    <a href="/pro-login/" class="btn btn-secondary">Go to Login</a>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/pro-dashboard/';
                }, 10000);
            </script>
        <?php else: ?>
            <div class="warning">
                <p>No form data received.</p>
                <p><a href="/pro-registration/" class="btn">← Go to Professional Registration</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
