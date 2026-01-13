<?php
// /public_html/professional-dashboard/index.php
session_start();
require_once __DIR__ . '/../backend/config/database.php';

// Check if professional is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_type'] !== 'professional') {
    header('Location: /sign-in/');
    exit;
}

// Get professional data from database
try {
    $pdo = getDBConnection();
    
    // Get professional details
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            GROUP_CONCAT(CONCAT(ps.skill_name, ' (', ps.years_experience, ' yrs)') SEPARATOR ', ') as skills_list
        FROM professionals p
        LEFT JOIN professional_skills ps ON p.id = ps.professional_id
        WHERE p.id = ?
        GROUP BY p.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $professional = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professional) {
        // Redirect to complete profile if not found
        header('Location: /professional-registration/?complete=1');
        exit;
    }
    
    // Get professional's initials for avatar
    $initials = 'P';
    if (!empty($professional['full_name'])) {
        $names = explode(' ', $professional['full_name']);
        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
    }
    
    // Get first name for greeting
    $first_name = explode(' ', $professional['full_name'])[0] ?? 'Professional';
    
    // Format dates
    $member_since = date('F Y', strtotime($professional['created_at']));
    
    // Time-based greeting
    $hour = date('G');
    if ($hour < 12) {
        $greeting = "Good morning, {$first_name}.";
    } elseif ($hour < 18) {
        $greeting = "Good afternoon, {$first_name}.";
    } else {
        $greeting = "Good evening, {$first_name}.";
    }
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Fallback data
    $professional = [
        'professional_id' => $_SESSION['user_uid'] ?? 'PRO-000000',
        'full_name' => $_SESSION['professional_name'] ?? 'Professional',
        'email' => $_SESSION['user_email'] ?? 'N/A',
        'hourly_rate' => 0.00,
        'rating' => 0.0,
        'zipcode' => 'N/A',
        'phone' => 'N/A',
        'status' => 'pending'
    ];
    $initials = 'P';
    $first_name = 'Professional';
    $greeting = "Welcome back, Professional.";
    $member_since = date('F Y');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo htmlspecialchars($professional['full_name']); ?> | Professional Dashboard | Learn the Fix</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* =======================
   MODERN COLOR VARIABLES - Matches Learnthefix.com
   ======================= */
:root {
    /* Primary Brand Colors from Homepage */
    --red-primary: #BF0A30;
    --red-hover: #000000;
    --red-subtle: rgba(191, 10, 48, 0.08);
    --red-light: rgba(191, 10, 48, 0.05);
    
    /* Sky Blue from homepage */
    --sky-blue: #7dd3fc;
    --sky-blue-light: rgba(125, 211, 252, 0.15);
    --sky-blue-medium: #7dd3fc;
    
    /* Neutral Text & Backgrounds */
    --text-primary: #0f172a;
    --text-secondary: #475569;
    --text-muted: #64748b;
    --bg-body: #f8fafc;
    --bg-card: #ffffff;
    --bg-soft: #f8fafc;
    --bg-subtle: #f1f5f9;
    
    /* Semantic Colors */
    --success-green: #059669;
    --warning-orange: #d97706;
    --info-blue: #3b82f6;
    
    /* Modern Shadows */
    --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.05);
    --shadow-hover: 0 4px 12px rgba(0, 0, 0, 0.04);
    --shadow-nav: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* =======================
   BASE & GLOBAL RESETS
   ======================= */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg-body);
    color: var(--text-primary);
    line-height: 1.6;
    font-weight: 400;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

