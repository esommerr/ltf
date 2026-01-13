<?php
// /public_html/admin/admin-dashboard.php
session_start();

// Fix the path to database.php since admin folder is at root level
require_once __DIR__ . '/../backend/config/database.php';

// Simple password protection - CHANGE THIS PASSWORD!
$admin_password = 'AdminPassword123'; // CHANGE TO SOMETHING SECURE
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - Learn the Fix</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --red-primary: #BF0A30;
                    --red-hover: #000000;
                    --bg-soft: #f9fafb;
                }
                body { 
                    font-family: 'Inter', sans-serif; 
                    background: var(--bg-soft);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .login-container {
                    background: white;
                    border-radius: 16px;
                    padding: 40px;
                    box-shadow: 0 12px 32px rgba(0,0,0,.08);
                    width: 100%;
                    max-width: 400px;
                    text-align: center;
                }
                .login-container h2 {
                    color: var(--red-primary);
                    margin-bottom: 8px;
                }
                .login-container p {
                    color: #6b7280;
                    margin-bottom: 30px;
                }
                input[type="password"] { 
                    width: 100%; 
                    padding: 14px; 
                    margin: 10px 0; 
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    font-size: 1rem;
                }
                button { 
                    background: var(--red-primary); 
                    color: white; 
                    border: none; 
                    padding: 14px; 
                    width: 100%; 
                    cursor: pointer;
                    border-radius: 8px;
                    font-size: 1rem;
                    font-weight: 600;
                    margin-top: 10px;
                }
                button:hover {
                    background: var(--red-hover);
                }
                .error {
                    color: #dc2626;
                    margin-top: 10px;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>🔐 Admin Login</h2>
                <p>Enter admin password to continue</p>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter admin password" required>
                    <button type="submit">Sign In</button>
                </form>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="error">Incorrect password. Please try again.</div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Get database connection
$pdo = getDBConnection();

// Handle actions
$message = '';
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'approve':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("UPDATE professionals SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "✅ Professional approved successfully!";
            break;
            
        case 'reject':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("UPDATE professionals SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "❌ Professional rejected.";
            break;
            
        case 'delete_user':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "🗑️ User deleted.";
            break;
            
        case 'delete_pro':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM professionals WHERE id = ?");
            $stmt->execute([$id]);
            $message = "🗑️ Professional deleted.";
            break;
    }
}

// Get all data
$users = $pdo->query("
    SELECT id, user_id, first_name, last_name, email, phone, zipcode, username, created_at 
    FROM users 
    ORDER BY created_at DESC
")->fetchAll();

$professionals = $pdo->query("
    SELECT id, professional_id, first_name, last_name, email, phone, zipcode, 
           username, skills, hourly_fee, status, created_at 
    FROM professionals 
    ORDER BY created_at DESC
")->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM professionals) as total_pros,
        (SELECT COUNT(*) FROM professionals WHERE status = 'pending') as pending_pros,
        (SELECT COUNT(*) FROM professionals WHERE status = 'approved') as approved_pros,
        (SELECT COUNT(*) FROM professionals WHERE status = 'rejected') as rejected_pros
")->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Learn the Fix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red-primary: #BF0A30;
            --red-hover: #000000;
            --green: #059669;
            --yellow: #d97706;
            --blue: #3b82f6;
            --gray: #6b7280;
            --bg-main: #ffffff;
            --bg-soft: #f9fafb;
            --border-soft: #e5e7eb;
        }
        
        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: var(--bg-soft);
            color: #111827;
        }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header { 
            background: var(--bg-main); 
            padding: 24px; 
            border-radius: 16px; 
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { margin: 0; color: var(--red-primary); font-size: 1.8rem; }
        .header p { margin: 5px 0 0 0; color: #6b7280; }
        
        .logout-btn {
            background: var(--red-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .logout-btn:hover { background: var(--red-hover); }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-main);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border-soft);
        }
        
        .stat-card h3 {
            margin: 0 0 12px 0;
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }
        
        .stat-user { color: var(--red-primary); }
        .stat-pro { color: var(--green); }
        .stat-pending { color: var(--yellow); }
        .stat-approved { color: var(--green); }
        
        /* Message */
        .message {
            padding: 16px 20px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #a7f3d0;
            font-weight: 500;
        }
        
        /* Tables Section */
        .section {
            background: var(--bg-main);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid var(--border-soft);
        }
        
        .section h2 {
            margin-top: 0;
            color: #111827;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-soft);
            font-size: 1.3rem;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-soft);
        }
        
        th {
            background: var(--bg-soft);
            font-weight: 600;
            color: #374151;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: var(--bg-soft);
        }
        
        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .pending { background: #fef3c7; color: #92400e; }
        .approved { background: #d1fae5; color: #065f46; }
        .rejected { background: #fee2e2; color: #991b1b; }
        
        /* Buttons */
        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            margin: 2px;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-delete { background: #6b7280; color: white; }
        .btn-view { background: var(--blue); color: white; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 15px; }
            .header { flex-direction: column; text-align: center; gap: 15px; }
            .header h1 { font-size: 1.5rem; }
            table { display: block; overflow-x: auto; }
            .stats-grid { grid-template-columns: 1fr; }
            .section { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>📊 Admin Dashboard</h1>
                <p>Monitor all user and professional registrations</p>
            </div>
            <form method="POST">
                <button type="submit" class="logout-btn" name="logout">Logout</button>
            </form>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👥 Total Users</h3>
                <div class="stat-value stat-user"><?php echo $stats['total_users']; ?></div>
                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 0.9rem;">Registered users</p>
            </div>
            <div class="stat-card">
                <h3>🛠️ Total Professionals</h3>
                <div class="stat-value stat-pro"><?php echo $stats['total_pros']; ?></div>
                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 0.9rem;">All professionals</p>
            </div>
            <div class="stat-card">
                <h3>⏳ Pending Approval</h3>
                <div class="stat-value stat-pending"><?php echo $stats['pending_pros']; ?></div>
                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 0.9rem;">Awaiting review</p>
            </div>
            <div class="stat-card">
                <h3>✅ Approved</h3>
                <div class="stat-value stat-approved"><?php echo $stats['approved_pros']; ?></div>
                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 0.9rem;">Active professionals</p>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="section">
            <h2>👤 User Registrations (<?php echo count($users); ?>)</h2>
            <?php if (empty($users)): ?>
                <div class="empty-state">No users have registered yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Zip Code</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['user_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo htmlspecialchars($user['zipcode']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-delete" onclick="if(confirm('Delete user: <?php echo addslashes($user['first_name'] . ' ' . $user['last_name']); ?>?')) window.location='?action=delete_user&id=<?php echo $user['id']; ?>'">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Professionals Table -->
        <div class="section">
            <h2>🛠️ Professional Registrations (<?php echo count($professionals); ?>)</h2>
            <?php if (empty($professionals)): ?>
                <div class="empty-state">No professionals have registered yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pro ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Hourly Fee</th>
                            <th>Zip Code</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($professionals as $pro): ?>
                        <tr>
                            <td><?php echo $pro['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($pro['professional_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pro['first_name'] . ' ' . $pro['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($pro['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $pro['status']; ?>">
                                    <?php echo ucfirst($pro['status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($pro['hourly_fee'], 2); ?></td>
                            <td><?php echo htmlspecialchars($pro['zipcode']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($pro['created_at'])); ?></td>
                            <td>
                                <?php if($pro['status'] == 'pending'): ?>
                                <button class="btn btn-approve" onclick="if(confirm('Approve <?php echo addslashes($pro['first_name'] . ' ' . $pro['last_name']); ?>?')) window.location='?action=approve&id=<?php echo $pro['id']; ?>'">Approve</button>
                                <button class="btn btn-reject" onclick="if(confirm('Reject <?php echo addslashes($pro['first_name'] . ' ' . $pro['last_name']); ?>?')) window.location='?action=reject&id=<?php echo $pro['id']; ?>'">Reject</button>
                                <?php endif; ?>
                                <button class="btn btn-delete" onclick="if(confirm('Delete professional <?php echo addslashes($pro['first_name'] . ' ' . $pro['last_name']); ?>?')) window.location='?action=delete_pro&id=<?php echo $pro['id']; ?>'">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px; padding: 20px; color: #9ca3af; font-size: 0.9rem;">
            <p>Last updated: <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p>Dashboard auto-refreshes every 5 minutes</p>
        </div>
    </div>
    
    <script>
    // Auto-refresh every 5 minutes to see new registrations
    setTimeout(function() {
        window.location.reload();
    }, 300000); // 5 minutes = 300000 milliseconds
    
    // Auto-hide message after 5 seconds
    setTimeout(function() {
        const message = document.querySelector('.message');
        if (message) {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(() => message.remove(), 500);
        }
    }, 5000);
    </script>
</body>
</html>
<?php
// Logout handler
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin-dashboard.php');
    exit;
}
?>
