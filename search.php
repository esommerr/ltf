<?php
// public_html/search.php - FRONTEND SEARCH PAGE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Professionals - Learn the Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Find a Professional</h1>
        
        <div class="card">
            <div class="card-body">
                <form action="search-results.php" method="GET">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">What do you need help with?</label>
                            <input type="text" class="form-control" name="query" 
                                   placeholder="e.g., plumbing, carpentry, HVAC...">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Location (ZIP)</label>
                            <input type="text" class="form-control" name="zipcode" 
                                   placeholder="e.g., 10001">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search Professionals
                    </button>
                </form>
            </div>
        </div>
        
        <div class="mt-5">
            <h3>Popular Categories</h3>
            <div class="row mt-3">
                <div class="col-md-3 mb-3">
                    <a href="search-results.php?category=plumbing" class="card text-decoration-none">
                        <div class="card-body text-center">
                            <i class="bi bi-tools fs-1 text-primary"></i>
                            <h5 class="mt-2">Plumbing</h5>
                        </div>
                    </a>
                </div>
                <!-- Add more categories -->
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
