<?php
// booking/confirmation.php - PDO VERSION
require_once '../backend/config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Fetch booking details using PDO
$booking = null;
if ($booking_id > 0) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT b.*, p.full_name as pro_name, COALESCE(p.profession, 'General Services') as category 
            FROM bookings b 
            JOIN professionals p ON b.professional_id = p.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// If no booking found, redirect
if (!$booking) {
    header("Location: ../search-results.php"); // Fixed from search.php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="alert alert-success py-4">
                    <i class="bi bi-check-circle-fill display-1 text-success mb-3"></i>
                    <h2 class="mb-3">Booking Request Submitted!</h2>
                    <p class="lead">Your learning session has been scheduled.</p>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Booking Details</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Professional:</span>
                                <strong><?php echo htmlspecialchars($booking['pro_name']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Category:</span>
                                <span><?php echo htmlspecialchars($booking['category']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Booking Reference:</span>
                                <code>LTF-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></code>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Status:</span>
                                <span class="badge bg-warning">Pending Confirmation</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>What happens next?</strong> The professional will contact you within 24 hours to confirm the details.
                </div>
                
                <div class="mt-4">
                    <a href="../profile.php?id=<?php echo $booking['professional_id']; ?>" 
                       class="btn btn-outline-primary me-2">
                       <i class="bi bi-person"></i> View Professional
                    </a>
                    <a href="../search-results.php" class="btn btn-primary"> <!-- Fixed from search.php -->
                       <i class="bi bi-search"></i> Find Another Pro
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