h1, h2, h3, h4 {
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

p {
    margin: 0;
    color: var(--text-secondary);
}

a {
    color: inherit;
    text-decoration: none;
    transition: all 0.2s ease;
}

/* =======================
   MODERN HEADER with Sign Out
   ======================= */
.dashboard-header {
    background: var(--bg-card);
    border-bottom: 1px solid var(--bg-subtle);
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 0 32px;
    box-shadow: var(--shadow-nav);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
}

.logo {
    display: flex;
    align-items: center;
    font-weight: 800;
}

.logo-image {
    height: 70px;
    width: auto;
    transition: opacity 0.2s ease;
}

.logo-image:hover {
    opacity: 0.85;
}

.user-nav {
    display: flex;
    align-items: center;
    gap: 28px;
}

.user-nav a {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
    padding: 8px 0;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-nav a:hover,
.user-nav a.active {
    color: var(--red-primary);
}

.user-nav a.active::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--red-primary);
    border-radius: 2px;
}

.notification-badge {
    background: var(--red-primary);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    position: absolute;
    top: -8px;
    right: -12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--red-primary), #9D0928);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-card);
}

.user-avatar:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: var(--shadow-hover);
}

/* User Menu Dropdown */
.user-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 24px;
    background: var(--bg-card);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--bg-subtle);
    min-width: 200px;
    z-index: 1000;
    overflow: hidden;
    margin-top: 8px;
}

.user-menu.active {
    display: block;
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border-bottom: 1px solid var(--bg-subtle);
}

.user-menu-item:last-child {
    border-bottom: none;
}

.user-menu-item:hover {
    background: var(--bg-soft);
    color: var(--text-primary);
}

.user-menu-item i {
    color: var(--red-primary);
    width: 18px;
}

.user-avatar-container {
    position: relative;
    display: flex;
    align-items: center;
}

/* Sign Out button in header */
.sign-out-btn {
    background: transparent;
    border: 1px solid var(--red-primary);
    color: var(--red-primary);
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.sign-out-btn:hover {
    background: var(--red-primary);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(191, 10, 48, 0.2);
}

/* =======================
   SKY BLUE DIVIDERS
   ======================= */
.sky-blue-divider { 
    position: relative; 
}

.sky-blue-divider::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 220px;
    height: 3px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(125, 211, 252, 0.4) 18%,
        var(--sky-blue-medium) 50%,
        rgba(125, 211, 252, 0.4) 82%,
        transparent 100%
    );
    border-radius: 3px;
    opacity: 0.85;
}

.sky-blue-divider-thick::before {
    width: 300px;
    height: 4px;
    opacity: 0.95;
}

.sky-blue-divider-dots::before {
    height: 2px;
    width: 160px;
    background-image: radial-gradient(var(--sky-blue-medium) 1.5px, transparent 1.5px);
    background-size: 10px 100%;
    background-repeat: repeat-x;
    opacity: 0.75;
}

.sky-blue-divider-full::before {
    width: 85%;
    max-width: 520px;
}

/* Sky blue accent line for cards */
.sky-blue-accent {
    position: relative;
    overflow: hidden;
}

.sky-blue-accent::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, 
        transparent 0%, 
        var(--sky-blue) 20%, 
        var(--sky-blue) 80%, 
        transparent 100%);
    opacity: 0.7;
}
/* =======================
   ANIMATION FOR SLIDE-IN BORDER
   ======================= */
@keyframes slideIn {
    from {
        width: 0;
        left: 50%;
    }
    to {
        width: 100%;
        left: 0;
    }
}

/* =======================
   MAIN LAYOUT
   ======================= */
.dashboard-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 24px 64px;
}

/* =======================
   WELCOME CARD
   ======================= */
.welcome-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 36px;
    margin-bottom: 32px;
    box-shadow: var(--shadow-card);
    position: relative;
    overflow: hidden;
    border: 1px solid var(--bg-subtle);
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--red-primary), #9D0928);
    opacity: 0.8;
}

.welcome-content h2 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 8px;
    color: var(--text-primary);
    letter-spacing: -0.025em;
}

.welcome-subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
    margin-bottom: 28px;
    font-weight: 400;
}

.welcome-meta {
    display: flex;
    gap: 24px;
    margin: 20px 0 32px;
    flex-wrap: wrap;
}

.welcome-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-secondary);
    font-size: 0.9rem;
    padding: 8px 14px;
    background: var(--bg-soft);
    border-radius: 8px;
    border: 1px solid var(--bg-subtle);
}

