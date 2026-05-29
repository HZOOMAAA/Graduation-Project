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
$property_type      = isset($_POST['property_type'])      ? trim(mysqli_real_escape_string($connect, $_POST['property_type']))      : '';
$construction_year  = isset($_POST['construction_year'])  ? trim(mysqli_real_escape_string($connect, $_POST['construction_year']))  : '';
$property_value     = isset($_POST['property_value'])     ? floatval($_POST['property_value'])                                      : 0;
$contents_value     = isset($_POST['contents_value'])     ? floatval($_POST['contents_value'])                                      : 0;
$coverage_type      = isset($_POST['coverage_type'])      ? trim(mysqli_real_escape_string($connect, $_POST['coverage_type']))      : '';
$property_address   = isset($_POST['property_address'])   ? trim(mysqli_real_escape_string($connect, $_POST['property_address']))   : '';
$property_usage     = isset($_POST['property_usage'])     ? trim(mysqli_real_escape_string($connect, $_POST['property_usage']))     : 'owned';

$client_name  = isset($_SESSION['name'])  ? trim(mysqli_real_escape_string($connect, $_SESSION['name']))  : 'Valued Customer';
$client_phone = isset($_SESSION['phone']) ? trim(mysqli_real_escape_string($connect, $_SESSION['phone'])) : '';

// ── Basic validation ──────────────────────────────────────────────────────────
$errors = [];
if (empty($property_type))     $errors[] = 'Property type is required.';
if (empty($construction_year)) $errors[] = 'Construction year is required.';
if ($property_value <= 0)      $errors[] = 'Property building value must be a positive number.';
if ($contents_value < 0)       $errors[] = 'Contents value cannot be negative.';
if (empty($coverage_type))     $errors[] = 'Coverage type is required.';
if (empty($property_address))   $errors[] = 'Property address is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Build JSON payload ────────────────────────────────────────────────────────
$applicationData = [
    'category'          => 'property',
    'client_name'       => $client_name,
    'client_phone'      => $client_phone,
    'property_type'     => $property_type,
    'construction_year' => $construction_year,
    'property_value'    => $property_value,
    'contents_value'    => $contents_value,
    'coverage_type'     => $coverage_type,
    'property_address'  => $property_address,
    'property_usage'    => $property_usage,
    'submitted_at'      => date('Y-m-d H:i:s'),
];

// ── Resolve category ID ──────────────────────────────────────────────────────
$catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Property%' LIMIT 1");
if (!$catResult || mysqli_num_rows($catResult) === 0) {
    mysqli_query($connect, "INSERT INTO categories (name) VALUES ('Property Insurance')");
    $category_id = mysqli_insert_id($connect);
} else {
    $catRow      = mysqli_fetch_assoc($catResult);
    $category_id = intval($catRow['category_id']);
}

// ── Store draft details inside the PHP Session ────────────────────────────────
$_SESSION['temp_application_data'] = $applicationData;
$_SESSION['temp_category_id']      = $category_id;

echo json_encode([
    'success' => true,
    'message' => 'Property insurance application draft saved successfully!',
]);
?>
