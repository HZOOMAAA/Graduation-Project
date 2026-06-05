<?php
require_once 'includes/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Graduation-Project/category-health.php');
    exit;
}

$birth_day = isset($_POST['birth_day']) ? intval($_POST['birth_day']) : 0;
$birth_month = isset($_POST['birth_month']) ? intval($_POST['birth_month']) : 0;
$birth_year = isset($_POST['birth_year']) ? intval($_POST['birth_year']) : 0;

$client_name = isset($_POST['client_name']) ? trim(mysqli_real_escape_string($connect, $_POST['client_name'])) : '';
$client_phone = isset($_POST['client_phone']) ? trim(mysqli_real_escape_string($connect, $_POST['client_phone'])) : '';

$family_chronic = isset($_POST['family_chronic']) ? trim($_POST['family_chronic']) : 'no';

$has_spouse = false;
$spouse_data = null;
if (!empty($_POST['spouse_day']) && !empty($_POST['spouse_month']) && !empty($_POST['spouse_year'])) {
    $has_spouse = true;
    $spouse_data = [
        'day' => intval($_POST['spouse_day']),
        'month' => intval($_POST['spouse_month']),
        'year' => intval($_POST['spouse_year']),
    ];
}

$children = [];
if (!empty($_POST['child_day']) && is_array($_POST['child_day'])) {
    $days = $_POST['child_day'];
    $months = $_POST['child_month'] ?? [];
    $years = $_POST['child_year'] ?? [];

    for ($i = 0; $i < count($days); $i++) {
        if (!empty($days[$i]) && !empty($months[$i]) && !empty($years[$i])) {
            $children[] = [
                'day' => intval($days[$i]),
                'month' => intval($months[$i]),
                'year' => intval($years[$i]),
            ];
        }
    }
}

$age = 0;
if ($birth_year > 0 && $birth_month > 0 && $birth_day > 0) {
    $birthdate = new DateTime("$birth_year-$birth_month-$birth_day");
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
}

$errors = [];
if ($birth_day < 1 || $birth_day > 31)
    $errors[] = 'Valid birth day is required.';
if ($birth_month < 1 || $birth_month > 12)
    $errors[] = 'Valid birth month is required.';
if ($birth_year < 1920 || $birth_year > date('Y'))
    $errors[] = 'Valid birth year is required.';
if ($age < 18)
    $errors[] = 'Primary member must be at least 18 years old.';

if (!empty($errors)) {
    $_SESSION['health_form_error'] = implode(' ', $errors);
    header('Location: /Graduation-Project/category-health.php');
    exit;
}

$applicationData = [
    'category' => 'health',
    'client_name' => $client_name,
    'client_phone' => $client_phone,
    'birth_day' => $birth_day,
    'birth_month' => $birth_month,
    'birth_year' => $birth_year,
    'age' => $age,
    'family_chronic' => $family_chronic,
    'has_spouse' => $has_spouse,
    'spouse' => $spouse_data,
    'children' => $children,
    'num_children' => count($children),
    'submitted_at' => date('Y-m-d H:i:s'),
];

$catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Medical%' OR name LIKE '%health%' LIMIT 1");
if (!$catResult || mysqli_num_rows($catResult) === 0) {
    $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE category_id = 2 LIMIT 1");
}
$catRow = mysqli_fetch_assoc($catResult);
$category_id = $catRow ? intval($catRow['category_id']) : 2;

$_SESSION['temp_application_data'] = $applicationData;
$_SESSION['temp_category_id'] = $category_id;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/Graduation-Project/category-health.php';
    header('Location: /Graduation-Project/auth/login.php');
    exit;
}

header('Location: /Graduation-Project/plans.php');
exit;
?>