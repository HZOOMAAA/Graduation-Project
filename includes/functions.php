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

/**
 * Generate a unique, category-prefixed policy number.
 * Format: CAR-2026-XXXXXXXX  (prefix changes by category)
 * Loops until a non-duplicate is found (DB UNIQUE constraint guarantee).
 */
function generatePolicyNumber($connect, $category_name) {
    $cat = strtolower($category_name ?? '');
    if      (strpos($cat, 'car')      !== false) $prefix = 'CAR';
    elseif  (strpos($cat, 'health')   !== false) $prefix = 'HLT';
    elseif  (strpos($cat, 'medical')  !== false) $prefix = 'HLT';
    elseif  (strpos($cat, 'life')     !== false) $prefix = 'LFE';
    elseif  (strpos($cat, 'property') !== false) $prefix = 'PRP';
    else                                          $prefix = 'INS';

    $year = date('Y');
    do {
        $rand   = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $number = "{$prefix}-{$year}-{$rand}";
        $escaped = mysqli_real_escape_string($connect, $number);
        $check  = mysqli_query($connect, "SELECT policy_id FROM policies WHERE policy_number = '$escaped' LIMIT 1");
    } while ($check && mysqli_num_rows($check) > 0);

    return $number;
}
?>
