<?php
/**
 * paymob_initiate.php
 * Initiator script for Paymob Visa/Mastercard payments.
 * Connects with Paymob API servers (Live Mode) or routes to simulated portal (Simulation Mode).
 */
session_start();

// Guard: User must be logged in as customer
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';
require_once 'includes/paymob_config.php';

$customer_id = (int)$_SESSION['user_id'];
$app_id      = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$provider    = (isset($_GET['provider']) && $_GET['provider'] === 'mastercard') ? 'mastercard' : 'visa';

if ($app_id <= 0) {
    header("Location: profile.php");
    exit();
}

// ── 1. Fetch Application & Customer Details ──
$stmt = mysqli_prepare($connect,
    "SELECT a.application_id, a.final_price, a.status,
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
            p.name AS plan_name, cat.name AS category_name, p.base_price AS base_price
     FROM applications a
     LEFT JOIN users u           ON a.customer_id = u.user_id
     LEFT JOIN insurance_plans p ON a.plan_id     = p.plan_id
     LEFT JOIN categories cat    ON a.category_id = cat.category_id
     WHERE a.application_id = ? AND a.customer_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $app_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$app) {
    header("Location: profile.php");
    exit();
}

// Security: Only allow paying if the application status is 'awaiting_payment'
if ($app['status'] !== 'awaiting_payment') {
    header("Location: profile.php");
    exit();
}

// ✅ Use final_price (the actual price after any adjustments), fallback to base_price
$amount       = (float)($app['final_price'] ?: $app['base_price']);
$amount_cents = (int)($amount * 100); // e.g. 1500 EGP = 150000 cents

// Parse customer first and last name
$name_parts = explode(' ', trim($app['customer_name'] ?? 'Coverly Customer'));
$first_name = htmlspecialchars($name_parts[0]);
$last_name  = htmlspecialchars(isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : 'Customer');
$email      = htmlspecialchars(!empty($app['customer_email']) ? $app['customer_email'] : 'customer@coverly.com');
$phone      = htmlspecialchars(!empty($app['customer_phone']) ? $app['customer_phone'] : '+201000000000');

// ── 2. Store app_id & provider in session so callback can read them ──
// (Paymob does NOT pass these back in its redirect GET params)
$_SESSION['app_id']   = $app_id;
$_SESSION['provider'] = $provider;

