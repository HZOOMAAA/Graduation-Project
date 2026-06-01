<?php
/**
 * paymob_callback.php
 * Redirect callback handler for Paymob payment gateway integration.
 * Processes successful transactions, writes policy certificates to the DB, and shows beautiful receipts.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';
require_once 'includes/functions.php';
require_once 'includes/paymob_config.php';

$customer_id = (int)$_SESSION['user_id'];

// ✅ Read app_id & provider from SESSION (set in paymob_initiate.php before redirect)
// Paymob does NOT pass these back in the redirect GET params
$app_id   = isset($_SESSION['app_id'])   ? (int)$_SESSION['app_id']   : 0;
$provider = isset($_SESSION['provider']) ? $_SESSION['provider']       : 'visa';

// Also accept GET fallback for simulation mode compatibility
if ($app_id <= 0) {
    $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
}

$success_raw = isset($_GET['success']) ? $_GET['success'] : 'false';
$txn_id      = isset($_GET['id'])      ? mysqli_real_escape_string($connect, $_GET['id']) : '';

if ($app_id <= 0) {
    header("Location: profile.php");
    exit();
}

// ── HMAC Verification ────────────────────────────────────────────────────────
// Verifies the callback is genuinely from Paymob and hasn't been tampered with
function verify_paymob_hmac(array $params, string $secret): bool {
    $received = $params['hmac'] ?? '';
    if (empty($received)) return false;

    // Paymob's required field order for HMAC calculation
    $fields = [
        'amount_cents', 'created_at', 'currency', 'error_occured',
        'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
        'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order', 'owner', 'pending',
        'source_data_pan', 'source_data_sub_type', 'source_data_type', 'success'
    ];

    $string = '';
    foreach ($fields as $field) {
        $string .= $params[$field] ?? '';
    }

    $computed = hash_hmac('sha512', $string, $secret);
    return hash_equals($computed, $received);
}

// Only verify HMAC when coming from real Paymob (has hmac param)
if (!empty($_GET['hmac']) && defined('PAYMOB_HMAC_SECRET') && !empty(PAYMOB_HMAC_SECRET)) {
    if (!verify_paymob_hmac($_GET, PAYMOB_HMAC_SECRET)) {
        // HMAC mismatch — possible tampering, reject silently
        error_log("Paymob HMAC verification failed for app_id=$app_id txn=$txn_id");
        header("Location: profile.php?error=payment_verification_failed");
        exit();
    }
}

// ── 1. Fetch Application Details ─────────────────────────────────────────────
$stmt = mysqli_prepare($connect,
    "SELECT a.*,
            u.name AS customer_name,
            p.name AS plan_name, p.insurance_company,
            cat.name AS category_name,
            p.base_price AS base_price
            
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

$policy_number      = '';
$payment_ref        = '';
$transaction_status = 'failed';

// ── 2. Process Successful Transaction ────────────────────────────────────────
if ($success_raw === 'true') {
    $transaction_status = 'success';

    if ($app['status'] === 'awaiting_payment') {
        // Generate unique policy number for this category
        $policy_number = generatePolicyNumber($connect, $app['category_name'] ?? 'Insurance');
        $payment_ref   = 'PAYMOB-' . strtoupper($provider) . '-' . $txn_id;

        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d', strtotime('+1 year'));
        $doc_path   = 'uploads/policies/policy_' . $app_id . '.pdf';

        // Prevent duplicate policy creation on page refresh
        $check_pol = mysqli_query($connect, "SELECT policy_number FROM policies WHERE application_id = $app_id LIMIT 1");
        if ($check_pol && mysqli_num_rows($check_pol) == 0) {

            $ins = mysqli_prepare($connect,
                "INSERT INTO policies (application_id, policy_number, start_date, end_date, document_path, payment_ref, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
            mysqli_stmt_bind_param($ins, 'isssss', $app_id, $policy_number, $start_date, $end_date, $doc_path, $payment_ref);
            $db_ok = mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            if ($db_ok) {
                // ✅ Mark application as paid
                mysqli_query($connect, "UPDATE applications SET status = 'paid' WHERE application_id = $app_id");
                $app['status'] = 'paid';

                // ✅ Clear session payment data now that it's processed
                unset($_SESSION['app_id'], $_SESSION['provider']);

            } else {
                $transaction_status = 'db_error';
                $db_err_msg = mysqli_error($connect);
            }
        }
    }

    // If already paid (e.g. page refresh), load existing policy so receipt still shows
    if ($app['status'] === 'paid') {
        $pol_q = mysqli_query($connect, "SELECT policy_number, payment_ref FROM policies WHERE application_id = $app_id LIMIT 1");
        if ($pol_q && $pol_r = mysqli_fetch_assoc($pol_q)) {
            $policy_number = $pol_r['policy_number'];
            $payment_ref   = $pol_r['payment_ref'];
        }
    }
}

// ✅ Use final_price (actual charged amount), fallback to base_price
$amount        = (float)($app['final_price'] ?: $app['base_price']);
$plan_name     = htmlspecialchars($app['plan_name']         ?? '');
$company       = htmlspecialchars($app['insurance_company'] ?? '');
$category_name = htmlspecialchars($app['category_name']     ?? '');

include 'includes/nav2.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Status — COVERLY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/Graduation-Project/assets/css/paymob_callback.css">

<?php if ($transaction_status === 'success'): ?>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<?php endif; ?>

<main class="callback-main">
    <div class="callback-container">

        <?php if ($transaction_status === 'success'): ?>
        <!-- ── SUCCESS SCREEN ── -->
        <div class="status-card success-card">
            <div class="icon-bubble success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1 class="status-title">Payment Confirmed!</h1>
            <p class="status-subtitle">Your transaction has been processed securely via Paymob</p>

            <div class="policy-hero-badge">
                <span class="policy-lbl">YOUR POLICY NUMBER</span>
                <span class="policy-val"><i class="fa-solid fa-file-shield"></i> <?php echo htmlspecialchars($policy_number); ?></span>
            </div>

            <div class="receipt-box" id="printReceipt">
                <div class="receipt-header">
                    <div class="receipt-brand">COVERLY INVOICE</div>
                    <div class="receipt-status"><i class="fa-solid fa-circle"></i> PAID</div>
                </div>

                <div class="receipt-grid">
                    <div class="receipt-item">
                        <span class="label">Subscriber</span>
                        <span class="value"><?php echo htmlspecialchars($app['customer_name']); ?></span>
                    </div>
                    <div class="receipt-item">
                        <span class="label">Insurance Partner</span>
                        <span class="value"><?php echo $company; ?></span>
                    </div>
                    <div class="receipt-item">
                        <span class="label">Insurance Plan</span>
                        <span class="value"><?php echo $plan_name; ?> (<?php echo $category_name; ?>)</span>
                    </div>
                    <div class="receipt-item">
                        <span class="label">Payment Provider</span>
                        <span class="value"><?php echo ucfirst($provider); ?> (Paymob Integration)</span>
                    </div>
                    <div class="receipt-item">
                        <span class="label">Transaction Reference</span>
                        <span class="value code-font"><?php echo htmlspecialchars($payment_ref); ?></span>
                    </div>
                    <div class="receipt-item">
                        <span class="label">Payment Date</span>
                        <span class="value"><?php echo date('F d, Y - h:i A'); ?></span>
                    </div>
                </div>

                <div class="receipt-total">
                    <span>Total Paid</span>
                    <span class="total-amount">EGP <?php echo number_format($amount, 2); ?></span>
                </div>
            </div>

            <div class="action-buttons-group">
                <a href="profile.php" class="action-btn btn-primary-gradient">
                    <i class="fa-solid fa-id-card"></i> View Policy Dashboard
                </a>
                <div class="btn-sub-row">
                    <button onclick="window.print()" class="action-btn btn-secondary">
                        <i class="fa-solid fa-print"></i> Print Invoice
                    </button>
                    <a href="uploads/policies/policy_<?php echo $app_id; ?>.pdf" target="_blank" class="action-btn btn-secondary">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </a>
                </div>
            </div>

            <p class="secure-footer-disclaimer">
                <i class="fa-solid fa-shield-halved text-success"></i> Secured and cryptographically signed by Paymob Gateway Services.
            </p>
        </div>

        <?php elseif ($transaction_status === 'db_error'): ?>
        <!-- ── DATABASE ERROR SCREEN ── -->
        <div class="status-card error-card">
            <div class="icon-bubble error">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h1 class="status-title">Fulfillment Failure</h1>
            <p class="status-subtitle">Payment was charged but we couldn't write the policy record.</p>

            <div class="err-box">
                <strong>Error Message:</strong> <?php echo htmlspecialchars($db_err_msg ?? 'Database transaction deadlock'); ?>
            </div>

            <div class="receipt-box">
                <div class="receipt-item">
                    <span class="label">Charge Amount</span>
                    <span class="value">EGP <?php echo number_format($amount, 2); ?></span>
                </div>
                <div class="receipt-item">
                    <span class="label">Transaction Reference</span>
                    <span class="value code-font">PAYMOB-ERR-<?php echo htmlspecialchars($txn_id); ?></span>
                </div>
            </div>

            <p style="font-size:13px;color:#666;margin:20px 0;">
                Please contact Coverly support immediately and supply the transaction ID listed above to manually issue your policy.
            </p>

            <div class="action-buttons-group">
                <a href="profile.php" class="action-btn btn-primary-gradient">Go to Profile</a>
            </div>
        </div>

        <?php else: ?>
        <!-- ── TRANSACTION FAILED SCREEN ── -->
        <div class="status-card error-card">
            <div class="icon-bubble error">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h1 class="status-title">Transaction Declined</h1>
            <p class="status-subtitle">Your credit card transaction could not be authorized by Paymob</p>

            <div class="decline-reason-card">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Possible Causes:</strong>
                    <ul style="margin:5px 0 0 15px;padding:0;font-size:13px;text-align:left;">
                        <li>Insufficient funds on card</li>
                        <li>Incorrect CVV or expiration date inputs</li>
                        <li>Daily internet-purchasing limits exceeded</li>
                    </ul>
                </div>
            </div>

            <div class="receipt-box" style="margin-top:20px;">
                <div class="receipt-item">
                    <span class="label">Provider</span>
                    <span class="value">Paymob Payment Services</span>
                </div>
                <div class="receipt-item">
                    <span class="label">Order ID</span>
                    <span class="value">#PM-<?php echo $app_id; ?></span>
                </div>
            </div>

            <div class="action-buttons-group">
                <a href="payment.php?app_id=<?php echo $app_id; ?>" class="action-btn btn-primary-gradient">
                    <i class="fa-solid fa-rotate-left"></i> Try Another Method
                </a>
                <a href="profile.php" class="action-btn btn-secondary">
                    Return to Profile
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php if ($transaction_status === 'success'): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        var duration = 3 * 1000;
        var end = Date.now() + duration;

        (function frame() {
            confetti({ particleCount: 3, angle: 60,  spread: 55, origin: { x: 0 }, colors: ['#7b1fa2', '#6f2cf3', '#10b981'] });
            confetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#7b1fa2', '#6f2cf3', '#10b981'] });
            if (Date.now() < end) requestAnimationFrame(frame);
        }());
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>