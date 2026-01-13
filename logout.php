<?php
// /public_html/backend/auth/logout.php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
if (session_destroy()) {
    // Also clear any remember me cookies if set
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['user_identifier'])) {
        setcookie('user_identifier', '', time() - 3600, '/');
    }
}

// Redirect to sign-in page
header('Location: /sign-in/');
exit();
?>