// ── 3. Run Flow based on Mode ──
if (PAYMOB_MODE === 'live') {

    if (empty(PAYMOB_API_KEY) || empty(PAYMOB_INTEGRATION_ID) || empty(PAYMOB_IFRAME_ID)) {
        displayError("Paymob Credentials Missing", "Please set your API Key, Integration ID, and Iframe ID in <code>includes/paymob_config.php</code> or change <code>PAYMOB_MODE</code> to <code>'simulation'</code>.");
        exit();
    }

    try {

        // ── STEP 1: Get Auth Token ────────────────────────────────────────
        $auth_url  = "https://accept.paymob.com/api/auth/tokens";
        $auth_data = json_encode(["api_key" => trim(PAYMOB_API_KEY)]);

        $ch = curl_init($auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        $auth_res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("CURL Error (Step 1): " . curl_error($ch));
        }
        curl_close($ch);

        $auth_json  = json_decode($auth_res, true);
        $auth_token = $auth_json['token'] ?? null;

        if (!$auth_token) {
            throw new Exception("Authentication failed. Paymob response: " . ($auth_res ?? 'No response'));
        }

        // ── STEP 2: Register Order ────────────────────────────────────────
        $order_url  = "https://accept.paymob.com/api/ecommerce/orders";
        $order_data = json_encode([
            "auth_token"      => $auth_token,
            "delivery_needed" => false,
            "amount_cents"    => $amount_cents,
            "currency"        => "EGP",
            "items"           => []
        ]);

        $ch = curl_init($order_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $order_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $order_res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("CURL Error (Step 2): " . curl_error($ch));
        }
        curl_close($ch);

        $order_json = json_decode($order_res, true);
        error_log("Paymob Order Response: " . $order_res);

        $order_id = $order_json['id'] ?? null;

        if (!$order_id) {
            $paymob_error = $order_json['message'] ?? $order_json['detail'] ?? $order_res;
            throw new Exception("Order registration failed with Paymob. Error: " . $paymob_error);
        }

        // ── STEP 2.5: Insert Pending Policy & Payment Records ─────────────
        require_once 'includes/functions.php';
        
        $check_pol = mysqli_query($connect, "SELECT policy_id FROM policies WHERE application_id = $app_id LIMIT 1");
        if ($check_pol && mysqli_num_rows($check_pol) > 0) {
            $pol_row = mysqli_fetch_assoc($check_pol);
            $policy_id = $pol_row['policy_id'];
            mysqli_query($connect, "UPDATE policies SET status = 'cancelled', payment_ref = NULL WHERE policy_id = $policy_id");
        } else {
            $policy_number = generatePolicyNumber($connect, $app['category_name'] ?? 'Insurance');
            $start_date = date('Y-m-d');
            $end_date   = date('Y-m-d', strtotime('+1 year'));
            $doc_path   = 'uploads/policies/policy_' . $app_id . '.pdf';

            $ins_pol = mysqli_prepare($connect,
                "INSERT INTO policies (application_id, policy_number, start_date, end_date, document_path, status)
                 VALUES (?, ?, ?, ?, ?, 'cancelled')"
            );
            mysqli_stmt_bind_param($ins_pol, 'issss', $app_id, $policy_number, $start_date, $end_date, $doc_path);
            mysqli_stmt_execute($ins_pol);
            $policy_id = mysqli_insert_id($connect);
            mysqli_stmt_close($ins_pol);
        }

        $check_pay = mysqli_query($connect, "SELECT payment_id FROM payments WHERE policy_id = $policy_id LIMIT 1");
        if ($check_pay && mysqli_num_rows($check_pay) > 0) {
            $pay_row = mysqli_fetch_assoc($check_pay);
            $payment_id = $pay_row['payment_id'];
            mysqli_query($connect, "UPDATE payments SET amount = $amount, status = 'pending', transaction_ref = '$order_id' WHERE payment_id = $payment_id");
        } else {
            $ins_pay = mysqli_prepare($connect,
                "INSERT INTO payments (policy_id, amount, method, status, transaction_ref)
                 VALUES (?, ?, 'credit_card', 'pending', ?)"
            );
            mysqli_stmt_bind_param($ins_pay, 'ids', $policy_id, $amount, $order_id);
            mysqli_stmt_execute($ins_pay);
            mysqli_stmt_close($ins_pay);
        }

        // ── STEP 3: Request Payment Key ───────────────────────────────────
        $key_url  = "https://accept.paymob.com/api/acceptance/payment_keys";
        $key_data = json_encode([
            "auth_token"           => $auth_token,
            "amount_cents"         => $amount_cents,
            "expiration"           => 3600,
            "order_id"             => (string)$order_id,
            "billing_data"         => [
                "apartment"       => "NA",
                "email"           => $email,
                "floor"           => "NA",
                "first_name"      => $first_name,
                "street"          => "NA",
                "building"        => "NA",
                "phone_number"    => $phone,
                "shipping_method" => "PKG",
                "postal_code"     => "NA",
                "city"            => "Cairo",
                "country"         => "EG",
                "last_name"       => $last_name,
                "state"           => "NA"
            ],
            "currency"             => "EGP",
            "integration_id"       => (int)PAYMOB_INTEGRATION_ID,
            "lock_order_when_paid" => "true",
            // ✅ Paymob will redirect user here after payment (success or fail)
            "redirect_url"         => "http://localhost/Graduation-Project/paymob_callback.php"
        ]);

        $ch = curl_init($key_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $key_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $key_res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("CURL Error (Step 3): " . curl_error($ch));
        }
        curl_close($ch);

        $key_json    = json_decode($key_res, true);
        $payment_key = $key_json['token'] ?? null;

        if (!$payment_key) {
            throw new Exception("Payment Key generation failed. Verify if your Card Integration ID is active and matching EGP currency.");
        }

        // ── Redirect to Paymob secure checkout iframe ─────────────────────
        $checkout_url = "https://accept.paymob.com/api/acceptance/iframes/" . PAYMOB_IFRAME_ID . "?payment_token=" . $payment_key;
        header("Location: " . $checkout_url);
        exit();

    } catch (Exception $e) {
        displayError(
            "Paymob API Handshake Failed",
            $e->getMessage() . "<br><b>Response Data:</b> " . htmlspecialchars($auth_res ?? $order_res ?? $key_res ?? 'No response')
        );
        exit();
    }

} else {
    // ── Simulation Mode ───────────────────────────────────────────────────
    header("Location: paymob_simulation.php?app_id=$app_id&provider=$provider");
    exit();
}

/**
 * Display premium HTML error card.
 */
function displayError($title, $message) {
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Error — COVERLY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #fafbfe; font-family: 'Inter', sans-serif; }
        .error-card-container { display: flex; justify-content: center; align-items: center; min-height: 70vh; padding: 20px; }
        .error-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #fee2e2; max-width: 550px; width: 100%; padding: 40px; text-align: center; }
        .err-icon { font-size: 56px; color: #ef4444; margin-bottom: 20px; }
        .err-title { font-size: 22px; font-weight: 700; color: #1f2937; margin-bottom: 12px; }
        .err-desc { font-size: 15px; color: #4b5563; line-height: 1.6; margin-bottom: 30px; }
        .err-actions { display: flex; gap: 15px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; transition: 0.2s; border: none; cursor: pointer; }
        .btn-primary { background: #7b1fa2; color: white; }
        .btn-primary:hover { background: #6a1b9a; }
        .btn-secondary { background: #f3f4f6; color: #4b5563; }
        .btn-secondary:hover { background: #e5e7eb; }
        code { background: #f3f4f6; padding: 3px 6px; border-radius: 5px; font-family: monospace; font-size: 13px; color: #be185d; }
    </style>
    <main class="error-card-container">
        <div class="error-card">
            <i class="fa-solid fa-circle-exclamation err-icon"></i>
            <h1 class="err-title"><?php echo $title; ?></h1>
            <p class="err-desc"><?php echo $message; ?></p>
            <div class="err-actions">
                <a href="payment.php?app_id=<?php echo isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0; ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="paymob_simulation.php?app_id=<?php echo isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0; ?>&provider=<?php echo isset($_GET['provider']) ? htmlspecialchars($_GET['provider']) : 'visa'; ?>" class="btn btn-primary">
                    <i class="fa-solid fa-flask"></i> Launch Simulation
                </a>
            </div>
        </div>
    </main>
    <?php
    include 'includes/footer.php';
}
?>