.meta-item i {
    color: var(--red-primary);
    width: 16px;
    font-size: 0.9rem;
}

/* =======================
   QUICK ACTIONS AT TOP
   ======================= */
.quick-actions-top {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid var(--bg-subtle);
}

.quick-action-btn-top {
    background: var(--bg-card);
    border: 1px solid var(--bg-subtle);
    border-radius: 12px;
    padding: 22px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    text-align: left;
}

.quick-action-btn-top:hover {
    border-color: var(--red-primary);
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.quick-action-btn-top i {
    color: var(--red-primary);
    font-size: 1.4rem;
    margin-top: 2px;
}

.quick-action-btn-top div:first-child {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.95rem;
    margin-bottom: 4px;
}

.quick-action-btn-top div:last-child {
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* =======================
   SECTION STYLES
   ======================= */
.open-jobs-section,
.combined-section,
.saved-pros-section {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 36px;
    margin-bottom: 32px;
    box-shadow: var(--shadow-card);
    border: 1px solid var(--bg-subtle);
    position: relative;
}

.open-jobs-section::before,
.combined-section::before,
.saved-pros-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 180px;
    height: 3px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(125, 211, 252, 0.4) 20%,
        var(--sky-blue) 50%,
        rgba(125, 211, 252, 0.4) 80%,
        transparent 100%
    );
    border-radius: 3px;
    opacity: 0.8;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--bg-subtle);
}

.section-title {
    font-size: 1.4rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-primary);
}

.section-title i {
    color: var(--red-primary);
    font-size: 1.3rem;
}

.section-count {
    background: white;
    color: var(--text-primary);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
}

/* =======================
   JOB CARD MODERN
   ======================= */
.open-jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
}

.job-card-modern {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 26px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--bg-subtle);
    position: relative;
    overflow: hidden;
}

.job-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.job-card-modern:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--sky-blue), #38bdf8); /* SKY BLUE */
    border-radius: 0 0 14px 14px;
    animation: slideIn 0.3s ease-out;
}

.job-card-modern.sky-blue-accent::before {
    background: linear-gradient(to bottom, 
        transparent 0%, 
        var(--sky-blue) 15%, 
        var(--sky-blue) 85%, 
        transparent 100%);
    opacity: 0.6;
}

.job-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
}

.job-card-title {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.15rem;
    margin-bottom: 6px;
}

.job-service-type {
    color: var(--red-primary);
    font-size: 0.95rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.job-service-type i {
    font-size: 0.9rem;
}

.job-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: white;
    color: var(--text-primary);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.status-new {
    background: white;
    color: var(--text-primary); /* Changed from info-blue to black */
    border: none;
    position: relative;
    transition: all 0.2s ease;
}

.status-completed {
    background: white;
    color: var(--text-primary);
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    box-shadow: none;
    display: flex;
    align-items: center;
    justify-content: center;
    height: fit-content;
    transition: all 0.2s ease;
}

.status-completed:hover {
    background: var(--bg-subtle);
    color: var(--text-primary);
    box-shadow: none;
    transform: translateY(-1px);
    border: none;
}

/* =======================
   TODAY'S SCHEDULE - Green underline on time hover
   ======================= */
.job-card-modern:hover .status-pending {
    position: relative;
}

/* TODAY'S SCHEDULE - Black underline on time hover */
.job-card-modern:hover .status-pending::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 2px;
    background: #000000; /* Changed from green to black */
    border-radius: 1px;
    opacity: 0.7;
}

/* =======================
   BOOKING REQUESTS - Black New text with red underline on hover
   ======================= */
.job-card-modern:hover .status-new::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 2px;
    background: var(--red-primary);
    border-radius: 1px;
    opacity: 0.7;
}

.job-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.job-detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.job-detail-item i {
    color: var(--red-primary);
    font-size: 1rem;
    width: 18px;
}

.job-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 20px;
    border-top: 1px solid var(--bg-subtle);
    margin-top: 16px;
}

