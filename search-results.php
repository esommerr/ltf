<?php
// search-results.php - UPDATED FOR PDO
require_once 'backend/config/database.php';

$pdo = getDBConnection();

$search_query = $_GET['query'] ?? '';
$zipcode = $_GET['zipcode'] ?? '';
$category = $_GET['category'] ?? '';

// Fetch professionals from database
$professionals = [];
try {
    $sql = "SELECT * FROM professionals WHERE 1=1";
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= " AND (full_name LIKE ? OR bio LIKE ? OR profession LIKE ? OR specialties LIKE ?)";
        $search_term = "%$search_query%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($category)) {
        $sql .= " AND profession = ?";
        $params[] = $category;
    }
    
    $sql .= " LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Search Results</h1>
        
        <?php if (empty($professionals)): ?>
            <div class="alert alert-info">
                No professionals found. Try a different search.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($professionals as $pro): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px;">
                                        <?php 
                                        $names = explode(' ', $pro['full_name']);
                                        echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                        ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title">
                                        <!-- ✅ CRITICAL LINK TO PROFILE -->
                                        <a href="profile.php?id=<?php echo $pro['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($pro['full_name']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($pro['zipcode']); ?>
                                        · 
                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($pro['category']); ?>
                                    </p>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($pro['bio']), 0, 100); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>$<?php echo number_format($pro['hourly_rate'], 2); ?>/hr</strong>
                                        <a href="profile.php?id=<?php echo $pro['id']; ?>" class="btn btn-sm btn-primary">
                                            View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
