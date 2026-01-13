<?php
// /public_html/user-dashboard/index.php
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /sign-in/');
    exit;
}

// Ensure this is a regular user, not a professional
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'professional') {
    header('Location: /pro-dashboard/');
    exit;
}

// Set user type if not set (for compatibility)
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'user';
}

// Include database if needed
// require_once '../backend/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Learn the Fix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        :root {
            --red-primary: #BF0A30;
            --red-hover: #a00828;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --bg-main: #ffffff;
            --bg-soft: #f9fafb;
            --bg-subtle: #f3f4f6;
            --border-soft: #e5e7eb;
            --success-green: #059669;
            --info-blue: #3b82f6;
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
            flex-direction: column;
        }
        
        .dashboard-header {
            background: var(--bg-main);
            border-bottom: 1px solid var(--border-soft);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo h1 {
            color: var(--red-primary);
            font-size: 1.5rem;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .user-type {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .logout-btn {
            background: var(--red-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .logout-btn:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
        }
        
        .dashboard-container {
            display: flex;
            flex: 1;
        }
        
        .sidebar {
            width: 250px;
            background: var(--bg-main);
            border-right: 1px solid var(--border-soft);
            padding: 30px 0;
        }
        
        .nav-item {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: var(--bg-soft);
            color: var(--red-primary);
        }
        
        .nav-item.active {
            background: var(--bg-soft);
            color: var(--red-primary);
            border-left: 3px solid var(--red-primary);
        }
        
        .main-content {
            flex: 1;
            padding: 40px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--red-primary), #a00828);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .welcome-section h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .user-id-display {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 10px;
            font-family: monospace;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }
        
        .dashboard-card {
            background: var(--bg-main);
            border: 1px solid var(--border-soft);
            border-radius: 12px;
            padding: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            background: var(--bg-soft);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--red-primary);
            font-size: 1.5rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .card-content {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .card-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--red-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--red-hover);
        }
        
        .btn-secondary {
            background: var(--bg-soft);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        
        .btn-secondary:hover {
            background: var(--bg-subtle);
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border-soft);
                padding: 20px 0;
            }
            
            .dashboard-header {
                padding: 20px;
                flex-direction: column;
                gap: 20px;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="logo">
            <h1>Learn the Fix</h1>
            <span class="user-type">User Dashboard</span>
        </div>
        
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                <div class="user-type">ID: <?php echo htmlspecialchars($_SESSION['user_uid'] ?? 'N/A'); ?></div>
            </div>
            <a href="/backend/auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    
    <!-- Main Layout -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <a href="/user-dashboard/" class="nav-item active">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="/user-dashboard/profile.php" class="nav-item">
                <span>👤</span>
                <span>My Profile</span>
            </a>
            <a href="/user-dashboard/bookings.php" class="nav-item">
                <span>📅</span>
                <span>My Bookings</span>
            </a>
            <a href="/user-dashboard/messages.php" class="nav-item">
                <span>💬</span>
                <span>Messages</span>
            </a>
            <a href="/user-dashboard/settings.php" class="nav-item">
                <span>⚙️</span>
                <span>Settings</span>
            </a>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h2>
                <p>Here's what's happening with your account today.</p>
                <div class="user-id-display">
                    User ID: <?php echo htmlspecialchars($_SESSION['user_uid'] ?? 'Not assigned'); ?>
                </div>
            </section>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">📅</div>
                        <h3 class="card-title">Upcoming Bookings</h3>
                    </div>
                    <div class="card-content">
                        <p>You have no upcoming appointments.</p>
                    </div>
                    <div class="card-actions">
                        <a href="/book-professional/" class="btn btn-primary">Book a Professional</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">💬</div>
                        <h3 class="card-title">Messages</h3>
                    </div>
                    <div class="card-content">
                        <p>No new messages.</p>
                    </div>
                    <div class="card-actions">
                        <a href="/user-dashboard/messages.php" class="btn btn-secondary">View Messages</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">⭐</div>
                        <h3 class="card-title">Profile Status</h3>
                    </div>
                    <div class="card-content">
                        <p>Your profile is 60% complete.</p>
                        <div style="background: var(--bg-subtle); height: 6px; border-radius: 3px; margin: 10px 0;">
                            <div style="width: 60%; height: 100%; background: var(--red-primary); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="/user-dashboard/profile.php" class="btn btn-primary">Complete Profile</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">📝</div>
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="card-content">
                        <p>No recent activity.</p>
                    </div>
                    <div class="card-actions">
                        <a href="/user-dashboard/activity.php" class="btn btn-secondary">View All Activity</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Simple dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Logout confirmation
            document.querySelector('.logout-btn')?.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
            
            // Update local time
            function updateTime() {
                const now = new Date();
                document.getElementById('current-time')?.textContent = 
                    now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }
            
            updateTime();
            setInterval(updateTime, 60000);
        });
    </script>
</body>
</html>
