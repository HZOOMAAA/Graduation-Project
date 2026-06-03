<?php
/**
 * paymob_webhook.php
 * Secure Paymob Webhook Callback Endpoint.
 * Receives transaction processing notifications from Paymob, validates HMAC signature,
 * updates payment status in the payments table, and updates corresponding policy status.
 */

// Force response content type to JSON
header('Content-Type: application/json');

// Include Paymob merchant configurations, PDO connection, and HMAC/logging helpers
require_once __DIR__ . '/includes/paymob_config.php';
require_once __DIR__ . '/includes/connection_pdo.php';
require_once __DIR__ . '/includes/paymob_helpers.php';

// Verify that the HMAC secret is configured
if (!defined('PAYMOB_HMAC_SECRET') || empty(PAYMOB_HMAC_SECRET)) {
    log_paymob_webhook("Webhook processing failed: PAYMOB_HMAC_SECRET is missing or empty in configuration.");
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "HMAC secret not configured"]);
    exit();
}

// 1. Retrieve the HMAC signature from the request query parameters
$receivedHmac = $_GET['hmac'] ?? '';
if (empty($receivedHmac)) {
    log_paymob_webhook("Webhook rejected: Request missing HMAC query parameter signature.");
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing HMAC signature"]);
    exit();
}

// 2. Retrieve the raw POST request payload body
$rawPayload = file_get_contents('php://input');
if (empty($rawPayload)) {
    log_paymob_webhook("Webhook rejected: Received request with empty body payload.");
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Empty body payload"]);
    exit();
}

// 3. Decode the raw JSON payload
$payload = json_decode($rawPayload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    log_paymob_webhook("Webhook rejected: Received malformed or invalid JSON payload.", ["json_error" => json_last_error_msg()]);
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit();
}

// Log incoming callback details for debugging/audit trails (PAN is redacted in log helper)
log_paymob_webhook("Incoming Paymob webhook callback received.", ["payload" => $payload, "receivedHmac" => $receivedHmac]);

// 4. Validate the Paymob HMAC signature authenticity before executing any business/DB logic
if (!verify_paymob_webhook_hmac($payload, $receivedHmac, PAYMOB_HMAC_SECRET)) {
    log_paymob_webhook("Webhook rejected: HMAC signature verification failed (computed signature mismatch).");
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "HMAC signature mismatch"]);
    exit();
}

// Check that the callback payload contains a valid transaction object block ('obj')
if (!isset($payload['obj']) || !is_array($payload['obj'])) {
    log_paymob_webhook("Webhook skipped: Missing transaction object 'obj' block inside payload.");
    http_response_code(422); // Unprocessable Entity
    echo json_encode(["status" => "error", "message" => "Missing transaction object"]);
    exit();
}

$obj = $payload['obj'];

// 5. Extract essential transaction attributes
$transaction_id = $obj['id'] ?? null;
$order_id       = null;

// Paymob stores order ID as a nested object in webhooks (i.e. ['order']['id'])
if (isset($obj['order'])) {
    $order_id = is_array($obj['order']) ? ($obj['order']['id'] ?? null) : $obj['order'];
}

// Check if transaction success is boolean true or equivalent string/int value
$is_success = isset($obj['success']) && ($obj['success'] === true || $obj['success'] === 'true' || $obj['success'] === 1 || $obj['success'] === '1');

// Extract payment method/brand (Visa, Mastercard, Wallet, etc.)
$payment_method = '';
if (isset($obj['source_data']) && is_array($obj['source_data'])) {
    $payment_method = $obj['source_data']['sub_type'] ?? ($obj['source_data']['type'] ?? '');
}

// Parse payment amount
$amount_cents = $obj['amount_cents'] ?? 0;
$amount       = (float)$amount_cents / 100;

log_paymob_webhook("Webhook HMAC validation passed. Extracted details successfully.", [
    "transaction_id" => $transaction_id,
    "order_id"       => $order_id,
    "is_success"     => $is_success,
    "payment_method" => $payment_method,
    "amount"         => $amount
]);

