<?php
// booking/form.php - LOCATED IN /booking/ FOLDER
require_once '../backend/config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get professional ID from URL
$pro_id = isset($_GET['pro_id']) ? intval($_GET['pro_id']) : 0;

// Fetch professional details using PDO
$pro_details = null;
if ($pro_id > 0) {
    try {
        // Get PDO connection
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT id, full_name as name, COALESCE(profession, 'General Services') as category FROM professionals WHERE id = ?");
        $stmt->execute([$pro_id]);
        $pro_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Booking form error: " . $e->getMessage());
        $pro_details = null;
    }
}

// If no professional found, redirect
if (!$pro_details) {
    header("Location: ../search-results.php");  // Changed from search.php
    exit();
}

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $name = trim($_POST['customer_name']);
    $email = filter_var(trim($_POST['customer_email']), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['customer_phone']);
    $description = trim($_POST['job_description']);
    $date = $_POST['preferred_date'];
    $time = $_POST['preferred_time'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($description)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Get PDO connection (reuse or get fresh)
            $pdo = getDBConnection();
            
            // Insert booking into database using PDO
            $stmt = $pdo->prepare("INSERT INTO bookings 
                                   (professional_id, customer_name, customer_email, customer_phone, 
                                    job_description, preferred_date, preferred_time, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->execute([$pro_id, $name, $email, $phone, $description, $date, $time]);
$booking_id = $pdo->lastInsertId();

// DEBUG: Show what happened
echo "<div style='background: #e8f5e8; padding: 20px; margin: 20px; border: 2px solid #4caf50;'>
        <h3>✅ Booking Saved Successfully!</h3>
        <p>Booking ID: <strong>$booking_id</strong></p>
        <p>Professional ID: <strong>$pro_id</strong></p>
        <p>Would normally redirect to: <code>confirmation.php?booking_id=$booking_id</code></p>
        <a href='confirmation.php?booking_id=$booking_id' class='btn btn-success'>
            Go to Confirmation Page Manually
        </a>
        <br><br>
        <a href='../profile.php?id=$pro_id' class='btn btn-secondary'>
            Back to Professional Profile
        </a>
      </div>";

// Temporarily COMMENT OUT the redirect to see the debug info
// header("Location: confirmation.php?booking_id=" . $booking_id);
// exit();
            
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $booking_id);
            exit();
            
        } catch (PDOException $e) {
            $error_message = "Sorry, there was an error submitting your request. Please try again.";
            error_log("Booking error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($pro_details['name']); ?> - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <!-- FIXED PATH for header include -->
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-4">
                    <a href="../profile.php?id=<?php echo $pro_id; ?>" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <h2 class="mb-0">Book <?php echo htmlspecialchars($pro_details['name']); ?></h2>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Remember:</strong> You'll learn the skill while the professional works!
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                    
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <p class="mb-1"><strong>Professional:</strong> <?php echo htmlspecialchars($pro_details['name']); ?></p>
                            <p class="mb-0 text-muted"><strong>Category:</strong> <?php echo htmlspecialchars($pro_details['category']); ?></p>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="professional_id" value="<?php echo $pro_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Your Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="customer_name" 
                                           value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>" 
                                           placeholder="John Smith" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" name="customer_email" 
                                               value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>" 
                                               placeholder="you@example.com" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input type="tel" class="form-control" name="customer_phone" 
                                               value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>" 
                                               placeholder="(555) 123-4567">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">What do you need help with? *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tools"></i></span>
                                    <textarea class="form-control" name="job_description" rows="4" 
                                              placeholder="Describe the task, project, or skill you want to learn..." required><?php echo isset($_POST['job_description']) ? htmlspecialchars($_POST['job_description']) : ''; ?></textarea>
                                </div>
                                <small class="form-text text-muted">Be specific so the professional can prepare properly</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Preferred Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                        <input type="date" class="form-control" name="preferred_date" 
                                               min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo isset($_POST['preferred_date']) ? $_POST['preferred_date'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Preferred Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                        <input type="time" class="form-control" name="preferred_time" 
                                               value="<?php echo isset($_POST['preferred_time']) ? $_POST['preferred_time'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-lightbulb"></i>
                                <strong>Learning Opportunity:</strong> This is your chance to watch and learn! The professional will explain what they're doing as they work.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="../profile.php?id=<?php echo $pro_id; ?>" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-calendar-check"></i> Submit Booking Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4 text-center text-muted">
                    <small>
                        <i class="bi bi-shield-check"></i> Your information is secure and will only be shared with the professional
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FIXED PATH for footer include -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