/* =======================
   MODERN BUTTONS
   ======================= */
.btn {
    background: var(--red-primary);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: inherit;
    letter-spacing: 0.01em;
}

.btn:hover {
    background: var(--red-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(191, 10, 48, 0.25);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--red-primary);
    color: var(--red-primary);
}

.btn-outline:hover {
    background: var(--red-primary);
    color: white;
}

.btn-success {
    padding: 10px 24px;
    background: linear-gradient(135deg, var(--success-green), #10b981);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-success:hover {
    background: linear-gradient(135deg, #047857, #059669);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
}

.btn-warning {
    background: var(--warning-orange);
    box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);
}

.btn-warning:hover {
    background: #b45309;
    box-shadow: 0 6px 12px rgba(180, 83, 9, 0.25);
}

.btn-small {
    padding: 8px 16px;
    font-size: 0.85rem;
    border-radius: 8px;
}

/* =======================
   STATS CARDS
   ======================= */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.stat-card-modern {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--bg-subtle);
    position: relative;
    overflow: hidden;
}

.stat-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
    border-color: var(--bg-subtle);
}

.stat-card-modern.sky-blue-accent::before {
    background: linear-gradient(to bottom, 
        transparent 0%, 
        var(--sky-blue) 25%, 
        var(--sky-blue) 75%, 
        transparent 100%);
    opacity: 0.5;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--red-primary);
    margin-bottom: 6px;
    line-height: 1;
}

.stat-label {
    font-size: 0.95rem;
    color: var(--text-secondary);
    font-weight: 500;
}

/* =======================
   PRO CARD MODERN
   ======================= */
.pro-card-modern {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--bg-subtle);
    position: relative;
    overflow: hidden;
}

.pro-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
    border-color: var(--bg-subtle);
}

.pro-card-modern.sky-blue-accent::before {
    background: linear-gradient(to bottom, 
        transparent 0%, 
        var(--sky-blue) 20%, 
        var(--sky-blue) 80%, 
        transparent 100%);
    opacity: 0.6;
}

.pro-avatar-modern {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--red-primary), #9D0928);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: var(--shadow-card);
}

.pro-info {
    flex: 1;
}

.pro-name {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.1rem;
    margin-bottom: 6px;
}

.pro-specialty {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 10px;
    line-height: 1.4;
}

.pro-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.pro-rating i {
    color: #fbbf24;
}

/* =======================
   WORK HISTORY SECTION
   ======================= */
.work-history-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
}

.work-history-card {
    background: #fcfcfc; /* Slightly darker than pure white */
    border-radius: 14px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--bg-subtle);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.work-history-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.work-history-card:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--sky-blue), #38bdf8); /* SKY BLUE */
    border-radius: 0 0 14px 14px;
    animation: slideIn 0.3s ease-out;
}

.work-history-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--bg-subtle);
}

.work-history-client-name {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.1rem;
    margin-bottom: 6px;
}

.work-history-job-type {
    color: var(--red-primary);
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.work-history-job-type i {
    font-size: 0.9rem;
}

.work-history-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(15, 23, 42, 0.08); /* Dark background for black theme */
    color: var(--text-primary); /* Black text */
}

.work-history-status i {
    color: var(--text-primary); /* Black icon */
}

.work-history-details {
    margin-bottom: 20px;
}

.work-history-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.work-history-meta-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 12px 8px;
    background: var(--bg-soft);
    border-radius: 8px;
    gap: 6px;
}

.work-history-meta-item i {
    color: var(--red-primary);
    font-size: 1rem;
}

