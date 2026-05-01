<?php
require_once 'includes/connection.php';
require_once 'includes/auth_check.php'; // ensures user is logged in

header('Content-Type: application/json');

// ── Validate method ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Collect & sanitize inputs ─────────────────────────────────────────────────
$brand     = isset($_POST['brand'])     ? trim(mysqli_real_escape_string($connect, $_POST['brand']))     : '';
$model     = isset($_POST['model'])     ? trim(mysqli_real_escape_string($connect, $_POST['model']))     : '';
$year      = isset($_POST['year'])      ? intval($_POST['year'])                                         : 0;
$price     = isset($_POST['price'])     ? floatval($_POST['price'])                                      : 0;
$condition = isset($_POST['condition']) ? trim(mysqli_real_escape_string($connect, $_POST['condition'])) : '';

// ── Basic validation ──────────────────────────────────────────────────────────
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

// ── Build JSON payload ────────────────────────────────────────────────────────
$applicationData = json_encode([
    'category'  => 'car',
    'brand'     => $brand,
    'model'     => $model,
    'year'      => $year,
    'price'     => $price,
    'condition' => $condition,
    'submitted_at' => date('Y-m-d H:i:s'),
]);

// ── Resolve IDs ───────────────────────────────────────────────────────────────
$customer_id = $_SESSION['user_id'];

// Get car category ID (look it up by name so it works for any DB seed)
$catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%car%' LIMIT 1");
if (!$catResult || mysqli_num_rows($catResult) === 0) {
    // Fallback: try 'Car Insurance' or any other common name pattern
    $catResult = mysqli_query($connect, "SELECT category_id FROM categories ORDER BY category_id ASC LIMIT 1");
}
$catRow     = mysqli_fetch_assoc($catResult);
$category_id = $catRow ? intval($catRow['category_id']) : 1;

// ── Insert into applications ──────────────────────────────────────────────────
$sql = "INSERT INTO applications (customer_id, category_id, status, application_data, created_at)
        VALUES (?, ?, 'pending_selection', ?, NOW())";

$stmt = mysqli_prepare($connect, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($connect)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'iis', $customer_id, $category_id, $applicationData);

if (mysqli_stmt_execute($stmt)) {
    $application_id = mysqli_insert_id($connect);
    echo json_encode([
        'success'        => true,
        'message'        => 'Application submitted successfully!',
        'application_id' => $application_id,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save application: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
?>
