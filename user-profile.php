<?php
// user-profile.php - User's own profile page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'backend/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user data
$user_id = $_SESSION['user_id'];
$user = null;

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            id,
            full_name,
            email,
            phone,
            address,
            city,
            state,
            zipcode,
            profile_picture,
            bio,
            created_at
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("User profile error: " . $e->getMessage());
    $user = null;
}

if (!$user) {
    // Handle error - user not found
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/messaging.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border: 5px solid white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle profile-avatar bg-white d-flex align-items-center justify-content-center overflow-hidden">
                        <?php if ($user['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                 class="w-100 h-100">
                        <?php else: ?>
                            <div class="display-4 text-primary">
                                <?php 
                                $names = explode(' ', $user['full_name']);
                                echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <h1 class="display-5 mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="mb-1">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?php echo htmlspecialchars($user['city'] . ', ' . $user['state']); ?>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-envelope me-1"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
                <div class="col-auto">
                    <a href="edit-profile.php" class="btn btn-light">
                        <i class="bi bi-pencil me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mb-5">
        <div class="row">
            <!-- Left Column: Stats & Quick Actions -->
            <div class="col-lg-4">
                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">My Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stat-card">
                                    <div class="display-6 text-primary mb-1" id="bookings-count">0</div>
                                    <small class="text-muted">Bookings</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-card">
                                    <div class="display-6 text-success mb-1" id="messages-count">0</div>
                                    <small class="text-muted">Messages</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="display-6 text-warning mb-1" id="favorites-count">0</div>
                                    <small class="text-muted">Favorites</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="display-6 text-info mb-1" id="learning-count">0</div>
                                    <small class="text-muted">Skills Learning</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="search-results.php" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Find a Pro
                            </a>
                            <a href="my-bookings.php" class="btn btn-outline-primary">
                                <i class="bi bi-calendar-check me-2"></i>My Bookings
                            </a>
                            <a href="my-messages.php" class="btn btn-outline-primary">
                                <i class="bi bi-chat-dots me-2"></i>My Messages
                            </a>
                            <a href="favorites.php" class="btn btn-outline-primary">
                                <i class="bi bi-heart me-2"></i>Favorites
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Account Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Account Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Member since:</dt>
                            <dd class="col-sm-7"><?php echo date('F Y', strtotime($user['created_at'])); ?></dd>
                            
                            <dt class="col-sm-5">Email:</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($user['email']); ?></dd>
                            
                            <dt class="col-sm-5">Phone:</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></dd>
                            
                            <dt class="col-sm-5">Location:</dt>
                            <dd class="col-sm-7">
                                <?php 
                                echo htmlspecialchars(
                                    implode(', ', array_filter([
                                        $user['address'],
                                        $user['city'],
                                        $user['state'],
                                        $user['zipcode']
                                    ]))
                                ); 
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Bio & Activity -->
            <div class="col-lg-8">
                <!-- Bio -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">About Me</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="edit-bio-btn">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="bio-content">
                            <?php if (!empty($user['bio'])): ?>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No bio yet. <a href="edit-profile.php">Add one</a> to tell pros about your learning goals!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush" id="recent-activity">
                            <!-- Will be populated by JavaScript -->
                            <div class="list-group-item text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fetch user stats (you'll need to create these API endpoints)
        async function loadUserStats() {
            try {
                const response = await fetch(`/backend/api/user-stats.php?user_id=<?php echo $user_id; ?>`);
                const stats = await response.json();
                
                document.getElementById('bookings-count').textContent = stats.bookings || 0;
                document.getElementById('messages-count').textContent = stats.messages || 0;
                document.getElementById('favorites-count').textContent = stats.favorites || 0;
                document.getElementById('learning-count').textContent = stats.skills_learning || 0;
                
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        // Fetch recent activity
        async function loadRecentActivity() {
            try {
                const response = await fetch(`/backend/api/recent-activity.php?user_id=<?php echo $user_id; ?>`);
                const activities = await response.json();
                
                const container = document.getElementById('recent-activity');
                container.innerHTML = '';
                
                if (activities.length === 0) {
                    container.innerHTML = `
                        <div class="list-group-item text-center py-4 text-muted">
                            <i class="bi bi-info-circle display-6 mb-3"></i>
                            <p>No recent activity yet. Book your first pro to get started!</p>
                        </div>
                    `;
                    return;
                }
                
                activities.forEach(activity => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item';
                    item.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${activity.title}</h6>
                            <small class="text-muted">${activity.time_ago}</small>
                        </div>
                        <p class="mb-1">${activity.description}</p>
                        ${activity.link ? `<a href="${activity.link}" class="btn btn-sm btn-outline-primary mt-2">View</a>` : ''}
                    `;
                    container.appendChild(item);
                });
                
            } catch (error) {
                console.error('Error loading activity:', error);
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUserStats();
            loadRecentActivity();
            
            // Edit bio button
            document.getElementById('edit-bio-btn')?.addEventListener('click', function() {
                window.location.href = 'edit-profile.php?section=bio';
            });
        });
    </script>
</body>
</html>
