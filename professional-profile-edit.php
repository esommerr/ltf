<?php
// edit-professional-profile.php - Professional business profile editor
session_start();
require_once 'backend/config/database.php';

// Must be logged in as professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'professional') {
    header("Location: login.php");
    exit();
}

$pro_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Get current professional data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            full_name,
            profession,
            hourly_rate,
            bio,
            specialties,
            years_experience,
            service_area,
            phone,
            email
        FROM professionals 
        WHERE id = ?
    ");
    $stmt->execute([$pro_id]);
    $professional = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professional) {
        throw new Exception("Professional not found");
    }
    
} catch (Exception $e) {
    error_log("Edit profile error: " . $e->getMessage());
    $errors[] = "Unable to load profile data.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $profession = trim($_POST['profession'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $specialties = trim($_POST['specialties'] ?? '');
    $years_experience = intval($_POST['years_experience'] ?? 0);
    $service_area = trim($_POST['service_area'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if ($hourly_rate < 10 || $hourly_rate > 500) {
        $errors[] = "Hourly rate must be between $10 and $500";
    }
    
    if (empty($profession)) {
        $errors[] = "Profession is required";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE professionals 
                SET hourly_rate = ?,
                    profession = ?,
                    bio = ?,
                    specialties = ?,
                    years_experience = ?,
                    service_area = ?,
                    phone = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $hourly_rate,
                $profession,
                $bio,
                $specialties,
                $years_experience,
                $service_area,
                $phone,
                $pro_id
            ]);
            
            $success = true;
            
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Professional Profile - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4 mb-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="professional-dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Edit Professional Profile</li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Edit Your Professional Profile</h4>
                        <small class="opacity-75">This information appears on your public profile</small>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Profile updated successfully! 
                                <a href="profile.php?id=<?php echo $pro_id; ?>" target="_blank">View your public profile</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hourly Rate ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="10" max="500" 
                                               class="form-control" name="hourly_rate"
                                               value="<?php echo htmlspecialchars($professional['hourly_rate'] ?? ''); ?>" required>
                                        <span class="input-group-text">/hr</span>
                                    </div>
                                    <small class="text-muted">Set your hourly rate for "Learn the Fix" services</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profession</label>
                                    <input type="text" class="form-control" name="profession"
                                           value="<?php echo htmlspecialchars($professional['profession'] ?? ''); ?>" required>
                                    <small class="text-muted">e.g., Electrician, Plumber, Carpenter</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Specialties</label>
                                <input type="text" class="form-control" name="specialties"
                                       value="<?php echo htmlspecialchars($professional['specialties'] ?? ''); ?>">
                                <small class="text-muted">Comma-separated: Electrical repairs, Lighting installation, Outlet replacement</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Years of Experience</label>
                                    <input type="number" min="0" max="50" class="form-control" 
                                           name="years_experience"
                                           value="<?php echo htmlspecialchars($professional['years_experience'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Service Area</label>
                                    <input type="text" class="form-control" name="service_area"
                                           value="<?php echo htmlspecialchars($professional['service_area'] ?? ''); ?>">
                                    <small class="text-muted">e.g., New York Metro, Northern NJ</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Professional Bio</label>
                                <textarea class="form-control" name="bio" rows="4"><?php echo htmlspecialchars($professional['bio'] ?? ''); ?></textarea>
                                <small class="text-muted">Tell customers about your expertise and teaching style</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Business Phone</label>
                                <input type="tel" class="form-control" name="phone"
                                       value="<?php echo htmlspecialchars($professional['phone'] ?? ''); ?>">
                                <small class="text-muted">This will be displayed to customers</small>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="professional-dashboard.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Professional Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
