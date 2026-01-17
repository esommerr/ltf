<?php
// edit-profile.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Form to edit user profile
?>