.work-history-meta-item span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.work-history-rating {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.rating-stars {
    display: flex;
    gap: 2px;
    color: #fbbf24;
    font-size: 0.9rem;
}

.rating-stars i {
    color: #fbbf24;
}

.rating-stars i:last-child {
    color: rgba(251, 191, 36, 0.3);
}

.rating-text {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.work-history-footer {
    padding-top: 16px;
    border-top: 1px solid var(--bg-subtle);
    text-align: center;
}

.view-details-link {
    color: var(--red-primary);
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.work-history-card:hover .view-details-link {
    color: var(--red-hover);
    transform: translateX(3px);
}

.view-details-link i {
    font-size: 0.8rem;
}

/* =======================
   PROFILE DETAILS
   ======================= */
.profile-details {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--bg-subtle);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.profile-label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.profile-value {
    color: var(--text-secondary);
    text-align: right;
    font-size: 0.9rem;
}

/* =======================
   EARNINGS & HISTORY
   ======================= */
.earnings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.earning-stat {
    text-align: center;
    padding: 16px;
    background: var(--bg-soft);
    border-radius: 12px;
}

.earning-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--red-primary);
    margin-bottom: 4px;
}

.earning-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.history-item-modern {
    display: flex;
    padding: 24px 0;
    position: relative;
    border-bottom: 1px solid var(--bg-subtle);
}

.history-item-modern:last-child {
    border-bottom: none;
}

.history-date {
    min-width: 100px;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.9rem;
    margin-right: 24px;
}

.history-content {
    flex: 1;
}

.history-job-title {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.05rem;
    margin-bottom: 8px;
}

.history-meta {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.history-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.history-meta i {
    color: var(--red-primary);
    font-size: 0.85rem;
}

/* =======================
   MODAL STYLES
   ======================= */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 36px;
    max-width: 480px;
    width: 100%;
    box-shadow: var(--shadow-hover);
    animation: modalAppear 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--red-primary), #9D0928);
    opacity: 0.8;
}

@keyframes modalAppear {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-content h3 {
    font-size: 1.4rem;
    margin-bottom: 12px;
    color: var(--text-primary);
}

.modal-actions {
    display: flex;
    gap: 16px;
    margin-top: 28px;
}

/* =======================
   EMPTY STATES
   ======================= */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3.5rem;
    color: rgba(156, 163, 175, 0.3);
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 1.2rem;
    margin-bottom: 12px;
    color: var(--text-secondary);
}

