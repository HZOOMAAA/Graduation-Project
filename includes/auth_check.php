<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Graduation-Project/auth/login.php');
    exit;
}

function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // Redirect unauthorized users to their respective dashboard if they try to access another role's page
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] == 'admin') {
                header('Location: /Graduation-Project/AdminDashboard.php');
            } elseif ($_SESSION['role'] == 'agent') {
                header('Location: /Graduation-Project/AgentDashboard.php');
            } else {
                header('Location: /Graduation-Project/home.php');
            }
        } else {
            header('Location: /Graduation-Project/auth/login.php');
        }
        exit;
    }
}
?>
