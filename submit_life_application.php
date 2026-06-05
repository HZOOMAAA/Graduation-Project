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

$birth_day   = isset($_POST['birth_day'])   ? intval($_POST['birth_day'])   : 0;
$birth_month = isset($_POST['birth_month']) ? intval($_POST['birth_month']) : 0;
$birth_year  = isset($_POST['birth_year'])  ? intval($_POST['birth_year'])  : 0;

$client_name  = isset($_POST['client_name'])  ? trim(mysqli_real_escape_string($connect, $_POST['client_name']))  : '';
$client_phone = isset($_SESSION['phone'])     ? trim(mysqli_real_escape_string($connect, $_SESSION['phone']))     : '';

$coverage_amount      = isset($_POST['coverage_amount'])      ? floatval($_POST['coverage_amount'])                      : 0;
$policy_term          = isset($_POST['policy_term'])          ? trim(mysqli_real_escape_string($connect, $_POST['policy_term'])) : '';
$beneficiary_name     = isset($_POST['beneficiary_name'])     ? trim(mysqli_real_escape_string($connect, $_POST['beneficiary_name'])) : '';
$beneficiary_relation = isset($_POST['beneficiary_relation']) ? trim(mysqli_real_escape_string($connect, $_POST['beneficiary_relation'])) : '';

$age = 0;
if ($birth_year > 0 && $birth_month > 0 && $birth_day > 0) {
    $birthdate = new DateTime("$birth_year-$birth_month-$birth_day");
    $today     = new DateTime();
    $age       = $today->diff($birthdate)->y;
}

$errors = [];
if ($birth_day < 1 || $birth_day > 31)     $errors[] = 'Valid birth day is required.';
if ($birth_month < 1 || $birth_month > 12) $errors[] = 'Valid birth month is required.';
if ($birth_year < 1920 || $birth_year > date('Y')) $errors[] = 'Valid birth year is required.';
if ($age < 18)                             $errors[] = 'Applicant must be at least 18 years old to apply for life insurance.';
if ($coverage_amount <= 0)                 $errors[] = 'Insurance coverage amount must be positive.';
if (empty($policy_term))                   $errors[] = 'Policy term is required.';
if (empty($beneficiary_name))              $errors[] = 'Beneficiary name is required.';
if (empty($beneficiary_relation))          $errors[] = 'Beneficiary relationship is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$applicationData = [
    'category'             => 'life',
    'client_name'          => $client_name,
    'client_phone'         => $client_phone,
    'birth_day'            => $birth_day,
    'birth_month'          => $birth_month,
    'birth_year'           => $birth_year,
    'age'                  => $age,
    'coverage_amount'      => $coverage_amount,
    'policy_term'          => $policy_term,
    'beneficiary_name'     => $beneficiary_name,
    'beneficiary_relation' => $beneficiary_relation,
    'submitted_at'         => date('Y-m-d H:i:s'),
];

$catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Life%' LIMIT 1");
if (!$catResult || mysqli_num_rows($catResult) === 0) {
    mysqli_query($connect, "INSERT INTO categories (name) VALUES ('Life Insurance')");
    $category_id = mysqli_insert_id($connect);
} else {
    $catRow      = mysqli_fetch_assoc($catResult);
    $category_id = intval($catRow['category_id']);
}

$_SESSION['temp_application_data'] = $applicationData;
$_SESSION['temp_category_id']      = $category_id;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/Graduation-Project/category-life.php';
    echo json_encode([
        'success' => false,
        'login_required' => true,
        'redirect_url' => '/Graduation-Project/auth/login.php'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Life insurance application draft saved successfully!',
]);
?>