/* Responsive adjustments for work history */
@media (max-width: 900px) {
    .work-history-grid {
        grid-template-columns: 1fr;
    }
    
    .work-history-meta {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 576px) {
    .work-history-meta {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .work-history-meta-item {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        padding: 10px 12px;
    }
}

/* =======================
   RESPONSIVE DESIGN
   ======================= */
@media (max-width: 1200px) {
    .dashboard-main,
    .nav-container {
        padding-left: 32px;
        padding-right: 32px;
    }
}

@media (max-width: 1100px) {
    .quick-actions-top {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .open-jobs-grid {
        grid-template-columns: 1fr;
    }
    
    .welcome-meta {
        flex-direction: column;
        gap: 12px;
    }
    
    .meta-item {
        width: 100%;
        justify-content: flex-start;
    }
    
    .earnings-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-main {
        padding: 24px 20px 48px;
    }
    
    .dashboard-header {
        padding: 0 20px;
    }
    
    .welcome-card,
    .open-jobs-section,
    .combined-section,
    .saved-pros-section {
        padding: 28px 20px;
    }
    
    .job-details-grid {
        grid-template-columns: 1fr;
    }
    
    .history-meta {
        flex-direction: column;
        gap: 6px;
    }
    
    .user-nav {
        gap: 16px;
    }
    
    .quick-actions-top {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .stats-overview {
        grid-template-columns: 1fr;
    }
    
    .earnings-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .user-nav a span:not(.notification-badge) {
        display: none;
    }
    
    .user-nav a i {
        font-size: 1.2rem;
    }
    
    .sign-out-btn span {
        display: none;
    }
    
    .sign-out-btn i {
        margin: 0;
    }
    
    .welcome-content h2 {
        font-size: 1.6rem;
    }
    
    .welcome-subtitle {
        font-size: 1rem;
    }
    
    .job-card-modern,
    .pro-card-modern,
    .stat-card-modern {
        padding: 20px;
    }
    
    .modal-content {
        padding: 28px 20px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .profile-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .profile-value {
        text-align: left;
    }
}
</style>
</head>

<body>
    <!-- MODERN HEADER with Sign Out button -->
    <header class="dashboard-header">
        <div class="nav-container">
            <div class="logo">
                <img
                    src="https://assets.zyrosite.com/m5KbQR9jMzu28lGZ/ltf-fliarzvEWo8OqyiI.jpg"
                    alt="Learn the Fix"
                    class="logo-image"
                >
            </div>
            
            <nav class="user-nav">
                <a href="/pro-messages.html" style="position: relative;">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="notification-badge">3</span>
                </a>
                <div class="user-avatar-container">
                    <div class="user-avatar" id="userAvatar">
                        <?php echo $initials; ?>
                    </div>
                    <div class="user-menu" id="userMenu">
                        <a href="/pro-profile-edit.html" class="user-menu-item">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                        <a href="/pro-settings.html" class="user-menu-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="/pro-payments.html" class="user-menu-item">
                            <i class="fas fa-credit-card"></i>
                            <span>Payment Methods</span>
                        </a>
                        <div class="user-menu-item" onclick="showLogoutModal()">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sign Out</span>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- MAIN DASHBOARD CONTENT -->
<main class="dashboard-main">
    <!-- WELCOME CARD WITH QUICK ACTIONS -->
    <div class="welcome-card sky-blue-divider">
        <div class="welcome-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px;">
                <div>
                    <h2 id="welcomeTitle"><?php echo $greeting; ?></h2>
                    <div class="welcome-meta" style="margin-top: 12px; margin-bottom: 24px;">
                        <?php if ($professional['rating'] > 0): ?>
                        <div class="meta-item">
                            <i class="fas fa-star"></i>
                            <span>Rating: <?php echo number_format($professional['rating'], 1); ?>/5.0</span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Rate: $<?php echo number_format($professional['hourly_rate'], 2); ?>/hr</span>
                        </div>
                    </div>
                    <div style="margin-top: 8px;">
                        <a href="/pro-update-availability.html" class="btn">
                            <i class="fas fa-toggle-on"></i>
                            Update Availability
                        </a>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                    <div class="meta-item" style="margin: 0;">
                        <i class="fas fa-id-card" style="color: var(--sky-blue);"></i>
                        <span>ID: <?php echo htmlspecialchars($professional['professional_id']); ?></span>
                    </div>
                    <div class="meta-item" style="margin: 0;">
                        <i class="fas fa-calendar-check" style="color: var(--sky-blue);"></i>
                        <span>Professional Since <?php echo $member_since; ?></span>
                    </div>
                    <?php if (!empty($professional['zipcode'])): ?>
                    <div class="meta-item" style="margin: 0;">
                        <i class="fas fa-map-marker-alt" style="color: var(--sky-blue);"></i>
                        <span id="proLocation"><?php echo htmlspecialchars($professional['zipcode']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- QUICK ACTIONS AT TOP -->
            <div class="quick-actions-top">
                <a href="/pro-schedule.html" class="quick-action-btn-top">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <div>View Schedule</div>
                        <div>0 appointments today</div>
                    </div>
                </a>
                
                <a href="/pro-messages.html" class="quick-action-btn-top">
                    <i class="fas fa-comments"></i>
                    <div>
                        <div>View Messages</div>
                        <div>0 unread messages</div>
                    </div>
                </a>
                
                <a href="/pro-jobs.html" class="quick-action-btn-top">
                    <i class="fas fa-tasks"></i>
                    <div>
                        <div>Active Jobs</div>
                        <div>0 in progress</div>
                    </div>
                </a>
                
                <!-- Profile & Settings button moved here from header -->
                <a href="/pro-profile-edit.html" class="quick-action-btn-top">
                    <i class="fas fa-user-cog"></i>
                    <div>
                        <div>Profile & Settings</div>
                        <div>Update your info</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

        <!-- TODAY'S SCHEDULE SECTION (MOVED ABOVE BOOK REQUESTS) -->
        <section class="open-jobs-section sky-blue-divider-thick">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar-day"></i>
                    Today's Schedule
                </h3>
                <span class="section-count" id="scheduleCount">0 Appointments</span>
            </div>
            
            <div class="open-jobs-grid" id="todaySchedule">
                <div class="empty-state">
                    <i class="fas fa-calendar"></i>
                    <h3>No appointments scheduled</h3>
                    <p>Your schedule is clear for today.</p>
                </div>
            </div>
        </section>

        <!-- BOOKING REQUESTS SECTION -->
        <section class="open-jobs-section sky-blue-divider-thick">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-bell"></i>
                    Booking Requests
                </h3>
                <span class="section-count" id="bookingCount">0 New Requests</span>
            </div>
            
            <div class="open-jobs-grid" id="bookingRequests">
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No booking requests</h3>
                    <p>You don't have any pending booking requests.</p>
                </div>
            </div>
        </section>

        <!-- WORK HISTORY SECTION -->
        <section class="saved-pros-section sky-blue-divider-full">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Work History
                </h3>
                <span class="section-count" id="workHistoryCount">Recent Jobs</span>
            </div>
            
            <div class="work-history-grid" id="workHistory">
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>No work history yet</h3>
                    <p>Complete your first job to see it here.</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 32px;">
                <button class="btn btn-outline" onclick="viewAllWorkHistory()">
                    <i class="fas fa-history"></i>
                    View Complete Work History
                </button>
            </div>
        </section>

        <!-- PROFILE DETAILS SECTION (MOVED UNDER RECENT JOBS) -->
        <section class="combined-section sky-blue-divider-dots">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-user-circle"></i>
                    Profile Details
                </h3>
            </div>
            
            <div class="job-card-modern sky-blue-accent">
                <ul class="profile-details">
                    <li class="profile-item">
                        <span class="profile-label">Professional ID:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['professional_id']); ?></span>
                    </li>
                    <li class="profile-item">
                        <span class="profile-label">Full Name:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['full_name']); ?></span>
                    </li>
                    <li class="profile-item">
                        <span class="profile-label">Email:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['email']); ?></span>
                    </li>
                    <li class="profile-item">
                        <span class="profile-label">Phone:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['phone']); ?></span>
                    </li>
                    <li class="profile-item">
                        <span class="profile-label">Location:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['zipcode']); ?></span>
                    </li>
                    <li class="profile-item">
                        <span class="profile-label">Hourly Rate:</span>
                        <span class="profile-value">$<?php echo number_format($professional['hourly_rate'], 2); ?>/hr</span>
                    </li>
                    <?php if (!empty($professional['skills_list'])): ?>
                    <li class="profile-item">
                        <span class="profile-label">Skills:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($professional['skills_list']); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="profile-item">
                        <span class="profile-label">Account Status:</span>
                        <span class="profile-value">
                            <span style="color: <?php echo $professional['status'] == 'approved' ? '#059669' : '#d97706'; ?>; font-weight: 600;">
                                <?php echo ucfirst($professional['status']); ?>
                            </span>
                        </span>
                    </li>
                </ul>
                
                <div style="margin-top: 24px; text-align: center;">
                    <a href="/pro-profile-edit.html" class="btn btn-outline btn-small">
                        <i class="fas fa-edit"></i>
                        Edit Professional Profile
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- LOGOUT MODAL -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-content">
            <h3>Sign Out</h3>
            <p style="margin-bottom: 24px; color: var(--text-secondary);">
                Are you sure you want to sign out of your professional account?
            </p>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="hideLogoutModal()">
                    Cancel
                </button>
                <button class="btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Sign Out
                </button>
            </div>
        </div>
    </div>

<script>
// DOM Elements
const logoutModal = document.getElementById('logoutModal');

// Time-based greeting
function updateGreeting() {
    const hour = new Date().getHours();
    const welcomeTitle = document.getElementById('welcomeTitle');
    
    if (hour < 12) {
        welcomeTitle.textContent = 'Good morning, <?php echo addslashes($first_name); ?>.';
    } else if (hour < 18) {
        welcomeTitle.textContent = 'Good afternoon, <?php echo addslashes($first_name); ?>.';
    } else {
        welcomeTitle.textContent = 'Good evening, <?php echo addslashes($first_name); ?>.';
    }
}

// Modal functions
function showLogoutModal() {
    logoutModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideLogoutModal() {
    logoutModal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function logout() {
    window.location.href = '/sign-out.php';
}

// Close modal when clicking outside
logoutModal.addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && logoutModal.style.display === 'flex') {
        hideLogoutModal();
    }
});