// If Paymob order ID is missing, we cannot associate it to our db records
if (empty($order_id)) {
    log_paymob_webhook("Webhook processing failed: Order ID is missing from payload.");
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Order ID not found in payload"]);
    exit();
}

try {
    // Start database transaction to ensure atomicity
    $pdo->beginTransaction();

    // 6. Find the corresponding payment record by referencing the Paymob order id
    // (Ensure payments.transaction_ref contains the Paymob order ID during checkout initialization)
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE transaction_ref = :order_id LIMIT 1 FOR UPDATE");
    $stmt->execute([':order_id' => (string)$order_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        // Rollback transaction and return 404
        $pdo->rollBack();
        log_paymob_webhook("Webhook rejected: Corresponding payment record not found in database.", ["order_id" => $order_id]);
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Payment record not found"]);
        exit();
    }

    // 7. Prevent duplicate processing if payment status is already completed
    $current_payment_status = strtoupper($payment['status']);
    if ($current_payment_status === 'COMPLETED' || $current_payment_status === 'FAILED') {
        $pdo->rollBack();
        log_paymob_webhook("Webhook ignored: Payment record is already processed.", [
            "payment_id"     => $payment['payment_id'],
            "current_status" => $current_payment_status
        ]);
        http_response_code(200); // Return 200 OK so Paymob stops retrying callbacks
        echo json_encode(["status" => "success", "message" => "Already processed"]);
        exit();
    }

    // Map payment method brand string to DB payments.method ENUM values:
    // enum('credit_card','paypal','bank_transfer','cash')
    $method_mapped = 'credit_card'; // default fallback for visa, mastercard, card, paymob, etc.
    $payment_method_lower = strtolower($payment_method);
    if (strpos($payment_method_lower, 'paypal') !== false) {
        $method_mapped = 'paypal';
    } elseif (strpos($payment_method_lower, 'bank') !== false || strpos($payment_method_lower, 'transfer') !== false) {
        $method_mapped = 'bank_transfer';
    } elseif (strpos($payment_method_lower, 'cash') !== false) {
        $method_mapped = 'cash';
    }

    // 8. Process state update based on payment success/failure status
    if ($is_success) {
        // Update payments status to 'completed', save transaction_id, and save payment method
        $updatePaymentStmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'completed', 
                transaction_ref = :transaction_id, 
                method = :method 
            WHERE payment_id = :payment_id
        ");
        $updatePaymentStmt->execute([
            ':transaction_id' => (string)$transaction_id,
            ':method'         => $method_mapped,
            ':payment_id'     => $payment['payment_id']
        ]);

        // Update the policy status to 'active'
        $updatePolicyStmt = $pdo->prepare("
            UPDATE policies 
            SET status = 'active' 
            WHERE policy_id = :policy_id
        ");
        $updatePolicyStmt->execute([
            ':policy_id' => $payment['policy_id']
        ]);

        log_paymob_webhook("Webhook successfully updated DB state.", [
            "payment_id"     => $payment['payment_id'],
            "policy_id"      => $payment['policy_id'],
            "new_status"     => "completed",
            "policy_status"  => "active",
            "transaction_id" => $transaction_id
        ]);
    } else {
        // Update payments status to 'failed'
        $updatePaymentStmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'failed' 
            WHERE payment_id = :payment_id
        ");
        $updatePaymentStmt->execute([
            ':payment_id' => $payment['payment_id']
        ]);

        log_paymob_webhook("Webhook payment failure registered. DB status set to failed.", [
            "payment_id" => $payment['payment_id'],
            "new_status" => "failed"
        ]);
    }

    // Commit changes to database
    $pdo->commit();

    // Respond with 200 Success status
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);

} catch (Exception $e) {
    // Rollback changes on database failures
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log exception details securely
    log_paymob_webhook("Webhook failed: Database transaction error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal database error"]);
}
?>
