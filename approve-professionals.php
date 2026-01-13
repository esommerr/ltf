<?php
// /public_html/admin/approve-professionals.php
session_start();
require_once '../backend/config/database.php';

// SIMPLE ADMIN CHECK - UPDATE THIS FOR PRODUCTION!
// For now, use a simple password. In production, use proper admin authentication.
$admin_password = 'admin123'; // CHANGE THIS IN PRODUCTION!

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // Show login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $login_error = 'Invalid password';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login</title>
            <style>
                body { font-family: Arial; padding: 40px; background: #f8fafc; }
                .login-box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }
                .btn { background: #BF0A30; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
                .error { color: #dc2626; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>Admin Login</h2>
                <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter admin password" required>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Admin is logged in, show approval page
$pdo = getDBConnection();

// Approve action
if (isset($_GET['approve']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE professionals SET status = 'approved' WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Professional approved! They can now log in.";
}

// Reject action
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE professionals SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Professional rejected.";
}

// Get pending professionals
$stmt = $pdo->prepare("SELECT * FROM professionals WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved professionals
$stmt = $pdo->prepare("SELECT * FROM professionals WHERE status = 'approved' ORDER BY updated_at DESC LIMIT 10");
$stmt->execute();
$approved_pros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approve Professionals | Admin</title>
    <style>
        :root {
            --red-primary: #BF0A30;
            --red-hover: #000000;
            --green: #059669;
            --gray: #6b7280;
        }
        body { 
            font-family: 'Inter', Arial, sans-serif; 
            padding: 20px; 
            background: #f8fafc;
            color: #111827;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; }
        .message { 
            background: #d1fae5; 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin: 15px 0;
            border: 1px solid #a7f3d0;
        }
        .professional { 
            background: white; 
            border: 1px solid #e5e7eb; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .professional:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
        .btn { 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            display: inline-block;
            font-weight: 600;
            margin-right: 8px;
            border: none;
            cursor: pointer;
        }
        .btn-approve { background: var(--green); color: white; }
        .btn-reject { background: var(--red-primary); color: white; }
        .btn-logout { background: var(--gray); color: white; float: right; }
        .section-title { 
            color: #374151; 
            margin: 30px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.8rem; 
            font-weight: 600;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .skill-tag { 
            background: #f3f4f6; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.85rem;
            margin-right: 6px;
            display: inline-block;
            margin-top: 4px;
        }
        .stats { 
            display: flex; 
            gap: 20px; 
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .stat-box { 
            background: white; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #e5e7eb;
            min-width: 150px;
        }
        .stat-number { font-size: 1.5rem; font-weight: 700; }
        .stat-label { color: #6b7280; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Professional Account Approvals</h1>
            <a href="?logout=1" class="btn btn-logout">Logout</a>
            <div style="clear: both;"></div>
            
            <?php if (isset($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php
            // Get stats
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM professionals");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Professionals</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #d97706;"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #059669;"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #dc2626;"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>
        
        <!-- PENDING PROFESSIONALS -->
        <h2 class="section-title">Pending Approvals (<?php echo count($professionals); ?>)</h2>
        
        <?php if (empty($professionals)): ?>
            <p>No pending professional applications.</p>
        <?php endif; ?>
        
        <?php foreach ($professionals as $pro): ?>
        <div class="professional">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h3 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($pro['full_name']); ?></h3>
                    <p style="margin: 4px 0; color: #6b7280;">
                        <strong>ID:</strong> <?php echo htmlspecialchars($pro['professional_id']); ?> • 
                        <strong>Applied:</strong> <?php echo date('M j, Y', strtotime($pro['created_at'])); ?>
                    </p>
                    <p style="margin: 4px 0;"><strong>Email:</strong> <?php echo htmlspecialchars($pro['email']); ?></p>
                    <p style="margin: 4px 0;"><strong>Phone:</strong> <?php echo htmlspecialchars($pro['phone']); ?></p>
                    <p style="margin: 4px 0;"><strong>Zip Code:</strong> <?php echo htmlspecialchars($pro['zipcode']); ?></p>
                    <p style="margin: 4px 0;"><strong>Hourly Fee:</strong> $<?php echo number_format($pro['hourly_fee'], 2); ?>/hr</p>
                    
                    <?php if (!empty($pro['skills'])): ?>
<div style="margin: 10px 0;">
    <strong>Skills:</strong>
    <span class="skill-tag"><?php echo htmlspecialchars($pro['skills']); ?></span>
</div>
<?php endif; ?>
                </div>
                <div>
                    <span class="badge badge-pending">PENDING</span>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button onclick="location.href='?approve=1&id=<?php echo $pro['id']; ?>'" class="btn btn-approve">
                    ✓ Approve
                </button>
                <button onclick="location.href='?reject=1&id=<?php echo $pro['id']; ?>'" class="btn btn-reject">
                    ✗ Reject
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- RECENTLY APPROVED -->
        <?php if (!empty($approved_pros)): ?>
        <h2 class="section-title">Recently Approved (Last 10)</h2>
        <?php foreach ($approved_pros as $pro): ?>
        <div class="professional" style="opacity: 0.8;">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($pro['full_name']); ?></p>
                    <p style="margin: 4px 0; font-size: 0.9rem; color: #6b7280;">
                        ID: <?php echo htmlspecialchars($pro['professional_id']); ?> • 
                        Approved: <?php echo date('M j, Y g:ia', strtotime($pro['updated_at'])); ?>
                    </p>
                </div>
                <span class="badge badge-approved">APPROVED</span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-refresh page every 30 seconds if there are pending applications
        <?php if (!empty($professionals)): ?>
        setTimeout(function() {
            location.reload();
        }, 30000); // 30 seconds
        <?php endif; ?>
        
        // Logout handler
        if (window.location.search.includes('logout=1')) {
            fetch('?logout=1', {method: 'POST'})
                .then(() => window.location.href = window.location.pathname);
        }
    </script>
</body>
</html>
<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