// Professional action functions
function acceptRequest(requestId) {
    if (confirm('Accept this booking request?')) {
        const requestItem = document.querySelector(`[onclick*="${requestId}"]`).closest('.job-card-modern');
        if (requestItem) {
            // Animate removal
            requestItem.style.opacity = '0';
            requestItem.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                requestItem.remove();
                
                // Update counts
                updateRequestCounts();
                
                // Show success message
                alert('Booking request accepted! Added to your schedule.');
            }, 300);
        }
    }
}

function suggestTime(requestId) {
    const newTime = prompt('Suggest a new time (e.g., "Tomorrow at 2:00 PM"):');
    if (newTime) {
        alert(`Time suggestion sent: ${newTime}`);
    }
}

function declineRequest(requestId) {
    if (confirm('Decline this booking request?')) {
        const requestItem = document.querySelector(`[onclick*="${requestId}"]`).closest('.job-card-modern');
        if (requestItem) {
            requestItem.remove();
            updateRequestCounts();
            alert('Booking request declined.');
        }
    }
}

function updateRequestCounts() {
    const requests = document.querySelectorAll('#bookingRequests .job-card-modern');
    const countElement = document.getElementById('bookingCount');
    countElement.textContent = requests.length + ' New Requests';
}

function startJob(appointmentId) {
    alert(`Starting job ${appointmentId}\n\nThis would navigate to job workflow.`);
    // window.location.href = `/pro-job-workflow.html?id=${appointmentId}`;
}

