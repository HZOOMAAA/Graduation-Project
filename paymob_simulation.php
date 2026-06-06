<?php
/**
 * paymob_simulation.php
 * High-fidelity, premium mock of Paymob's Visa/Mastercard checkout portal.
 * Ideal for local presentations and offline operations.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';

$customer_id = (int)$_SESSION['user_id'];
$app_id      = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$provider    = (isset($_GET['provider']) && $_GET['provider'] === 'mastercard') ? 'mastercard' : 'visa';

if ($app_id <= 0) {
    header("Location: profile.php");
    exit();
}

// ── Fetch application details ──
$stmt = mysqli_prepare($connect,
    "SELECT a.application_id, a.final_price, a.status,
            u.name AS customer_name,
            p.name AS plan_name, p.insurance_company,
            cat.name AS category_name
     FROM applications a
     LEFT JOIN users u         ON a.customer_id = u.user_id
     LEFT JOIN insurance_plans p ON a.plan_id   = p.plan_id
     LEFT JOIN categories cat  ON a.category_id = cat.category_id
     WHERE a.application_id = ? AND a.customer_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $app_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$app || $app['status'] !== 'awaiting_payment') {
    header("Location: profile.php");
    exit();
}

$amount = (float)$app['final_price'];
$amount_raw_cents = round($amount * 100);
$customer_name = htmlspecialchars($app['customer_name'] ?? '');
$plan_name     = htmlspecialchars($app['plan_name'] ?? '');
$company       = htmlspecialchars($app['insurance_company'] ?? 'Coverly Partner');
$category_name = htmlspecialchars($app['category_name'] ?? '');
$is_mc         = ($provider === 'mastercard');

// ── Pre-create Pending Policy & Payment Records for Simulation Mode ──
require_once 'includes/functions.php';
$sim_order_id = 'SIM-ORDER-' . $app_id . '-' . mt_rand(1000, 9999);

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
    mysqli_query($connect, "UPDATE payments SET amount = $amount, status = 'pending', transaction_ref = '$sim_order_id' WHERE payment_id = $payment_id");
} else {
    $ins_pay = mysqli_prepare($connect,
        "INSERT INTO payments (policy_id, amount, method, status, transaction_ref)
         VALUES (?, ?, 'credit_card', 'pending', ?)"
    );
    mysqli_stmt_bind_param($ins_pay, 'ids', $policy_id, $amount, $sim_order_id);
    mysqli_stmt_execute($ins_pay);
    mysqli_stmt_close($ins_pay);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paymob Secure Payment Portal</title>
    <!-- Modern Fonts and CSS Framework Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/Graduation-Project/assets/css/paymob_simulation.css">
</head>
<body>

<div class="paymob-outer">
    <!-- Top Secure Lock Banner -->
    <div class="secure-top-bar">
        <span><i class="fa-solid fa-lock text-"></i> 256-Bit SSL Secured Transaction Channel</span>
        <span class="sandbox-tag"><i class="fa-solid fa-flask"></i> Paymob Sandbox Environment</span>
    </div>

    <div class="paymob-container">
        <!-- Left Column: Invoice summary -->
        <div class="invoice-section">
            <div class="brand-header">
                <div class="app-logo">COVERLY</div>
                <div class="paymob-badge">
                    <span class="pow-text">powered by</span>
                    <span class="paymob-txt">pay<span class="purple-txt">mob</span></span>
                </div>
            </div>

            <div class="merchant-box">
                <span class="lbl">MERCHANT</span>
                <span class="val"><?php echo $company; ?> Insurance</span>
            </div>

            <div class="divider"></div>

            <div class="order-summary">
                <div class="order-summary-hdr"><i class="fa-regular fa-file-lines"></i> Order Details</div>
                
                <div class="invoice-row">
                    <span>Application ID</span>
                    <span class="val-dark">#<?php echo $app_id; ?></span>
                </div>
                <div class="invoice-row">
                    <span>Insurance Plan</span>
                    <span class="val-dark"><?php echo $plan_name; ?></span>
                </div>
                <div class="invoice-row">
                    <span>Category</span>
                    <span class="val-dark"><?php echo $category_name; ?></span>
                </div>
                <div class="invoice-row">
                    <span>Subscriber Name</span>
                    <span class="val-dark"><?php echo $customer_name; ?></span>
                </div>
            </div>

            <div class="divider"></div>

            <div class="price-box">
                <div class="price-header">Amount Due</div>
                <div class="price-amount">
                    <span class="currency">EGP</span> 
                    <span class="number"><?php echo number_format($amount, 2); ?></span>
                </div>
            </div>

            <div class="security-standards">
                <div class="std-item"><i class="fa-solid fa-shield-halved"></i> PCI-DSS Compliant</div>
                <div class="std-item"><i class="fa-solid fa-check"></i> Certified Security</div>
            </div>
        </div>

        <!-- Right Column: Card form -->
        <div class="payment-section">
            <div class="payment-hdr">
                <div class="payment-title">
                    <i class="fa-solid fa-credit-card"></i>
                    Pay with Card
                </div>
                <div class="payment-subtitle">
                    Enter your MasterCard or Visa details below to process payment
                </div>
            </div>

            <!-- Loader Screen (overlay within card panel during submission) -->
            <div class="processing-screen" id="processingScreen" style="display:none;">
                <div class="gateway-logo">
                    <span class="pm-p1">pay</span><span class="pm-p2">mob</span>
                </div>
                <div class="spinner-ring">
                    <div></div><div></div><div></div><div></div>
                </div>
                <h3 class="proc-title">Processing Your Transaction</h3>
                <p class="proc-desc">Contacting Paymob authorization center... Do not refresh or close this tab.</p>
            </div>

            <form id="paymobForm" novalidate>
                <!-- Alert Banner -->
                <div class="alert-error" id="errorBanner" style="display:none;"></div>

                <!-- Cardholder Name -->
                <div class="input-group">
                    <label class="input-lbl">CARDHOLDER NAME</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user field-icon"></i>
                        <input type="text" id="holderName" placeholder="Name as printed on card" class="field-input" value="<?php echo $customer_name; ?>">
                    </div>
                </div>

                <!-- Card Number -->
                <div class="input-group">
                    <label class="input-lbl">CARD NUMBER</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-credit-card field-icon"></i>
                        <input type="text" id="cardNumber" placeholder="0000 0000 0000 0000" class="field-input" maxlength="19">
                        <span class="brand-preview" id="cardBrandIcon">
                            <?php if ($is_mc): ?>
                                <i class="fa-brands fa-cc-mastercard brand-icon mc"></i>
                            <?php else: ?>
                                <i class="fa-brands fa-cc-visa brand-icon visa"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="col-grid">
                    <!-- Expiry -->
                    <div class="input-group">
                        <label class="input-lbl">EXPIRY DATE</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-calendar field-icon"></i>
                            <input type="text" id="cardExpiry" placeholder="MM/YY" class="field-input" maxlength="5">
                        </div>
                    </div>
                    <!-- CVV -->
                    <div class="input-group">
                        <label class="input-lbl">CVV / SECURITY CODE</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="cardCvv" placeholder="•••" class="field-input" maxlength="3">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-pay-btn" id="paymobSubmit">
                    <span>Pay Securely EGP <?php echo number_format($amount, 2); ?></span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="secure-disclaimer">
                    <i class="fa-solid fa-shield-cat"></i> Your connection is encrypted. Paymob protects your card details using financial-grade security layers.
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Input formatters
    const numInput = document.getElementById('cardNumber');
    const expInput = document.getElementById('cardExpiry');
    const cvvInput = document.getElementById('cardCvv');
    const nameInput = document.getElementById('holderName');
    const cardBrandIcon = document.getElementById('cardBrandIcon');

    // Live Card Brand Recognizer & Format
    numInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = v.replace(/(.{4})/g, '$1 ').trim();
        
        // Dynamically change logo based on card starting digits
        if (v.startsWith('4')) {
            cardBrandIcon.innerHTML = '<i class="fa-brands fa-cc-visa brand-icon visa"></i>';
        } else if (v.startsWith('5') || v.startsWith('2')) {
            cardBrandIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard brand-icon mc"></i>';
        } else {
            cardBrandIcon.innerHTML = '<i class="fa-solid fa-credit-card brand-icon"></i>';
        }
    });

    // Expiry Slash auto-formatter
    expInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        if (v.length >= 3) {
            v = v.substring(0, 2) + '/' + v.substring(2, 4);
        }
        this.value = v;
    });

    // Form Submission Processing
    document.getElementById('paymobForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const errBanner = document.getElementById('errorBanner');
        errBanner.style.display = 'none';

        // Validations
        const name = nameInput.value.trim();
        const cardNum = numInput.value.replace(/\s/g, '');
        const expiry = expInput.value;
        const cvv = cvvInput.value;

        if (!name) { showErr('Cardholder Name is required.'); return; }
        if (cardNum.length !== 16) { showErr('Please enter a valid 16-digit card number.'); return; }
        if (expiry.length !== 5 || !expiry.includes('/')) { showErr('Enter expiry in MM/YY format.'); return; }
        if (cvv.length !== 3) { showErr('Please enter a valid 3-digit CVV.'); return; }

        // Start premium loading overlay
        document.getElementById('processingScreen').style.display = 'flex';
        document.getElementById('paymobForm').style.opacity = '0.15';
        document.getElementById('paymobForm').style.pointerEvents = 'none';

        // Simulate secure verification time (1.8 seconds)
        setTimeout(() => {
            const randomTxnId = Math.floor(100000000 + Math.random() * 900000000);
            const app_id = "<?php echo $app_id; ?>";
            const provider = "<?php echo $provider; ?>";
            const amountCents = "<?php echo $amount_raw_cents; ?>";

            // Redirect to callback page mimicking the exact Paymob redirection response payload shape
            window.location.href = `paymob_callback.php?success=true&id=${randomTxnId}&amount_cents=${amountCents}&currency=EGP&pending=false&app_id=${app_id}&provider=${provider}`;
        }, 1800);
    });

    function showErr(msg) {
        errBanner.textContent = msg;
        errBanner.style.display = 'block';
        errBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>

</body>
</html>
