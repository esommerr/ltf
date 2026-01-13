<?php
// /public_html/dashboard/index.php
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /sign-in/');
    exit;
}

// Redirect based on user type
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'professional') {
        header('Location: /pro-dashboard/');
    } else {
        header('Location: /user-dashboard/');
    }
} else {
    // Fallback for old sessions
    header('Location: /user-dashboard/');
}
exit;
?>