function callClient(phoneNumber) {
    alert(`Calling client: ${phoneNumber}\n\nIn a real app, this would initiate a phone call.`);
    // window.open(`tel:${phoneNumber}`);
}

function editProfile() {
    window.location.href = '/pro-profile-edit.html';
}

// Work History functions
function viewJobDetails(jobId) {
    // In a real app, this would navigate to job details page
    alert(`Viewing details for job ${jobId}\n\nThis would navigate to a detailed job summary page.`);
    // window.location.href = `/pro-job-details.html?id=${jobId}`;
}

function viewAllWorkHistory() {
    // In a real app, this would show all work history
    alert('Viewing complete work history\n\nThis would navigate to a full work history page.');
    // window.location.href = '/pro-work-history.html';
}

// User menu functionality
function showUserMenu() {
    const userMenu = document.getElementById('userMenu');
    userMenu.classList.toggle('active');
}

// Close user menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    const userAvatar = document.getElementById('userAvatar');
    const userAvatarContainer = document.querySelector('.user-avatar-container');
    
    if (userMenu && userMenu.classList.contains('active') && 
        !userAvatarContainer.contains(e.target)) {
        userMenu.classList.remove('active');
    }
});

// Close user menu with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const userMenu = document.getElementById('userMenu');
        if (userMenu && userMenu.classList.contains('active')) {
            userMenu.classList.remove('active');
        }
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateGreeting();
    
    // Add subtle animations to cards when page loads
    setTimeout(() => {
        const cards = document.querySelectorAll('.job-card-modern, .pro-card-modern, .stat-card-modern, .work-history-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease';
                card.style.opacity = '1';
            }, index * 100);
        });
    }, 300);
});

// Add CSS animation for pulse effect
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
