<?php
// includes/functions.php
function url($path = '') {
    $base = '/'; // Adjust if your site is in a subfolder
    return $base . ltrim($path, '/');
}
?>
