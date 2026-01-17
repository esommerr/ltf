<?php
// profile.php - Customer-facing professional profile
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'backend/config/database.php';

// Get professional ID from URL
$pro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch professional details
$professional = null;
if ($pro_id > 0) {
    try {
        // Get PDO connection
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                id,
                full_name,
                profession,
                zipcode,
                city,
                state,
                hourly_rate,
                bio,
                rating,
                total_jobs as jobs_completed,
                specialties,
                years_experience,
                is_verified,
                service_area
            FROM professionals 
            WHERE id = ?
        ");
        
        $stmt->execute([$pro_id]);
        $professional = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Profile error: " . $e->getMessage());
        $professional = null;
    }
}

// Redirect if no professional found
if (!$professional) {
    header("Location: search-results.php");
    exit();
}

// Format data - USING CORRECT COLUMN NAMES
$rating = $professional['rating'] ? number_format($professional['rating'], 1) : 'N/A';
$review_count = $professional['jobs_completed'] ?? 0;
$skills = $professional['specialties'] ?? 'General handyman services';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($professional['full_name']); ?> - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="search-results.php">Find Pros</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($professional['full_name']); ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <!-- Professional Info -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 me-4">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px; font-size: 2.5rem; font-weight: bold;">
                                    <?php 
                                    $names = explode(' ', $professional['full_name']);
                                    echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Details -->
                            <div class="flex-grow-1">
                                <h1 class="card-title h2 mb-2"><?php echo htmlspecialchars($professional['full_name']); ?></h1>
                                
                                <div class="mb-3">
                                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($professional['profession']); ?></span>
                                    <span class="ms-2 text-muted">
                                        <i class="bi bi-geo-alt"></i> 
                                        <?php echo htmlspecialchars($professional['zipcode']); ?>
                                    </span>
                                </div>
                                
                                <!-- Rating -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="text-warning me-2">
                                            <?php 
                                            $numeric_rating = floatval($rating);
                                            for ($i = 1; $i <= 5; $i++): 
                                                if ($i <= floor($numeric_rating)) {
                                                    echo '<i class="bi bi-star-fill"></i>';
                                                } elseif ($i == ceil($numeric_rating) && fmod($numeric_rating, 1) >= 0.5) {
                                                    echo '<i class="bi bi-star-half"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star"></i>';
                                                }
                                            endfor; 
                                            ?>
                                        </div>
                                        <strong class="me-2"><?php echo $rating; ?></strong>
                                        <span class="text-muted">(<?php echo $review_count; ?> reviews)</span>
                                    </div>
                                </div>
                                
                                <!-- After rating section, add: -->
<div class="mb-3">
    <div id="message-professional-button"></div>
</div>
                                
                                <!-- Skills -->
                                <div class="mb-4">
                                    <h5 class="h6">Skills & Expertise</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $skill_list = explode(',', $skills);
                                        foreach ($skill_list as $skill): 
                                            if (!empty(trim($skill))):
                                        ?>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Bio -->
                                <div class="mb-4">
                                    <h5 class="h6">About</h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($professional['bio'])); ?></p>
                                </div>
                                
                                <!-- Stats -->
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-3">
                                                <h4 class="mb-1">$<?php echo number_format($professional['hourly_rate'], 2); ?></h4>
                                                <small class="text-muted">Hourly Rate</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-3">
                                                <h4 class="mb-1"><?php echo $professional['jobs_completed'] ?? 0; ?></h4>
                                                <small class="text-muted">Jobs Completed</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-3">
                                                <h4 class="mb-1">98%</h4>
                                                <small class="text-muted">Response Rate</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Book This Pro</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="display-6 text-success mb-2">$<?php echo number_format($professional['hourly_rate'], 2); ?>/hr</div>
                            <p class="text-muted">Learn while they work!</p>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3">What's included:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Professional service</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Hands-on learning</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> All necessary tools</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i> Cleanup included</li>
                            </ul>
                        </div>
                        
                        <!-- ✅ BOOKING BUTTON -->
                        <div class="d-grid gap-2">
                            <a href="booking/form.php?pro_id=<?php echo $professional['id']; ?>" 
                               class="btn btn-success btn-lg py-3">
                               <i class="bi bi-calendar-check me-2"></i>
                               Book & Learn Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
       <!-- Messaging Button Script -->
    <script type="module">
        import { createMessageButton } from './js/messaging-button.js';
        
        const container = document.getElementById('message-professional-button');
        if (container) {
            const messageBtn = createMessageButton({
                professionalId: '<?php echo $professional['id']; ?>',
                variant: 'outlined',
                buttonText: 'Message ' + '<?php echo addslashes($professional['full_name']); ?>'
            });
            container.appendChild(messageBtn);
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
