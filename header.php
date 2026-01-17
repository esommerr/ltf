<?php
// includes/header.php
require_once 'functions.php';
// session_start(); REMOVED - called in main pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn the Fix - Hire Pros & Learn Skills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        // Make current user ID available to JavaScript
        window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
        window.currentUserName = <?php echo json_encode($_SESSION['user_name'] ?? 'User'); ?>;
        window.currentUserType = <?php echo json_encode($_SESSION['user_type'] ?? 'user'); ?>;
    </script>
    <?php endif; ?>
    
    <style>
        .navbar-brand img { height: 40px; }
        .nav-link { font-weight: 500; }
        .nav-link.active { color: #BF0A30 !important; font-weight: 600; }
        /* Add dropdown styles */
        .dropdown-menu { min-width: 200px; }
        .dropdown-item:active { background-color: #BF0A30; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="https://assets.zyrosite.com/m5KbQR9jMzu28lGZ/ltf-fliarzvEWo8OqyiI.jpg" 
                     alt="Learn the Fix" 
                     class="logo-image">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="search-results.php">Find Pros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/how-it-works.html">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/categories.html">Categories</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in - show dropdown -->
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                               id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                                     style="width: 36px; height: 36px; font-size: 14px;">
                                    <?php 
                                    $user_name = $_SESSION['user_name'] ?? 'User';
                                    $names = explode(' ', $user_name);
                                    echo strtoupper(
                                        substr($names[0], 0, 1) . 
                                        (isset($names[1]) ? substr($names[1], 0, 1) : '')
                                    );
                                    ?>
                                </div>
                                <span class="me-1"><?php echo htmlspecialchars($user_name); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="user-profile.php">
                                        <i class="bi bi-person-circle me-2"></i>My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="my-bookings.php">
                                        <i class="bi bi-calendar-check me-2"></i>My Bookings
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="messages.php">
                                        <i class="bi bi-chat-dots me-2"></i>Messages
                                        <?php if ($_SESSION['unread_messages'] ?? 0 > 0): ?>
                                            <span class="badge bg-danger rounded-pill float-end">
                                                <?php echo $_SESSION['unread_messages']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="favorites.php">
                                        <i class="bi bi-heart me-2"></i>Favorites
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($_SESSION['user_type'] == 'professional'): ?>
                                    <li>
                                        <a class="dropdown-item" href="professional-dashboard.php">
                                            <i class="bi bi-briefcase me-2"></i>Pro Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="settings.php">
                                        <i class="bi bi-gear me-2"></i>Settings
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="../backend/auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- User is NOT logged in - show sign in buttons -->
                        <a href="login.php" class="btn btn-outline-primary me-2">Sign In</a>
                        <a href="/become-a-pro.html" class="btn btn-primary">Become a Pro</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <main>
