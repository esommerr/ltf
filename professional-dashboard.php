<?php
// professional-dashboard.php - Dashboard for professionals
session_start();
require_once 'backend/config/database.php';

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'professional') {
    header("Location: login.php");
    exit();
}

// Get professional data
$pro_id = $_SESSION['user_id'];
$professional = null;

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            id,
            professional_id,
            full_name,
            email,
            phone,
            profession,
            hourly_rate,
            bio,
            rating,
            total_jobs as jobs_completed,
            status,
            created_at
        FROM professionals 
        WHERE id = ?
    ");
    
    $stmt->execute([$pro_id]);
    $professional = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Professional dashboard error: " . $e->getMessage());
    $professional = null;
}

if (!$professional) {
    // Handle error
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get dashboard stats
try {
    // Today's bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as today_bookings
        FROM bookings 
        WHERE pro_id = ? 
        AND DATE(booking_date) = CURDATE()
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$pro_id]);
    $today_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_earnings
        FROM bookings 
        WHERE pro_id = ? 
        AND status = 'completed'
    ");
    $stmt->execute([$pro_id]);
    $earnings_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Unread messages
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_messages
        FROM messages 
        WHERE recipient_id = ? 
        AND recipient_type = 'professional'
        AND is_read = 0
    ");
    $stmt->execute([$pro_id]);
    $message_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update session with actual unread count
    $_SESSION['unread_messages'] = $message_stats['unread_messages'] ?? 0;
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Dashboard - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/messaging.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd 0%, #198754 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            transition: all 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .quick-action-btn {
            padding: 1rem;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .quick-action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .booking-item {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .booking-item.completed {
            border-left-color: #198754;
        }
        .booking-item.cancelled {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-2">Welcome back, <?php echo htmlspecialchars($professional['full_name']); ?>!</h1>
                    <p class="mb-0">
                        <span class="badge bg-light text-dark me-2">PRO-<?php echo substr($professional['professional_id'], -6); ?></span>
                        <span class="me-3"><i class="bi bi-star-fill text-warning"></i> <?php echo $professional['rating'] ?? 'N/A'; ?></span>
                        <span><i class="bi bi-briefcase"></i> <?php echo $professional['jobs_completed'] ?? 0; ?> jobs completed</span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <a href="edit-professional-profile.php" class="btn btn-light">
                            <i class="bi bi-pencil me-2"></i>Edit Profile
                        </a>
                        <a href="profile.php?id=<?php echo $pro_id; ?>" class="btn btn-outline-light" target="_blank">
                            <i class="bi bi-eye me-2"></i>View Public Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mb-5">
        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 opacity-75">Today's Bookings</h6>
                                <h2 class="card-title mb-0"><?php echo $today_stats['today_bookings'] ?? 0; ?></h2>
                            </div>
                            <i class="bi bi-calendar-check stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 opacity-75">Total Earnings</h6>
                                <h2 class="card-title mb-0">$<?php echo number_format($earnings_stats['total_earnings'] ?? 0, 2); ?></h2>
                            </div>
                            <i class="bi bi-currency-dollar stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 opacity-75">Unread Messages</h6>
                                <h2 class="card-title mb-0"><?php echo $_SESSION['unread_messages']; ?></h2>
                            </div>
                            <i class="bi bi-chat-dots stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 opacity-75">Response Rate</h6>
                                <h2 class="card-title mb-0">98%</h2>
                            </div>
                            <i class="bi bi-graph-up stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column: Quick Actions & Recent Bookings -->
            <div class="col-lg-8">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="my-bookings.php" class="quick-action-btn bg-light border">
                                    <i class="bi bi-calendar-check text-primary display-6 mb-2"></i>
                                    <h6>My Bookings</h6>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="my-messages.php" class="quick-action-btn bg-light border">
                                    <i class="bi bi-chat-dots text-info display-6 mb-2"></i>
                                    <h6>Messages</h6>
                                    <?php if ($_SESSION['unread_messages'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $_SESSION['unread_messages']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="availability.php" class="quick-action-btn bg-light border">
                                    <i class="bi bi-clock text-warning display-6 mb-2"></i>
                                    <h6>Availability</h6>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="earnings.php" class="quick-action-btn bg-light border">
                                    <i class="bi bi-cash-stack text-success display-6 mb-2"></i>
                                    <h6>Earnings</h6>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings</h5>
                        <a href="my-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div id="recent-bookings">
                            <!-- Will be populated via JavaScript or PHP -->
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT b.id, b.customer_name, b.service_type, b.booking_date, 
                                           b.status, b.total_amount, b.created_at
                                    FROM bookings b
                                    WHERE b.pro_id = ?
                                    ORDER BY b.booking_date DESC
                                    LIMIT 5
                                ");
                                $stmt->execute([$pro_id]);
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (empty($bookings)) {
                                    echo '<p class="text-muted text-center py-4">No bookings yet.</p>';
                                } else {
                                    foreach ($bookings as $booking) {
                                        $status_class = '';
                                        if ($booking['status'] === 'completed') $status_class = 'completed';
                                        if ($booking['status'] === 'cancelled') $status_class = 'cancelled';
                                        ?>
                                        <div class="booking-item p-3 bg-light rounded <?php echo $status_class; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($booking['service_type']); ?></h6>
                                                    <p class="mb-1 small">
                                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($booking['customer_name']); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <div class="h5 mb-1">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                                                    <span class="badge bg-<?php 
                                                        echo $booking['status'] === 'confirmed' ? 'success' : 
                                                             ($booking['status'] === 'pending' ? 'warning' : 
                                                             ($booking['status'] === 'completed' ? 'primary' : 'danger')); 
                                                    ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<p class="text-danger text-center py-4">Error loading bookings.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Profile & Notifications -->
            <div class="col-lg-4">
                <!-- Profile Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Hourly Rate</small>
                            <h4>$<?php echo number_format($professional['hourly_rate'], 2); ?>/hr</h4>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Profession</small>
                            <p class="mb-0"><?php echo htmlspecialchars($professional['profession']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Account Status</small>
                            <p class="mb-0">
                                <span class="badge bg-<?php echo $professional['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($professional['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Member Since</small>
                            <p class="mb-0"><?php echo date('F Y', strtotime($professional['created_at'])); ?></p>
                        </div>
                        <a href="edit-professional-profile.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-pencil me-2"></i>Edit Profile Details
                        </a>
                    </div>
                </div>
                
                <!-- Recent Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-info-circle text-info"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted">Just now</small>
                                        <p class="mb-0 small">Welcome to your dashboard!</p>
                                    </div>
                                </div>
                            </div>
                            <!-- More notifications would go here -->
                        </div>
                        <div class="text-center mt-3">
                            <a href="notifications.php" class="btn btn-sm btn-outline-secondary">View All Notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh unread messages count every 30 seconds
        setInterval(function() {
            fetch('backend/api/get-unread-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread_messages !== undefined) {
                        // Update badge in header
                        const badge = document.querySelector('.badge.bg-danger');
                        if (badge) {
                            if (data.unread_messages > 0) {
                                badge.textContent = data.unread_messages;
                                badge.style.display = 'inline-block';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                        // Update stat card
                        const statCard = document.querySelector('.bg-info .card-title');
                        if (statCard) {
                            statCard.textContent = data.unread_messages;
                        }
                    }
                })
                .catch(error => console.error('Error refreshing messages:', error));
        }, 30000); // 30 seconds
    </script>
</body>
</html>
