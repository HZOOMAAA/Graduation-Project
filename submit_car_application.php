<?php
require_once 'includes/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$brand     = isset($_POST['brand'])     ? trim(mysqli_real_escape_string($connect, $_POST['brand']))     : '';
$model     = isset($_POST['model'])     ? trim(mysqli_real_escape_string($connect, $_POST['model']))     : '';
$year      = isset($_POST['year'])      ? intval($_POST['year'])                                         : 0;
$price     = isset($_POST['price'])     ? floatval($_POST['price'])                                      : 0;
$condition = isset($_POST['condition']) ? trim(mysqli_real_escape_string($connect, $_POST['condition'])) : '';

$errors = [];
if (empty($brand))     $errors[] = 'Car brand is required.';
if (empty($model))     $errors[] = 'Car model is required.';
if ($year < 1900)      $errors[] = 'Valid manufacture year is required.';
if ($price <= 0)       $errors[] = 'Estimated price must be a positive number.';
if (!in_array($condition, ['new', 'used'])) $errors[] = 'Car condition must be new or used.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$applicationData = json_encode([
    'category'  => 'car',
    'brand'     => $brand,
    'model'     => $model,
    'year'      => $year,
    'price'     => $price,
    'condition' => $condition,
    'submitted_at' => date('Y-m-d H:i:s'),
]);

$customer_id = $_SESSION['user_id'] ?? null;

$catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%car%' LIMIT 1");
if (!$catResult || mysqli_num_rows($catResult) === 0) {
    $catResult = mysqli_query($connect, "SELECT category_id FROM categories ORDER BY category_id ASC LIMIT 1");
}
$catRow     = mysqli_fetch_assoc($catResult);
$category_id = $catRow ? intval($catRow['category_id']) : 1;

$_SESSION['temp_application_data'] = [
    'category'  => 'car',
    'brand'     => $brand,
    'model'     => $model,
    'year'      => $year,
    'price'     => $price,
    'condition' => $condition,
    'submitted_at' => date('Y-m-d H:i:s'),
];
$_SESSION['temp_category_id'] = $category_id;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/Graduation-Project/category-car.php';
    echo json_encode([
        'success' => false,
        'login_required' => true,
        'redirect_url' => '/Graduation-Project/auth/login.php'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Car details saved to session draft successfully!',
]);
?>
