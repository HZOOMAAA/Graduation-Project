<?php
function redirectToRoleDashboard($role) {
    switch ($role) {
        case 'admin':
            header('Location: /Graduation-Project/profile.php');
            break;
        case 'agent':
            header('Location: /Graduation-Project/profile.php');
            break;
        case 'customer':
        default:
            header('Location: /Graduation-Project/profile.php');
            break;
    }
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Graduation-Project/auth/login.php');
        exit;
    }
}
?>
