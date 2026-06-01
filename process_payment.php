<?php
/**
 * process_payment.php
 * Handles payment confirmation (Visa / Mastercard / Fawry simulation).
 * Generates a unique category-prefixed policy number and marks app as paid.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

require_once 'includes/connection.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$customer_id     = (int)$_SESSION['user_id'];
$app_id          = (int)($_POST['app_id'] ?? 0);
$payment_method  = in_array($_POST['payment_method'] ?? '', ['visa', 'mastercard', 'fawry'])
                   ? $_POST['payment_method'] : '';
$card_last4      = preg_replace('/\D/', '', $_POST['card_last4'] ?? '');
$card_last4      = substr($card_last4, -4);
$fawry_ref       = mysqli_real_escape_string($connect, trim($_POST['fawry_ref'] ?? ''));

if ($app_id <= 0 || empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data.']);
    exit;
}

// ── Verify application: must belong to customer and be awaiting_payment ──
$stmt = mysqli_prepare($connect,
    "SELECT a.application_id, a.final_price, a.customer_id,
            cat.name AS category_name
     FROM applications a
     LEFT JOIN categories cat ON a.category_id = cat.category_id
     WHERE a.application_id = ? AND a.customer_id = ? AND a.status = 'awaiting_payment'"
);
mysqli_stmt_bind_param($stmt, 'ii', $app_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$app) {
    echo json_encode(['success' => false, 'message' => 'Application not found or already processed.']);
    exit;
}

// ── Generate unique policy number ──
$policy_number = generatePolicyNumber($connect, $app['category_name'] ?? 'Insurance');

// ── Build payment_ref string ──
if ($payment_method === 'fawry') {
    $payment_ref = 'FAWRY-' . strtoupper($fawry_ref);
} else {
    $suffix = !empty($card_last4) ? '-' . $card_last4 : '';
    $payment_ref = strtoupper($payment_method) . $suffix . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

// ── Insert policy record ──
$start_date = date('Y-m-d');
$end_date   = date('Y-m-d', strtotime('+1 year'));
$doc_path   = 'uploads/policies/policy_' . $app_id . '.pdf';

$ins = mysqli_prepare($connect,
    "INSERT INTO policies (application_id, policy_number, start_date, end_date, document_path, payment_ref, status)
     VALUES (?, ?, ?, ?, ?, ?, 'active')"
);
mysqli_stmt_bind_param($ins, 'isssss', $app_id, $policy_number, $start_date, $end_date, $doc_path, $payment_ref);
$ok = mysqli_stmt_execute($ins);
mysqli_stmt_close($ins);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Failed to create policy: ' . mysqli_error($connect)]);
    exit;
}

// ── Update application status to paid ──
mysqli_query($connect, "UPDATE applications SET status = 'paid' WHERE application_id = $app_id");

echo json_encode([
    'success'       => true,
    'policy_number' => $policy_number,
    'message'       => 'Payment confirmed! Policy issued successfully.',
]);
