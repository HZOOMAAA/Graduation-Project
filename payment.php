<?php
session_start();

// Guard: must be logged in as customer
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';

$customer_id = (int)$_SESSION['user_id'];
$app_id      = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

// Validate app_id and ownership
if ($app_id <= 0) {
    header("Location: profile.php");
    exit();
}

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

if (!$app) {
    header("Location: profile.php");
    exit();
}

// Only allow payment if status is awaiting_payment
if ($app['status'] !== 'awaiting_payment') {
    header("Location: profile.php");
    exit();
}

$customer_name   = htmlspecialchars($app['customer_name'] ?? $_SESSION['name'] ?? 'Customer');
$plan_name       = htmlspecialchars($app['plan_name'] ?? 'Insurance Plan');
$company         = htmlspecialchars($app['insurance_company'] ?? 'Coverly Partner');
$category_name   = htmlspecialchars($app['category_name'] ?? 'Insurance');
$amount          = number_format((float)$app['final_price'], 2);
$amount_raw      = (float)$app['final_price'];

// Category-based prefix preview for display
$cat_lc = strtolower($app['category_name'] ?? '');
if      (strpos($cat_lc, 'car')      !== false) $pol_prefix = 'CAR';
elseif  (strpos($cat_lc, 'health')   !== false) $pol_prefix = 'HLT';
elseif  (strpos($cat_lc, 'life')     !== false) $pol_prefix = 'LFE';
elseif  (strpos($cat_lc, 'property') !== false) $pol_prefix = 'PRP';
else                                              $pol_prefix = 'INS';

include 'includes/nav2.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Payment — COVERLY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/Graduation-Project/assets/css/payment.css">

<main class="main-content">
    <div class="container">
        <div class="payment-card">
            <div class="card-header">
                <h1 class="card-title">Insurance Payment Portal</h1>
                <p class="card-subtitle">Secure payment for your approved insurance application</p>
            </div>

            <div class="info-grid">
                <div>
                    <span class="info-label">Company</span>
                    <h3 class="info-value"><?php echo $company; ?></h3>
                </div>
                <div>
                    <span class="info-label">Customer Name</span>
                    <h3 class="info-value" id="customerName"><?php echo $customer_name; ?></h3>
                </div>
                <div>
                    <span class="info-label">Policy Reference Prefix</span>
                    <h3 class="info-value">
                        <span style="background:#f0e6ff;color:#7b1fa2;padding:3px 12px;border-radius:20px;font-size:13px;font-weight:700;">
                            <?php echo $pol_prefix; ?>-<?php echo date('Y'); ?>-XXXXXXXX
                        </span>
                    </h3>
                </div>
                <div>
                    <span class="info-label">Plan</span>
                    <h3 class="info-value" style="font-size:15px;"><?php echo $plan_name; ?></h3>
                </div>
                <div>
                    <span class="info-label">Category</span>
                    <h3 class="info-value"><?php echo $category_name; ?></h3>
                </div>
                <div class="info-amount">
                    <span class="info-label">Total Amount Due</span>
                    <h2 class="amount-value">EGP <?php echo $amount; ?></h2>
                </div>
            </div>

            <h2 class="section-title">Select Payment Method</h2>
            <p class="section-subtitle">Choose your preferred payment provider to continue securely.</p>

            <div class="payment-grid">

                <div class="payment-option" onclick="location.href='paymob_initiate.php?app_id=<?php echo $app_id; ?>&provider=visa'">
                    <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" alt="Visa" class="payment-icon">
                    <h3 class="payment-name">Visa</h3>
                    <p class="payment-desc">Pay securely using Visa via Paymob portal.</p>
                    <button class="btn-continue">Continue</button>
                </div>

                <div class="payment-option" onclick="location.href='fawry.php?app_id=<?php echo $app_id; ?>'">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Fawry_logo.svg/320px-Fawry_logo.svg.png" alt="Fawry" class="payment-icon" style="object-fit:contain;">
                    <h3 class="payment-name">Fawry</h3>
                    <p class="payment-desc">Complete your payment through Fawry services.</p>
                    <button class="btn-continue">Continue</button>
                </div>

                <div class="payment-option" onclick="location.href='paymob_initiate.php?app_id=<?php echo $app_id; ?>&provider=mastercard'">
                    <img src="https://cdn-icons-png.flaticon.com/512/196/196561.png" alt="Mastercard" class="payment-icon">
                    <h3 class="payment-name">Mastercard</h3>
                    <p class="payment-desc">Secure Mastercard payment via Paymob portal.</p>
                    <button class="btn-continue">Continue</button>
                </div>

            </div>

            <div class="secure-banner">
                <p><i class="fas fa-shield-alt icon-secure"></i> All transactions are end-to-end encrypted. Your card data is never stored.</p>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>