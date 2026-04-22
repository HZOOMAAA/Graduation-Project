<?php
session_start();
require_once __DIR__ . '/../inculdes/connection.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Graduation-Project/auth/login.php');
    exit;
}

function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: /Graduation-Project/index.php');
        exit;
    }
}
?>
